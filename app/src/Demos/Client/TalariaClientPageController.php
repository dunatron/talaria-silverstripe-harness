<?php

namespace {

    use SilverStripe\Control\Controller;
    use SilverStripe\Control\HTTPResponse;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Environment;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\View\ArrayData;
    use Talaria\TalariaClient;

    /**
     * @extends PageController<TalariaClientPage>
     */
    class TalariaClientPageController extends PageController
    {
        private static $allowed_actions = [
            'index',
            'captureDebug',
            'captureInfo',
            'captureWarning',
            'captureError',
            'captureFatal',
            'captureException',
            'captureWithTitleAndMechanism',
            'captureChainedException',
            'setGlobalTags',
            'setGlobalExtra',
            'setGlobalUser',
            'overrideUser',
            'clearUser',
            'enqueueOnly',
            'flushQueue',
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
                    'Title' => 'captureMessage (debug)',
                    'Description' => 'TalariaClient::captureMessage at debug with per-event tags + extra',
                    'Href' => $this->actionLink('captureDebug'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'captureMessage (info)',
                    'Description' => 'TalariaClient::captureMessage at info with title override',
                    'Href' => $this->actionLink('captureInfo'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'captureMessage (warning)',
                    'Description' => 'TalariaClient::captureMessage at warning',
                    'Href' => $this->actionLink('captureWarning'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'captureMessage (error)',
                    'Description' => 'TalariaClient::captureMessage at error (message only, no exception object)',
                    'Href' => $this->actionLink('captureError'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'captureMessage (fatal)',
                    'Description' => 'TalariaClient::captureMessage at fatal',
                    'Href' => $this->actionLink('captureFatal'),
                    'Kind' => 'message',
                ],
                [
                    'Title' => 'captureException',
                    'Description' => 'Exception with per-event tags, extra, and userId',
                    'Href' => $this->actionLink('captureException'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Title + mechanism',
                    'Description' => 'Custom title + mechanism { type, handled, synthetic }',
                    'Href' => $this->actionLink('captureWithTitleAndMechanism'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'Chained exceptions',
                    'Description' => 'getPrevious() chain — expect exception.values length 3',
                    'Href' => $this->actionLink('captureChainedException'),
                    'Kind' => 'exception',
                ],
                [
                    'Title' => 'setTags (global)',
                    'Description' => 'setTags then capture — global tags merge with per-event tags',
                    'Href' => $this->actionLink('setGlobalTags'),
                    'Kind' => 'context',
                ],
                [
                    'Title' => 'setExtra (global)',
                    'Description' => 'setExtra then capture — global + per-event extra on the event',
                    'Href' => $this->actionLink('setGlobalExtra'),
                    'Kind' => 'context',
                ],
                [
                    'Title' => 'setUser (global)',
                    'Description' => "setUser('harness-user-42') then capture without per-event userId",
                    'Href' => $this->actionLink('setGlobalUser'),
                    'Kind' => 'context',
                ],
                [
                    'Title' => 'Override userId',
                    'Description' => 'Global user set, then per-event userId overrides it',
                    'Href' => $this->actionLink('overrideUser'),
                    'Kind' => 'context',
                ],
                [
                    'Title' => 'Clear user',
                    'Description' => 'setUser(null) then capture — no userId on the event',
                    'Href' => $this->actionLink('clearUser'),
                    'Kind' => 'context',
                ],
                [
                    'Title' => 'Enqueue only',
                    'Description' => 'Capture 2 messages without flush; flash shows queueSize (shutdown may still drain)',
                    'Href' => $this->actionLink('enqueueOnly'),
                    'Kind' => 'batch',
                ],
                [
                    'Title' => 'Flush queue',
                    'Description' => 'Explicit flush(); flash reports post-flush queueSize',
                    'Href' => $this->actionLink('flushQueue'),
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
            $message = $session?->get('TalariaClientFlash');
            if (is_string($message) && $message !== '') {
                $session->clear('TalariaClientFlash');

                return $message;
            }

            return null;
        }

        public function captureDebug(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria client demo: debug message', 'debug', [
                'tags' => $this->tags('captureDebug'),
                'extra' => ['harness' => 'captureDebug', 'level_demo' => 'debug'],
            ]);

            return $this->done('Sent captureMessage at debug');
        }

        public function captureInfo(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria client demo: info message', 'info', [
                'tags' => $this->tags('captureInfo'),
                'extra' => ['harness' => 'captureInfo', 'level_demo' => 'info'],
                'title' => 'Client demo info',
            ]);

            return $this->done('Sent captureMessage at info with title override');
        }

        public function captureWarning(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria client demo: warning message', 'warning', [
                'tags' => $this->tags('captureWarning'),
                'extra' => ['harness' => 'captureWarning', 'level_demo' => 'warning'],
            ]);

            return $this->done('Sent captureMessage at warning');
        }

        public function captureError(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria client demo: error message', 'error', [
                'tags' => $this->tags('captureError'),
                'extra' => ['harness' => 'captureError', 'level_demo' => 'error'],
            ]);

