<?php

namespace {

    use App\Demos\Blog\BlogPageControllerTrait;
    use App\Demos\Blog\BlogPost;

    class BlogHolderPageController extends PageController
    {
        use BlogPageControllerTrait;

        protected function init()
        {
            $this->bootBlogFrontend();
            parent::init();
        }

        public function BlogPropsJSON(): string
        {
            $detailPage = BlogDetailPage::get()
                ->filter('ParentID', $this->data()->ID)
                ->first();

            $detailBaseUrl = $detailPage ? rtrim($detailPage->Link(), '/') : rtrim($this->Link(), '/') . '/post';

            $posts = array_map(
                static fn (BlogPost $post): array => $post->toListItem(),
                $this->blogRepository()->all()
            );

            return $this->encodeDataAttribute([
                'view' => 'list',
                'entity' => 'BlogHolderPage',
                'listUrl' => rtrim($this->Link(), '/'),
                'detailBaseUrl' => $detailBaseUrl,
                'posts' => $posts,
            ]);
        }

        public function TalariaConfigJSON(): string
        {
            return $this->talariaConfigAttribute();
        }
    }
}
