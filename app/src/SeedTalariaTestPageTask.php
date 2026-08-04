<?php

namespace {

    use SilverStripe\Dev\BuildTask;

    /**
     * Creates / updates Talaria harness pages.
     */
    class SeedTalariaTestPageTask extends BuildTask
    {
        private static string $segment = 'SeedTalariaTestPageTask';

        protected $title = 'Seed Talaria harness pages';

        protected $description = 'Creates Talaria harness pages including the JSON-backed blog holder/detail pages';

        public function run($request)
        {
            $this->seedHomePage();
            $this->seedLoggerPage();
            $this->seedClientPage();
            $this->seedCheckoutPage();
            $this->seedBookstorePage();
            $this->seedBrowserPage();
            $this->seedBlogPages();
        }

        private function seedHomePage(): void
        {
            $home = \Page::get()->filter([
                'URLSegment' => 'home',
                'ParentID' => 0,
            ])->first();

            if (!$home) {
                $home = \Page::get()->filter('ParentID', 0)->sort('ID', 'ASC')->first();
            }

            if (!$home) {
                $home = HomePage::create();
                $home->URLSegment = 'home';
                $this->message('Creating HomePage…');
            } elseif (!($home instanceof HomePage)) {
                $home = $home->newClassInstance(HomePage::class);
                $this->message('Migrating root page to HomePage…');
            } else {
                $this->message('Updating existing HomePage…');
            }

            $home->Title = 'Talaria harness';
            $home->MenuTitle = 'Home';
            $home->Content = '';
            $home->ShowInMenus = true;
            $home->ShowInSearch = false;
            $home->write();
            if ($home->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $home->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/');
        }

        private function seedLoggerPage(): void
        {
            $page = TalariaTestPage::get()->find('URLSegment', 'talaria-test');
            if (!$page) {
                $page = TalariaTestPage::create();
                $page->URLSegment = 'talaria-test';
                $this->message('Creating TalariaTestPage…');
            } else {
                $this->message('Updating existing TalariaTestPage…');
            }

            $page->Title = 'Talaria logger harness';
            $page->Content = '<p>Use the buttons below to fire different Talaria logging paths against your local SDK symlink.</p>';
            $page->ShowInMenus = true;
            $page->ShowInSearch = false;
            $page->write();
            if ($page->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $page->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/talaria-test');
        }

        private function seedClientPage(): void
        {
            $page = TalariaClientPage::get()->find('URLSegment', 'talaria-client');
            if (!$page) {
                $page = TalariaClientPage::create();
                $page->URLSegment = 'talaria-client';
                $this->message('Creating TalariaClientPage…');
            } else {
                $this->message('Updating existing TalariaClientPage…');
            }

            $page->Title = 'Talaria client harness';
            $page->Content = '<p>Use the buttons below to exercise the <code>TalariaClient</code> public API: severity levels, tags, extra, userId, mechanism, and flush.</p>';
            $page->ShowInMenus = true;
            $page->ShowInSearch = false;
            $page->write();
            if ($page->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $page->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/talaria-client');
        }

        private function seedCheckoutPage(): void
        {
            $page = CheckoutDemoPage::get()->find('URLSegment', 'checkout-demo');
            if (!$page) {
                $page = CheckoutDemoPage::create();
                $page->URLSegment = 'checkout-demo';
                $this->message('Creating CheckoutDemoPage…');
            } else {
                $this->message('Updating existing CheckoutDemoPage…');
            }

            $page->Title = 'Checkout workflow demo';
            $page->Content = '<p>Demonstrates best-practice request setup on the Injector <code>TalariaClient</code>, then a multi-step checkout with milestone events.</p>';
            $page->ShowInMenus = true;
            $page->ShowInSearch = false;
            $page->write();
            if ($page->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $page->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/checkout-demo');
        }

        private function seedBookstorePage(): void
        {
            $page = BookstorePage::get()->find('URLSegment', 'bookstore');
            if (!$page) {
                $page = BookstorePage::create();
                $page->URLSegment = 'bookstore';
                $this->message('Creating BookstorePage…');
            } else {
                $this->message('Updating existing BookstorePage…');
            }

            $page->Title = 'Bookstore (Talaria Logger)';
            $page->MenuTitle = 'Bookstore';
            $page->Content = '<p>Feature-complete catalog, session cart, and checkout instrumented with <code>Talaria\\Logger</code> (child scopes, tags vs extra, minLevel, isLevelEnabled, captureException).</p>';
            $page->ShowInMenus = true;
            $page->ShowInSearch = false;
            $page->write();
            if ($page->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $page->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/bookstore');
        }

        private function seedBrowserPage(): void
        {
            $page = TalariaBrowserPage::get()->find('URLSegment', 'talaria-browser');
            if (!$page) {
                $page = TalariaBrowserPage::create();
                $page->URLSegment = 'talaria-browser';
                $this->message('Creating TalariaBrowserPage…');
            } else {
                $this->message('Updating existing TalariaBrowserPage…');
            }

            $page->Title = 'Talaria browser harness';
            $page->Content = '<p>JS-driven demos against the browser SDK the Silverstripe module already injects (<code>window.Talaria</code>). No separate client load or init.</p>';
            $page->ShowInMenus = true;
            $page->ShowInSearch = false;
            $page->write();
            if ($page->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $page->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/talaria-browser');
        }

        private function seedBlogPages(): void
        {
            $holder = BlogHolderPage::get()->find('URLSegment', 'blog');
            if (!$holder) {
                $holder = BlogHolderPage::create();
                $holder->URLSegment = 'blog';
                $this->message('Creating BlogHolderPage…');
            } else {
                $this->message('Updating existing BlogHolderPage…');
            }

            $holder->Title = 'Blog';
            $holder->MenuTitle = 'Blog';
            $holder->Content = '<p>Posts are loaded from a local JSON fixture and rendered by the TypeScript blog app (npm <code>@newtalaria/browser</code>).</p>';
            $holder->ShowInMenus = true;
            $holder->ShowInSearch = false;
            $holder->write();
            if ($holder->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $holder->publishRecursive();
            }

            $detail = BlogDetailPage::get()->filter([
                'URLSegment' => 'post',
                'ParentID' => $holder->ID,
            ])->first();

            if (!$detail) {
                $detail = BlogDetailPage::create();
                $detail->URLSegment = 'post';
                $detail->ParentID = $holder->ID;
                $this->message('Creating BlogDetailPage…');
            } else {
                $this->message('Updating existing BlogDetailPage…');
            }

            $detail->Title = 'Blog post';
            $detail->MenuTitle = 'Post';
            $detail->Content = '';
            $detail->ShowInMenus = false;
            $detail->ShowInSearch = false;
            $detail->write();
            if ($detail->hasExtension(\SilverStripe\Versioned\Versioned::class)) {
                $detail->publishRecursive();
            }

            $this->message('Done: https://new-talaria-ss-test.ddev.site/blog');
            $this->message('Detail example: https://new-talaria-ss-test.ddev.site/blog/post/welcome-to-talaria');
        }

        private function message(string $text): void
        {
            echo $text . PHP_EOL;
        }
    }
}
