<?php

namespace {

    use App\Demos\Blog\BlogPageControllerTrait;
    use SilverStripe\Control\HTTPRequest;

    class BlogDetailPageController extends PageController
    {
        use BlogPageControllerTrait;

        private static array $url_handlers = [
            '$!Slug' => 'index',
        ];

        private static array $allowed_actions = [
            'index',
        ];

        private ?string $requestedSlug = null;

        protected function init()
        {
            $this->bootBlogFrontend();
            parent::init();
        }

        public function index(HTTPRequest $request)
        {
            $slug = (string) $request->param('Slug');
            if ($slug === '') {
                $slug = (string) $request->param('Action');
            }
            $slug = $this->normaliseSlug($slug);

            // When visiting /blog/post with no slug, Action may be the page's own action name.
            if ($slug === null || $slug === 'index') {
                $this->requestedSlug = null;
            } else {
                $this->requestedSlug = $slug;
            }

            return $this;
        }

        public function BlogPropsJSON(): string
        {
            $slug = $this->requestedSlug;
            if ($slug === null) {
                // Fallback: parse remaining URL after this page's link
                $slug = $this->slugFromRequest();
            }

            $listPage = $this->Parent();
            $listUrl = $listPage ? rtrim($listPage->Link(), '/') : '/blog';

            $post = $slug !== null && $slug !== ''
                ? $this->blogRepository()->findBySlug($slug)
                : null;

            return $this->encodeDataAttribute([
                'view' => 'detail',
                'entity' => 'BlogDetailPage',
                'listUrl' => $listUrl,
                'detailBaseUrl' => rtrim($this->Link(), '/'),
                'slug' => $slug,
                'post' => $post?->toArray(),
                'found' => $post !== null,
            ]);
        }

        public function TalariaConfigJSON(): string
        {
            return $this->talariaConfigAttribute();
        }

        private function slugFromRequest(): ?string
        {
            $request = $this->getRequest();
            $url = trim($request->getURL(false), '/');
            $base = trim((string) parse_url(rtrim($this->Link(), '/'), PHP_URL_PATH), '/');
            if ($base !== '' && str_starts_with($url, $base . '/')) {
                $rest = substr($url, strlen($base) + 1);
                $segment = explode('/', $rest)[0] ?? '';
                return $this->normaliseSlug($segment);
            }

            return null;
        }

        private function normaliseSlug(string $slug): ?string
        {
            $slug = trim(rawurldecode($slug));
            // Guard against query strings accidentally glued onto the segment.
            if (str_contains($slug, '?')) {
                $slug = explode('?', $slug, 2)[0];
            }
            if (str_contains($slug, '#')) {
                $slug = explode('#', $slug, 2)[0];
            }
            $slug = trim($slug, '/');

            return $slug === '' ? null : $slug;
        }
    }
}