            return $this->done('Sent captureMessage at error (no exception object)');
        }

        public function captureFatal(): HTTPResponse
        {
            $this->client()->captureMessage('Talaria client demo: fatal message', 'fatal', [
                'tags' => $this->tags('captureFatal'),
                'extra' => ['harness' => 'captureFatal', 'level_demo' => 'fatal'],
            ]);

            return $this->done('Sent captureMessage at fatal');
        }

        public function captureException(): HTTPResponse
        {
            try {
                throw new \RuntimeException('Talaria client demo: captureException boom');
            } catch (\Throwable $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureException'),
                    'extra' => [
                        'harness' => 'captureException',
                        'cart_id' => 'client-demo-cart-1',
                    ],
                    'userId' => 'per-event-user-99',
                ]);
            }

            return $this->done('Sent captureException with per-event tags/extra/userId');
        }

        public function captureWithTitleAndMechanism(): HTTPResponse
        {
            try {
                throw new \RuntimeException('Talaria client demo: payment refused');
            } catch (\Throwable $e) {
                $this->client()->captureException($e, [
                    'tags' => $this->tags('captureWithTitleAndMechanism'),
                    'extra' => ['harness' => 'captureWithTitleAndMechanism'],
                    'title' => 'Checkout payment failed',
                    'mechanism' => [
                        'type' => 'generic',
                        'handled' => true,
                        'synthetic' => false,
                    ],
                ]);
            }

            return $this->done('Sent captureException with custom title + mechanism');
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

        public function setGlobalTags(): HTTPResponse
        {
            $client = $this->client();
            $client->setTags([
                'harness' => 'client-demo',
                'region' => 'nz',
            ]);
            $client->captureMessage('Talaria client demo: after setTags', 'info', [
                'tags' => array_merge($this->tags('setGlobalTags'), [
                    'step' => 'after-setTags',
                ]),
                'extra' => ['harness' => 'setGlobalTags'],
            ]);

            return $this->done(
                'Called setTags(harness, region) then captureMessage — expect merged tags '
                . '(globals persist on this client until PHP worker recycle)',
            );
        }

        public function setGlobalExtra(): HTTPResponse
        {
            $client = $this->client();
            $client->setExtra([
                'build' => 'local-ss-test',
                'tenant' => 'harness',
            ]);
            $client->captureMessage('Talaria client demo: after setExtra', 'info', [
                'tags' => $this->tags('setGlobalExtra'),
                'extra' => [
                    'harness' => 'setGlobalExtra',
                    'seq' => 1,
                ],
            ]);

            return $this->done(
                'Called setExtra(build, tenant) then captureMessage — expect global + per-event extra',
            );
        }

        public function setGlobalUser(): HTTPResponse
        {
            $client = $this->client();
            $client->setUser('harness-user-42');
            $client->captureMessage('Talaria client demo: after setUser', 'info', [
                'tags' => $this->tags('setGlobalUser'),
                'extra' => ['harness' => 'setGlobalUser'],
            ]);

            return $this->done("Called setUser('harness-user-42') then capture — expect that userId");
        }

        public function overrideUser(): HTTPResponse
        {
            $client = $this->client();
            $client->setUser('harness-user-42');
            $client->captureMessage('Talaria client demo: userId override', 'info', [
                'tags' => $this->tags('overrideUser'),
                'extra' => ['harness' => 'overrideUser'],
                'userId' => 'override-user-7',
            ]);

            return $this->done(
                'Global user harness-user-42 set, capture used userId=override-user-7',
            );
        }

        public function clearUser(): HTTPResponse
        {
            $client = $this->client();
            $client->setUser(null);
            $client->captureMessage('Talaria client demo: after clearUser', 'info', [
                'tags' => $this->tags('clearUser'),
                'extra' => ['harness' => 'clearUser'],
            ]);

            return $this->done('Called setUser(null) then capture — expect no userId on event');
        }

        public function enqueueOnly(): HTTPResponse
        {
            $client = $this->client();
            $client->captureMessage('Talaria client demo: enqueue #1', 'info', [
                'tags' => $this->tags('enqueueOnly'),
                'extra' => ['harness' => 'enqueueOnly', 'seq' => 1],
            ]);
            $client->captureMessage('Talaria client demo: enqueue #2', 'warning', [
                'tags' => $this->tags('enqueueOnly'),
                'extra' => ['harness' => 'enqueueOnly', 'seq' => 2],
            ]);

            $size = $client->queueSize();
            $message = "Enqueued 2 events without flush; queueSize={$size}"
                . ' (shutdown / defaultIntegrations may still drain the queue)';

            $request = $this->getRequest();
            $session = $request->getSession();
            if ($session) {
                $session->set('TalariaClientFlash', $message);
                $session->save($request);
            }

            return $this->redirect($this->Link() ?: '/talaria-client');
        }

        public function flushQueue(): HTTPResponse
        {
            $client = $this->client();
            $before = $client->queueSize();
            try {
                $client->flush();
            } catch (\Throwable $e) {
                return $this->done('flush error: ' . $e->getMessage(), flush: false);
            }
            $after = $client->queueSize();

            return $this->done(
                "Flushed queue (before={$before}, after={$after})",
                flush: false,
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

        private function actionLink(string $action): string
        {
            return Controller::join_links($this->Link(), $action);
        }

        private function client(): TalariaClient
        {
            return Injector::inst()->get(TalariaClient::class);
        }

        /**
         * @return array<string, string>
         */
        private function tags(string $action): array
        {
            return [
                'source' => 'talaria_ss_test',
                'entity' => 'TalariaClientPage',
                'operation' => $action,
            ];
        }

        private function done(string $message, bool $flush = true): HTTPResponse
        {
            if ($flush) {
                try {
                    $this->client()->flush();
                } catch (\Throwable $e) {
                    $message .= ' — flush error: ' . $e->getMessage();
                }
            }

            $request = $this->getRequest();
            $session = $request->getSession();
            if ($session) {
                $session->set('TalariaClientFlash', $message);
                $session->save($request);
            }

            return $this->redirect($this->Link() ?: '/talaria-client');
        }
    }
}
