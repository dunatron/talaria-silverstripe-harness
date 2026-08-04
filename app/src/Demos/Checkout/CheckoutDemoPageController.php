<?php

namespace {

    use App\Demos\Checkout\DemoCheckoutException;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\HTTPResponse;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Environment;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\Security\Security;
    use SilverStripe\View\ArrayData;
    use Talaria\TalariaClient;

    /**
     * @extends PageController<CheckoutDemoPage>
     */
    class CheckoutDemoPageController extends PageController
    {
        private static $allowed_actions = [
            'index',
            'runHappyPath',
            'runPaymentFailure',
            'status',
        ];

        public function index($request = null)
        {
            return $this;
        }

        /**
         * @return ArrayList<ArrayData>
         */
        public function WorkflowCases(): ArrayList
        {
            $cases = [
                [
                    'Title' => 'Happy path',
                    'Description' => 'Bootstrap TalariaClient, then validate → reserve → charge → confirm. Four info events + flush.',
                    'Href' => $this->actionLink('runHappyPath'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'Payment failure',
                    'Description' => 'Same setup through reserve, then handled DemoCheckoutException + abort warning.',
                    'Href' => $this->actionLink('runPaymentFailure'),
                    'Kind' => 'exception',
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
            $message = $session?->get('CheckoutDemoFlash');
            if (is_string($message) && $message !== '') {
                $session->clear('CheckoutDemoFlash');

                return $message;
            }

            return null;
        }

        public function runHappyPath(): HTTPResponse
        {
            $orderId = 'order-' . bin2hex(random_bytes(4));
            $cartId = 'cart-' . bin2hex(random_bytes(4));
            $client = $this->bootstrapClient($orderId, $cartId);

            $client->captureMessage('Cart validated', 'info', $this->stepContext('validate_cart'));
            $client->captureMessage('Stock reserved', 'info', $this->stepContext('reserve_stock', [
                'sku' => 'SKU-DEMO-1',
                'qty' => 2,
            ]));
            $client->captureMessage('Payment charged', 'info', $this->stepContext('charge_payment', [
                'amount_cents' => 4999,
            ]));
            $client->captureMessage('Order confirmed', 'info', $this->stepContext('confirm_order'));

            return $this->done(
                "Happy path complete for {$orderId}: validate_cart → reserve_stock → charge_payment → confirm_order",
            );
        }

        public function runPaymentFailure(): HTTPResponse
        {
            $orderId = 'order-' . bin2hex(random_bytes(4));
            $cartId = 'cart-' . bin2hex(random_bytes(4));
            $client = $this->bootstrapClient($orderId, $cartId);

            $client->captureMessage('Cart validated', 'info', $this->stepContext('validate_cart'));
            $client->captureMessage('Stock reserved', 'info', $this->stepContext('reserve_stock', [
                'sku' => 'SKU-DEMO-1',
                'qty' => 2,
            ]));

            try {
                throw new DemoCheckoutException('Payment gateway refused card');
            } catch (DemoCheckoutException $e) {
                $client->captureException($e, array_merge(
                    $this->stepContext('charge_payment', [
                        'amount_cents' => 4999,
                        'payment_method' => 'card_demo',
                    ]),
                    [
                        'title' => 'Checkout payment failed',
                        'mechanism' => [
                            'type' => 'generic',
                            'handled' => true,
                            'synthetic' => false,
                        ],
                    ],
                ));
            }

            $client->captureMessage('Checkout aborted after payment failure', 'warning', $this->stepContext('checkout_aborted'));

            return $this->done(
                "Payment failure path for {$orderId}: validate_cart → reserve_stock → handled exception → checkout_aborted",
            );
        }

        public function status(): HTTPResponse
        {
            $status = $this->ConfigStatus()->toMap();
            $body = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $response = HTTPResponse::create($body ?: '{}', 200);
            $response->addHeader('Content-Type', 'application/json');

            return $response;
        }

        private function bootstrapClient(string $orderId, string $cartId): TalariaClient
        {
            $client = $this->client();

            // Re-apply on every action: Injector client is process-shared across FPM workers.
            $client->setTags([
                'module' => 'checkout',
                'feature' => 'place_order',
                'source' => 'talaria_ss_test',
                'entity' => 'CheckoutDemoPage',
            ]);
            $client->setExtra([
                'order_id' => $orderId,
                'cart_id' => $cartId,
                'page_url' => $this->Link() ?: '/checkout-demo',
            ]);

            $member = Security::getCurrentUser();
            $client->setUser($member ? (string) $member->ID : 'guest-harness');

            return $client;
        }

        /**
         * @param array<string, mixed> $extra
         * @return array{tags: array<string, string>, extra: array<string, mixed>}
         */
        private function stepContext(string $step, array $extra = []): array
        {
            return [
                'tags' => [
                    'source' => 'talaria_ss_test',
                    'entity' => 'CheckoutDemoPage',
                    'module' => 'checkout',
                    'step' => $step,
                ],
                'extra' => array_merge(['harness' => 'checkout_demo'], $extra),
            ];
        }

        private function actionLink(string $action): string
        {
            return Controller::join_links($this->Link(), $action);
        }

        private function client(): TalariaClient
        {
            return Injector::inst()->get(TalariaClient::class);
        }

        private function done(string $message): HTTPResponse
        {
            try {
                $this->client()->flush();
            } catch (\Throwable $e) {
                $message .= ' — flush error: ' . $e->getMessage();
            }

            $request = $this->getRequest();
            $session = $request->getSession();
            if ($session) {
                $session->set('CheckoutDemoFlash', $message);
                $session->save($request);
            }

            return $this->redirect($this->Link() ?: '/checkout-demo');
        }
    }
}
