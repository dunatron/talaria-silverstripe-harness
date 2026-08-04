<?php

namespace {

    use App\Demos\Checkout\DemoCheckoutException;
    use Psr\Log\LoggerInterface;
    use SilverStripe\Control\Controller;
    use SilverStripe\Control\HTTPResponse;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Environment;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\View\ArrayData;
    use Talaria\TalariaClient;

    /**
     * @extends PageController<TalariaTestPage>
     */
    class TalariaTestPageController extends PageController
    {
        private static $allowed_actions = [
            'index',
            'logInfo',
            'logWarning',
            'logErrorMessage',
            'logErrorWithException',
            'captureMessage',
            'captureException',
            'captureChainedException',
            'captureNamespacedException',
            'captureErrorThrowable',
            'captureDeepStack',
            'captureBatch',
            'status',
        ];

        public function index($request = null)
        {
            return $this;
        }

        /**
         * @return ArrayList<ArrayData>
         */
        public function TestCases(): ArrayList
        {
            $cases = [
                [
                    'Title' => 'PSR info (Monolog)',
                    'Description' => 'LoggerInterface::info — filtered unless minLevel allows info',
                    'Href' => $this->actionLink('logInfo'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'PSR warning (Monolog)',
                    'Description' => 'LoggerInterface::warning with tags + extra',
                    'Href' => $this->actionLink('logWarning'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'PSR error message only',
                    'Description' => 'error() without exception — no structured exception object',
                    'Href' => $this->actionLink('logErrorMessage'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'PSR error + exception',
                    'Description' => "error(..., ['exception' => \$e]) — primary Silverstripe path",
                    'Href' => $this->actionLink('logErrorWithException'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Client captureMessage',
                    'Description' => 'TalariaClient::captureMessage (info)',
                    'Href' => $this->actionLink('captureMessage'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'Client captureException',
                    'Description' => 'TalariaClient::captureException — expect platform=php + frames',
                    'Href' => $this->actionLink('captureException'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Chained exceptions',
                    'Description' => 'getPrevious() chain — expect exception.values length 3',
                    'Href' => $this->actionLink('captureChainedException'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Namespaced exception',
                    'Description' => 'App\\Demos\\Checkout\\DemoCheckoutException — short title',
                    'Href' => $this->actionLink('captureNamespacedException'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Error throwable',
                    'Description' => 'PHP \\Error (not Exception)',
                    'Href' => $this->actionLink('captureErrorThrowable'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Deep app stack',
                    'Description' => 'Nested helpers → app frames in stacktrace',
                    'Href' => $this->actionLink('captureDeepStack'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Batch (3 events)',
                    'Description' => 'Message + exception + Monolog error in one request',
                    'Href' => $this->actionLink('captureBatch'),
                    'Kind' => 'batch',
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
            $message = $session?->get('TalariaTestFlash');
            if (is_string($message) && $message !== '') {
                $session->clear('TalariaTestFlash');

                return $message;
            }

            return null;
        }

        public function logInfo(): HTTPResponse
        {
            $this->logger()->info('Talaria harness: info message', $this->baseContext('logInfo'));

            return $this->done('Sent PSR info (check minLevel if missing in dashboard)');
        }

        public function logWarning(): HTTPResponse
        {
            $this->logger()->warning('Talaria harness: warning message', $this->baseContext('logWarning'));

            return $this->done('Sent PSR warning');
        }

        public function logErrorMessage(): HTTPResponse
        {
            $this->logger()->error('Talaria harness: error without exception', $this->baseContext('logErrorMessage'));

            return $this->done('Sent PSR error message (no exception object)');
        }

        public function logErrorWithException(): HTTPResponse
        {
            try {
                $this->explode('logErrorWithException');
            } catch (\Throwable $e) {
                $this->logger()->error(
                    'Talaria harness: caught exception via Monolog',
                    array_merge($this->baseContext('logErrorWithException'), ['exception' => $e]),
                );
            }

            return $this->done('Sent PSR error with exception — expect structured frames');
        }

        public function captureMessage(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria harness: direct captureMessage', 'info', [
                'tags' => $this->tags('captureMessage'),
                'extra' => ['harness' => 'captureMessage'],
            ]);

            return $this->done('Sent TalariaClient::captureMessage');
        }

        public function captureException(): HTTPResponse
        {
            try {
                $this->explode('captureException');
            } catch (\Throwable $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureException'),
                    'extra' => ['harness' => 'captureException', 'cart_id' => 'test-cart-1'],
                ]);
            }

            return $this->done('Sent captureException — expect platform=php + exception.values');
        }

        public function captureChainedException(): HTTPResponse
        {
            $root = new \RuntimeException('root cause: db down', 1001);
            $mid = new \InvalidArgumentException('middle: bad cart', 1002, $root);
            $top = new \LogicException('top: checkout aborted', 1003, $mid);

            $this->client()->captureException($top, [
                'tags' => $this->tags('captureChainedException'),
                'extra' => ['harness' => 'captureChainedException'],
            ]);

            return $this->done('Sent chained exception (3 values, oldest→newest)');
        }

        public function captureNamespacedException(): HTTPResponse
        {
            try {
                throw new DemoCheckoutException('Payment gateway refused card');
            } catch (DemoCheckoutException $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureNamespacedException'),
                    'extra' => ['harness' => 'captureNamespacedException'],
                ]);
            }

            return $this->done('Sent DemoCheckoutException');
        }

        public function captureErrorThrowable(): HTTPResponse
        {
            try {
                throw new \Error('Talaria harness: synthetic Error throwable');
            } catch (\Error $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureErrorThrowable'),
                    'extra' => ['harness' => 'captureErrorThrowable'],
                ]);
            }

            return $this->done('Sent \\Error via captureException');
        }

        public function captureDeepStack(): HTTPResponse
        {
            try {
                $this->levelOne();
            } catch (\Throwable $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureDeepStack'),
                    'extra' => ['harness' => 'captureDeepStack'],
                ]);
            }

            return $this->done('Sent deep stack exception');
        }

        public function captureBatch(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria harness: batch message', 'warning', [
                'tags' => $this->tags('captureBatch'),
                'extra' => ['harness' => 'captureBatch', 'seq' => 1],
            ]);

            try {
                throw new \RuntimeException('Talaria harness: batch exception');
            } catch (\Throwable $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureBatch'),
                    'extra' => ['harness' => 'captureBatch', 'seq' => 2],
                ]);
            }

