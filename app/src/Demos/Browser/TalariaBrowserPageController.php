<?php

namespace {

    use SilverStripe\Control\HTTPResponse;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Environment;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\View\ArrayData;
    use SilverStripe\View\Requirements;
    use Talaria\TalariaClient;

    /**
     * Thin shell for the browser SDK harness. Demo UI lives in theme JS;
     * Talaria browser client is injected by FrontendExtension / RequirementsHelper.
     *
     * @extends PageController<TalariaBrowserPage>
     */
    class TalariaBrowserPageController extends PageController
    {
        private static $allowed_actions = [
            'index',
            'status',
            'serverError',
        ];

        protected function init()
        {
            parent::init();
            Requirements::themedJavascript('talaria-browser-harness');
            Requirements::themedJavascript('talaria-browser-throws');
        }

        public function index($request = null)
        {
            return $this;
        }

        public function ConfigStatus(): ArrayData
        {
            $dsn = (string) (Environment::getEnv('TALARIA_DSN') ?: '');
            $browserDsn = (string) (Environment::getEnv('TALARIA_BROWSER_DSN') ?: '');
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
                'BrowserDsn' => $browserDsn !== ''
                    ? $browserDsn
                    : '(unset — browser falls back to TALARIA_DSN)',
                'ApiKey' => $keyPreview,
                'Environment' => $env !== '' ? $env : '(module default)',
                'Release' => $release !== '' ? $release : '(none)',
                'IsDev' => Director::isDev(),
                'QueueSize' => $queueSize,
            ]);
        }

        public function status(): HTTPResponse
        {
            $status = $this->ConfigStatus()->toMap();
            $body = json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $response = HTTPResponse::create($body ?: '{}', 200);
            $response->addHeader('Content-Type', 'application/json');

            return $response;
        }

        /**
         * Same-origin 500 for browser failed-request promotion demos.
         */
        public function serverError(): HTTPResponse
        {
            $body = json_encode([
                'ok' => false,
                'error' => 'Talaria browser harness intentional server error',
                'entity' => 'TalariaBrowserPage',
            ], JSON_UNESCAPED_SLASHES);
            $response = HTTPResponse::create($body ?: '{"ok":false}', 500);
            $response->addHeader('Content-Type', 'application/json');

            return $response;
        }

        private function client(): TalariaClient
        {
            return Injector::inst()->get(TalariaClient::class);
        }
    }
}
