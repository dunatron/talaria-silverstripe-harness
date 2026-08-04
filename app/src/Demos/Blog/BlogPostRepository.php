<?php

declare(strict_types=1);

namespace App\Demos\Blog;

use RuntimeException;

/**
 * Loads blog posts from a local JSON fixture.
 */
final class BlogPostRepository
{
    private ?array $posts = null;

    public function __construct(
        private readonly string $fixturePath = '',
    ) {
    }

    /**
     * @return list<BlogPost>
     */
    public function all(): array
    {
        return $this->load();
    }

    public function findBySlug(string $slug): ?BlogPost
    {
        foreach ($this->load() as $post) {
            if ($post->slug === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * @return list<BlogPost>
     */
    private function load(): array
    {
        if ($this->posts !== null) {
            return $this->posts;
        }

        $path = $this->fixturePath !== ''
            ? $this->fixturePath
            : dirname(__DIR__, 3) . '/data/blog-posts.json';

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Blog fixture not found or unreadable: ' . $path);
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Failed to read blog fixture: ' . $path);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Blog fixture is not valid JSON: ' . $path);
        }

        $items = $decoded['posts'] ?? [];
        if (!is_array($items)) {
            throw new RuntimeException('Blog fixture missing posts array: ' . $path);
        }

        $posts = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $post = BlogPost::fromArray($item);
            if ($post->slug === '') {
                continue;
            }
            $posts[] = $post;
        }

        // Newest first
        usort(
            $posts,
            static fn (BlogPost $a, BlogPost $b): int => strcmp($b->publishedAt, $a->publishedAt)
        );

        $this->posts = $posts;

        return $this->posts;
    }
}