            $this->logger()->error(
                'Talaria harness: batch monolog error',
                array_merge(
                    $this->baseContext('captureBatch'),
                    ['exception' => new \RuntimeException('batch via monolog'), 'seq' => 3],
                ),
            );

            return $this->done('Sent batch of 3 events');
        }

        public function status(): HTTPResponse
        {
            $status = $this->ConfigStatus()->toMap();
            $body = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $response = HTTPResponse::create($body ?: '{}', 200);
            $response->addHeader('Content-Type', 'application/json');

            return $response;
        }

        private function actionLink(string $action): string
        {
            return Controller::join_links($this->Link(), $action);
        }

        private function logger(): LoggerInterface
        {
            return Injector::inst()->get(LoggerInterface::class);
        }

        private function client(): TalariaClient
        {
            return Injector::inst()->get(TalariaClient::class);
        }

        /**
         * @return array<string, mixed>
         */
        private function baseContext(string $action): array
        {
            return [
                'tags' => $this->tags($action),
                'harness' => $action,
                'type' => 'talaria_ss_test',
            ];
        }

        /**
         * @return array<string, string>
         */
        private function tags(string $action): array
        {
            return [
                'source' => 'talaria_ss_test',
                'entity' => 'TalariaTestPage',
                'operation' => $action,
            ];
        }

        private function explode(string $action): never
        {
            throw new \RuntimeException("Talaria harness boom ({$action})");
        }

        private function levelOne(): void
        {
            $this->levelTwo();
        }

        private function levelTwo(): void
        {
            $this->levelThree();
        }

        private function levelThree(): void
        {
            throw new \RuntimeException('Talaria harness: deep stack boom');
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
                $session->set('TalariaTestFlash', $message);
                $session->save($request);
            }

            return $this->redirect($this->Link() ?: '/talaria-test');
        }
    }
}
