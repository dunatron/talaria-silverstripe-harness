<?php

namespace {

    use SilverStripe\CMS\Model\SiteTree;
    use SilverStripe\Control\Director;
    use SilverStripe\Core\Environment;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\View\ArrayData;

    /**
     * @extends PageController<HomePage>
     */
    class HomePageController extends PageController
    {
        public function index($request = null)
        {
            return $this;
        }

        public function ConfigStatus(): ArrayData
        {
            $dsn = (string) (Environment::getEnv('TALARIA_DSN') ?: '');
            $key = (string) (Environment::getEnv('TALARIA_API_KEY') ?: '');
            $env = (string) (Environment::getEnv('TALARIA_ENVIRONMENT') ?: '');
            $release = (string) (Environment::getEnv('TALARIA_RELEASE') ?: '');
            $browserDsn = (string) (Environment::getEnv('TALARIA_BROWSER_DSN') ?: '');

            $keyPreview = $key === ''
                ? '(missing)'
                : (strlen($key) > 12 ? substr($key, 0, 12) . '…' : $key);

            return ArrayData::create([
                'Configured' => $dsn !== '' && str_starts_with($key, 'tal_live_'),
                'Dsn' => $dsn !== '' ? $dsn : '(missing TALARIA_DSN)',
                'BrowserDsn' => $browserDsn !== '' ? $browserDsn : '(optional TALARIA_BROWSER_DSN)',
                'ApiKey' => $keyPreview,
                'Environment' => $env !== '' ? $env : '(module default)',
                'Release' => $release !== '' ? $release : '(none)',
                'IsDev' => Director::isDev(),
            ]);
        }

        public function FeaturedDemo(): ArrayData
        {
            return $this->demoItem(
                segment: 'bookstore',
                title: 'Bookstore',
                badge: 'Recommended',
                summary: 'Canonical server-side instrumentation with Talaria\\Logger.',
                detail: 'A complete catalog, session cart, and checkout flow. Exercises feature-scoped loggers, child scopes, tags versus extra, withMinLevel, isLevelEnabled, captureException, and PSR-3 placeholders. Start here for the production-style pattern you should copy into application code.',
                showcases: 'Talaria\\Logger · Injector TalariaClient · cart & checkout paths · success and failure outcomes',
                filterHint: 'feature=bookstore · entity=BookstorePage',
            );
        }

        /**
         * @return ArrayList<ArrayData>
         */
        public function OtherDemos(): ArrayList
        {
            $list = ArrayList::create();
            $items = [
                $this->demoItem(
                    segment: 'checkout-demo',
                    title: 'Checkout workflow',
                    badge: '',
                    summary: 'Direct TalariaClient multi-step checkout.',
                    detail: 'Contrasts with the Bookstore: the same style of workflow instrumented via TalariaClient captureMessage / captureException rather than Talaria\\Logger. Useful for understanding the lower-level client API.',
                    showcases: 'TalariaClient · request tags / extra / user · happy path & payment failure',
                    filterHint: 'entity=CheckoutDemoPage',
                ),
                $this->demoItem(
                    segment: 'talaria-test',
                    title: 'Logger harness',
                    badge: '',
                    summary: 'Button lab for Monolog and TalariaClient paths.',
                    detail: 'Fires individual logging actions through Psr\\Log\\LoggerInterface (Injector Monolog handler) and direct client captures. Use this to verify ingest, levels, and exception stacks quickly.',
                    showcases: 'Monolog / LoggerInterface · captureMessage · captureException · batch',
                    filterHint: 'entity=TalariaTestPage',
                ),
                $this->demoItem(
                    segment: 'talaria-client',
                    title: 'Client API',
                    badge: '',
                    summary: 'Full TalariaClient public surface.',
                    detail: 'Exercises severity levels, setTags / setExtra / setUser, per-event context, custom titles, mechanism metadata, and queue flush behaviour.',
                    showcases: 'TalariaClient API completeness · queue & flush',
                    filterHint: 'entity=TalariaClientPage',
                ),
                $this->demoItem(
                    segment: 'talaria-browser',
                    title: 'Browser SDK',
                    badge: '',
                    summary: 'CDN-injected @newtalaria/browser on a thin PHP page.',
                    detail: 'Proves the Silverstripe module’s frontend inject (window.Talaria) plus automatic capture of plain page script failures.',
                    showcases: 'CDN inject · window.Talaria · auto-capture throws',
                    filterHint: 'entity=TalariaBrowserPage',
                ),
                $this->demoItem(
                    segment: 'blog',
                    title: 'Blog',
                    badge: '',
                    summary: 'Vite TypeScript app owning @newtalaria/browser.',
                    detail: 'JSON-backed posts rendered client-side. Demonstrates npm browser SDK integration when the frontend owns init (CDN inject disabled on these pages).',
                    showcases: 'Vite · @newtalaria/browser · Talaria.logger / withTags',
                    filterHint: 'surface=blog · feature=blog',
                ),
            ];

            foreach ($items as $item) {
                $list->push($item);
            }

            return $list;
        }

        /**
         * @return ArrayList<ArrayData>
         */
        public function InstrumentationApproaches(): ArrayList
        {
            $list = ArrayList::create();
            $rows = [
                [
                    'Title' => 'Monolog / LoggerInterface',
                    'Body' => 'Stay on Silverstripe’s default Injector logger. The module attaches a Talaria handler; application code type-hints Psr\\Log\\LoggerInterface with no Talaria imports. Best for CMS and module noise.',
                    'Example' => 'Logger harness',
                ],
                [
                    'Title' => 'Talaria\\Logger',
                    'Body' => 'Wrap the shared Injector TalariaClient for product instrumentation: scoped tags, child loggers, minLevel, isLevelEnabled, and captureException—the same model as the browser SDK.',
                    'Example' => 'Bookstore (recommended)',
                ],
                [
                    'Title' => 'Browser SDK',
                    'Body' => 'Load @newtalaria/browser via the module CDN inject, or own init from a Vite bundle. Use for frontend errors, messages, and session context alongside PHP events.',
                    'Example' => 'Browser harness · Blog',
                ],
            ];

            foreach ($rows as $row) {
                $list->push(ArrayData::create($row));
            }

            return $list;
        }

        private function demoItem(
            string $segment,
            string $title,
            string $badge,
            string $summary,
            string $detail,
            string $showcases,
            string $filterHint,
        ): ArrayData {
            $page = SiteTree::get()->filter('URLSegment', $segment)->first();
            $link = $page ? $page->Link() : '/' . $segment;
            $menuTitle = $page
                ? (string) ($page->MenuTitle ?: $page->Title ?: $title)
                : $title;

            return ArrayData::create([
                'Title' => $menuTitle,
                'Badge' => $badge,
                'Summary' => $summary,
                'Detail' => $detail,
                'Showcases' => $showcases,
                'FilterHint' => $filterHint,
                'Link' => $link,
                'Segment' => $segment,
            ]);
        }
    }
}
