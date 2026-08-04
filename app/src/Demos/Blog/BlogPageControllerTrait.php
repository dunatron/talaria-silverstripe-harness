<?php

declare(strict_types=1);

namespace App\Demos\Blog;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;
use Talaria\SilverStripe\Config as TalariaConfig;

/**
 * Shared blog controller helpers: disable CDN SDK inject, load Vite blog app, encode JSON attrs.
 */
trait BlogPageControllerTrait
{
    protected function bootBlogFrontend(): void
    {
        // Blog TypeScript owns @newtalaria/browser — skip FrontendExtension CDN inject.
        Config::modify()->set(TalariaConfig::class, 'enableBrowserFrontend', false);

        // Exposed via composer vendor-plugin (themes/talaria → public/_resources/themes/talaria).
        $scriptUrl = Director::absoluteURL('_resources/themes/talaria/dist/js/blog.js');
        Requirements::insertHeadTags(
            '<script type="module" src="' . htmlspecialchars($scriptUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"></script>',
            'talaria-blog-app'
        );
    }

    /**
     * Attribute-safe JSON for data-* props.
     * HTML-escapes quotes for attribute context. Use `$BlogPropsJSON.RAW` in templates
     * so Silverstripe does not escape a second time. `element.getAttribute()` returns decoded JSON.
     *
     * @param array<string, mixed>|null $data
     */
    protected function encodeDataAttribute(?array $data): string
    {
        if ($data === null) {
            return 'null';
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{}';
        }

        return htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function talariaConfigAttribute(): string
    {
        $options = TalariaConfig::toBrowserOptions('silverstripe-frontend', [
            'source' => 'talaria_ss_test',
            'surface' => 'blog',
        ]);

        return $this->encodeDataAttribute($options);
    }

    protected function blogRepository(): BlogPostRepository
    {
        return \SilverStripe\Core\Injector\Injector::inst()->get(BlogPostRepository::class);
    }
}
