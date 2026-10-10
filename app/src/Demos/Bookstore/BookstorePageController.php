<?php

namespace {

    use App\Demos\Bookstore\Book;
    use App\Demos\Bookstore\BookstoreValidationException;
    use App\Demos\Bookstore\Cart;
    use App\Demos\Bookstore\Catalog;
    use App\Demos\Bookstore\CheckoutService;
    use GuzzleHttp\Client as GuzzleClient;
    use GuzzleHttp\HandlerStack;
    use SilverStripe\CMS\Model\SiteTree;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\HTTPRequest;
    use SilverStripe\Control\HTTPResponse;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Environment;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\View\ArrayData;
    use Talaria\TalariaClient;
    use Talaria\Tracing\GuzzleMiddleware;
    use Talaria\Tracing\SpanKind;

    /**
     * @extends PageController<BookstorePage>
     */
    class BookstorePageController extends PageController
    {
        private static $allowed_actions = [
            'index',
            'cart',
            'checkout',
            'add',
            'update',
            'remove',
            'clearCart',
            'placeOrder',
            'failPayment',
            'failStock',
            'failEmpty',
            'status',
            'slow',
            'nPlusOne',
            'outbound',
            'queueJob',
        ];

        private ?Catalog $catalogInstance = null;
        private ?Cart $cartInstance = null;
        private ?CheckoutService $checkoutService = null;

        public function index($request = null)
        {
            $svc = $this->bookstoreCheckout();
            $svc->logBrowse($svc->baseLogger());

            return $this;
        }

        public function cart(HTTPRequest $request)
        {
            $this->bookstoreCheckout()->cartLogger()->info('Viewing cart', [
                'tags' => ['operation' => 'cart_view'],
                'item_count' => $this->bookstoreCart()->itemCount(),
            ]);

            return $this;
        }

        public function checkout(HTTPRequest $request)
        {
            $this->bookstoreCheckout()->baseLogger()->info('Opened checkout', [
                'tags' => ['operation' => 'checkout', 'surface' => 'checkout'],
                'item_count' => $this->bookstoreCart()->itemCount(),
            ]);

            return $this;
        }

        public function CatalogBooks(): ArrayList
        {
            $list = ArrayList::create();
            foreach ($this->bookstoreCatalog()->all() as $book) {
                $list->push($this->bookData($book));
            }

            return $list;
        }

        public function CartLines(): ArrayList
        {
            $list = ArrayList::create();
            foreach ($this->bookstoreCart()->detailedLines() as $line) {
                $list->push(ArrayData::create([
                    'Book' => $this->bookData($line['book']),
                    'Qty' => $line['qty'],
                    'LineTotalFormatted' => $line['line_total_formatted'],
                    'UpdateLink' => $this->actionLink('update'),
                    'RemoveLink' => Controller::join_links(
                        $this->actionLink('remove'),
                        '?book=' . rawurlencode($line['book']->id),
                    ),
                ]));
            }

            return $list;
        }

        public function CartSummary(): ArrayData
        {
            $cart = $this->bookstoreCart();

            return ArrayData::create([
                'ItemCount' => $cart->itemCount(),
                'IsEmpty' => $cart->isEmpty(),
                'SubtotalFormatted' => '$' . number_format($cart->subtotalCents() / 100, 2),
                'CartLink' => $this->actionLink('cart'),
                'CheckoutLink' => $this->actionLink('checkout'),
                'ClearLink' => $this->actionLink('clearCart'),
                'CatalogLink' => $this->Link() ?: '/bookstore',
            ]);
        }

