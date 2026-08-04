<?php

namespace {

    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\CMS\Model\SiteTree;
    use SilverStripe\Control\Director;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\View\ArrayData;
    use SilverStripe\View\Requirements;

    /**
     * @template T of Page
     * @extends ContentController<T>
     */
    class PageController extends ContentController
    {
        private static $allowed_actions = [];

        protected function init()
        {
            parent::init();
            Requirements::javascript('_resources/themes/talaria/dist/js/nav.js');
        }

        /**
         * Command-palette style navigation groups for the site header.
         *
         * @return ArrayList<ArrayData>
         */
        public function NavigationGroups(): ArrayList
        {
            $groups = ArrayList::create();

            $groups->push($this->navGroup('Quick links', [
                $this->navItem('Home', '/', 'home', 'Harness home'),
                $this->navItem('CMS admin', '/admin', 'admin', 'Silverstripe admin'),
            ]));

            $groups->push($this->navGroup('Product demos', [
                $this->navItemFromSegment(
                    'bookstore',
                    'Bookstore',
                    'Talaria Logger — catalog, cart, checkout',
                    'bookstore',
                ),
                $this->navItemFromSegment(
                    'checkout-demo',
                    'Checkout workflow',
                    'TalariaClient multi-step checkout',
                    'checkout',
                ),
            ]));

            $groups->push($this->navGroup('SDK harnesses', [
                $this->navItemFromSegment(
                    'talaria-test',
                    'Logger harness',
                    'Monolog + TalariaClient buttons',
                    'logger',
                ),
                $this->navItemFromSegment(
                    'talaria-client',
                    'Client API',
                    'Full TalariaClient surface',
                    'client',
                ),
                $this->navItemFromSegment(
                    'talaria-browser',
                    'Browser SDK',
                    'CDN inject + window.Talaria',
                    'browser',
                ),
            ]));

            $groups->push($this->navGroup('Integrations', [
                $this->navItemFromSegment(
                    'blog',
                    'Blog',
                    'Vite + @newtalaria/browser',
                    'blog',
                ),
            ]));

            return $groups;
        }

        /**
         * @param list<ArrayData> $items
         */
        private function navGroup(string $title, array $items): ArrayData
        {
            $list = ArrayList::create();
            foreach ($items as $item) {
                if ($item !== null) {
                    $list->push($item);
                }
            }

            return ArrayData::create([
                'Title' => $title,
                'Items' => $list,
            ]);
        }

        private function navItemFromSegment(
            string $segment,
            string $fallbackTitle,
            string $subtitle,
            string $icon,
        ): ?ArrayData {
            $page = SiteTree::get()->filter('URLSegment', $segment)->first();
            if ($page) {
                return $this->navItem(
                    (string) ($page->MenuTitle ?: $page->Title ?: $fallbackTitle),
                    $page->Link(),
                    $icon,
                    $subtitle,
                    $page->isCurrent() || $page->isSection(),
                );
            }

            return $this->navItem($fallbackTitle, '/' . $segment, $icon, $subtitle);
        }

        private function navItem(
            string $title,
            string $href,
            string $icon,
            string $subtitle = '',
            bool $current = false,
        ): ArrayData {
            $absolute = Director::absoluteURL($href);

            return ArrayData::create([
                'Title' => $title,
                'Subtitle' => $subtitle,
                'Link' => $href,
                'AbsoluteLink' => $absolute,
                'Icon' => $icon,
                'IsCurrent' => $current,
                'SearchText' => strtolower(trim($title . ' ' . $subtitle . ' ' . $href)),
            ]);
        }
    }
}