        /**
         * @return ArrayList<ArrayData>
         */
        public function CheckoutOutcomes(): ArrayList
        {
            $cases = [
                [
                    'Title' => 'Payment succeeded',
                    'Description' => 'validate → reserve → charge → confirm via Logger child scopes; clears cart.',
                    'Href' => $this->actionLink('placeOrder'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'Payment declined',
                    'Description' => 'captureException on BookstorePaymentException + warn abort; cart kept.',
                    'Href' => $this->actionLink('failPayment'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Out of stock',
                    'Description' => 'withMinLevel(error) drops inventory noise; stock captureException + abort.',
                    'Href' => $this->actionLink('failStock'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Empty cart check',
                    'Description' => 'Clears cart, then warn + captureException at validate (no charge).',
                    'Href' => $this->actionLink('failEmpty'),
                    'Kind' => 'message',
                ],
            ];

            $list = ArrayList::create();
            foreach ($cases as $case) {
                $list->push(ArrayData::create($case));
            }

            return $list;
        }

        /**
         * APM demos: slow request, ORM N+1, outbound Guzzle, queue-style spans.
         *
         * @return ArrayList<ArrayData>
         */
        public function TracingDemos(): ArrayList
        {
            $cases = [
                [
                    'Title' => 'Slow action',
                    'Description' => 'Sleeps 350ms under the request transaction so the waterfall shows a long child span.',
                    'Href' => $this->actionLink('slow'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'N+1 queries',
                    'Description' => 'Loads SiteTree rows then re-fetches each by ID (repeated identical SELECTs).',
                    'Href' => $this->actionLink('nPlusOne'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Outbound Guzzle',
                    'Description' => 'Application Guzzle client with traceparent to example.com (not the ingest client).',
                    'Href' => $this->actionLink('outbound'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'Queued job spans',
                    'Description' => 'Inline producer/consumer messaging spans (QueuedJobs module not required).',
                    'Href' => $this->actionLink('queueJob'),
                    'Kind' => 'message',
                ],
            ];

            $list = ArrayList::create();
            foreach ($cases as $case) {
                $list->push(ArrayData::create($case));
            }

            return $list;
        }

        public function ConfigStatus(): ArrayData
        {
            $dsn = (string) (Environment::getEnv('TALARIA_DSN') ?: '');
            $key = (string) (Environment::getEnv('TALARIA_API_KEY') ?: '');
            $env = (string) (Environment::getEnv('TALARIA_ENVIRONMENT') ?: '');
            $release = (string) (Environment::getEnv('TALARIA_RELEASE') ?: '');

            $keyPreview = $key === ''
                ? '(missing)'
                : (strlen($key) > 12 ? substr($key, 0, 12) . '…' : $key);

            $queueSize = 0;
            try {
                $queueSize = $this->client()->queueSize();
            } catch (\Throwable) {
                // ignore
            }

            return ArrayData::create([
                'Configured' => $dsn !== '' && str_starts_with($key, 'tal_live_'),
                'Dsn' => $dsn !== '' ? $dsn : '(missing TALARIA_DSN)',
                'ApiKey' => $keyPreview,
                'Environment' => $env !== '' ? $env : '(module default)',
                'Release' => $release !== '' ? $release : '(none)',
                'IsDev' => Director::isDev(),
                'QueueSize' => $queueSize,
            ]);
        }

        public function FlashMessage(): ?string
        {
            $session = $this->getRequest()->getSession();
            $message = $session?->get('BookstoreFlash');
            if (is_string($message) && $message !== '') {
                $session->clear('BookstoreFlash');

                return $message;
            }

            return null;
        }

        public function CurrentView(): string
        {
            $action = (string) $this->getRequest()->param('Action');
            if ($action === 'cart' || $action === 'checkout') {
                return $action;
            }

            return 'catalog';
        }

        public function add(HTTPRequest $request): HTTPResponse
        {
            $bookId = (string) ($request->getVar('book') ?: $request->requestVar('book'));
            $qty = max(1, (int) ($request->getVar('qty') ?: $request->requestVar('qty') ?: 1));
            $logger = $this->bookstoreCheckout()->cartLogger();

            try {
                $this->bookstoreCart()->add($bookId, $qty);
                $book = $this->bookstoreCatalog()->findById($bookId);
                $logger->info('Added to cart', [
                    'tags' => ['operation' => 'cart_add'],
                    'extra' => [
                        'book_id' => $bookId,
                        'sku' => $book?->sku,
                        'qty' => $qty,
                    ],
                ]);

                return $this->done("Added {$qty}× " . ($book?->title ?? $bookId) . ' to cart', 'cart');
            } catch (BookstoreValidationException $e) {
                $logger->warn('Add to cart failed', [
                    'tags' => ['operation' => 'cart_add'],
                    'extra' => ['book_id' => $bookId],
                ]);
                $logger->captureException($e, [
                    'title' => 'Bookstore add failed',
                    'tags' => ['operation' => 'cart_add'],
                ]);

                return $this->done('Add failed: ' . $e->getMessage());
            }
        }

        public function update(HTTPRequest $request): HTTPResponse
        {
            $bookId = (string) $request->requestVar('book');
            $qty = (int) $request->requestVar('qty');
            $logger = $this->bookstoreCheckout()->cartLogger();

            try {
                $this->bookstoreCart()->update($bookId, $qty);
                $logger->info('Updated cart line', [
                    'tags' => ['operation' => 'cart_update'],
                    'extra' => ['book_id' => $bookId, 'qty' => $qty],
                ]);

                return $this->done("Updated qty for {$bookId} to {$qty}", 'cart');
            } catch (BookstoreValidationException $e) {
                $logger->warn('Cart update failed', [
                    'tags' => ['operation' => 'cart_update'],
                    'extra' => ['book_id' => $bookId],
                ]);

                return $this->done('Update failed: ' . $e->getMessage(), 'cart');
            }
        }

        public function remove(HTTPRequest $request): HTTPResponse
        {
            $bookId = (string) ($request->getVar('book') ?: $request->requestVar('book'));
            $this->bookstoreCart()->remove($bookId);
            $this->bookstoreCheckout()->cartLogger()->info('Removed from cart', [
                'tags' => ['operation' => 'cart_remove'],
                'extra' => ['book_id' => $bookId],
            ]);

            return $this->done("Removed {$bookId} from cart", 'cart');
        }

        public function clearCart(HTTPRequest $request): HTTPResponse
        {
            $this->bookstoreCart()->clear();
            $this->bookstoreCheckout()->cartLogger()->warn('Cart cleared', [
                'tags' => ['operation' => 'cart_clear'],
            ]);

            return $this->done('Cart cleared');
        }

        public function placeOrder(HTTPRequest $request): HTTPResponse
        {
            if ($this->bookstoreCart()->isEmpty()) {
                $this->ensureSampleCart();
            }

            $result = $this->bookstoreCheckout()->placeOrder();

            return $this->done($result['message'], 'checkout');
        }

        public function failPayment(HTTPRequest $request): HTTPResponse
        {
            if ($this->bookstoreCart()->isEmpty()) {
                $this->ensureSampleCart();
            }

            $result = $this->bookstoreCheckout()->failPayment();

            return $this->done($result['message'], 'checkout');
        }

        public function failStock(HTTPRequest $request): HTTPResponse
        {
            if ($this->bookstoreCart()->isEmpty()) {
                $this->ensureSampleCart();
            }

            $result = $this->bookstoreCheckout()->failStock();

            return $this->done($result['message'], 'checkout');
        }

        public function failEmpty(HTTPRequest $request): HTTPResponse
        {
            $result = $this->bookstoreCheckout()->failEmpty();

            return $this->done($result['message'], 'checkout');
        }

        public function status(): HTTPResponse
        {
            $status = $this->ConfigStatus()->toMap();
            $status['cart'] = $this->bookstoreCart()->toArray();
            $status['view'] = $this->CurrentView();
            $body = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $response = HTTPResponse::create($body ?: '{}', 200);
            $response->addHeader('Content-Type', 'application/json');

            return $response;
        }

        private function ensureSampleCart(): void
        {
            // Prefer an in-stock title so payment paths can reach charge.
            foreach ($this->bookstoreCatalog()->all() as $book) {
                if ($book->inStock()) {
                    $this->bookstoreCart()->add($book->id, 1);
                    $this->bookstoreCheckout()->cartLogger()->info('Seeded sample cart line for checkout demo', [
                        'tags' => ['operation' => 'cart_add'],
                        'extra' => ['book_id' => $book->id, 'sku' => $book->sku, 'qty' => 1],
                    ]);

                    return;
                }
            }
        }

        private function bookData(Book $book): ArrayData
        {
            return ArrayData::create([
                'ID' => $book->id,
                'Title' => $book->title,
                'Author' => $book->author,
                'Sku' => $book->sku,
                'Stock' => $book->stock,
                'InStock' => $book->inStock(),
                'PriceFormatted' => $book->priceFormatted(),
                'AddLink' => Controller::join_links(
                    $this->actionLink('add'),
                    '?book=' . rawurlencode($book->id) . '&qty=1',
                ),
            ]);
        }

        public function slow(HTTPRequest $request): HTTPResponse
        {
            $span = $this->client()->startSpan('usleep', SpanKind::Internal, [
                'talaria.demo' => 'slow',
            ]);
            usleep(350000);
            $span->end();

            return $this->done('Slow action slept 350ms — check the Performance waterfall.');
        }

        public function nPlusOne(HTTPRequest $request): HTTPResponse
        {
            $pages = SiteTree::get()->limit(12);
            $n = 0;
            foreach ($pages as $page) {
                $again = SiteTree::get()->byID($page->ID);
                if ($again) {
                    $n++;
                }
            }

            return $this->done("N+1 demo: re-fetched {$n} SiteTree records by ID (repeated identical SELECTs).");
        }

        public function outbound(HTTPRequest $request): HTTPResponse
        {
            $client = $this->client();
            $stack = HandlerStack::create();
            $stack->push(GuzzleMiddleware::create($client), 'talaria_tracing');
            $http = new GuzzleClient([
                'handler' => $stack,
                'timeout' => 3,
                'http_errors' => false,
            ]);

            try {
                $http->get('https://example.com/');

                return $this->done('Outbound Guzzle request traced with traceparent.');
            } catch (\Throwable $e) {
                return $this->done('Outbound request failed (span still recorded): ' . $e->getMessage());
            }
        }

        public function queueJob(HTTPRequest $request): HTTPResponse
        {
            $client = $this->client();
            $produce = $client->startSpan('publish bookstore.demo', SpanKind::Producer, [
                'messaging.system' => 'silverstripe',
                'messaging.destination.name' => 'bookstore.demo',
                'messaging.operation' => 'publish',
            ]);
            $produce->end();

            $consume = $client->startSpan('process bookstore.demo', SpanKind::Consumer, [
                'messaging.system' => 'silverstripe',
                'messaging.destination.name' => 'bookstore.demo',
                'messaging.operation' => 'process',
            ]);
            usleep(20000);
            $consume->end();

            return $this->done('Queued-job style producer/consumer spans recorded (inline).');
        }

        private function bookstoreCatalog(): Catalog
        {
            return $this->catalogInstance ??= new Catalog();
        }

        private function bookstoreCart(): Cart
        {
            if ($this->cartInstance !== null) {
                return $this->cartInstance;
            }

            $session = $this->getRequest()->getSession();
            if ($session === null) {
                throw new \RuntimeException('Session required for bookstore cart');
            }

            return $this->cartInstance = new Cart($session, $this->bookstoreCatalog());
        }

        private function bookstoreCheckout(): CheckoutService
        {
            return $this->checkoutService ??= new CheckoutService(
                $this->client(),
                $this->bookstoreCatalog(),
                $this->bookstoreCart(),
            );
        }

        private function client(): TalariaClient
        {
            return Injector::inst()->get(TalariaClient::class);
        }

        private function actionLink(string $action): string
        {
            return Controller::join_links($this->Link(), $action);
        }

        private function done(string $message, string $redirectAction = ''): HTTPResponse
        {
            try {
                $this->client()->flush();
            } catch (\Throwable $e) {
                $message .= ' — flush error: ' . $e->getMessage();
            }

            $request = $this->getRequest();
            $session = $request->getSession();
            if ($session) {
                $session->set('BookstoreFlash', $message);
                $session->save($request);
            }

            $target = $this->Link() ?: '/bookstore';
            if ($redirectAction !== '') {
                $target = $this->actionLink($redirectAction);
            }

            return $this->redirect($target);
        }
    }
}
