<?php

declare(strict_types=1);

namespace App\Demos\Blog;

/**
 * Immutable blog post DTO loaded from the local JSON fixture.
 */
final class BlogPost
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $excerpt,
        public readonly string $body,
        public readonly string $author,
        public readonly string $publishedAt,
        public readonly array $tags,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                if (is_string($tag) && $tag !== '') {
                    $tags[] = $tag;
                }
            }
        }

        return new self(
            slug: is_string($data['slug'] ?? null) ? $data['slug'] : '',
            title: is_string($data['title'] ?? null) ? $data['title'] : '',
            excerpt: is_string($data['excerpt'] ?? null) ? $data['excerpt'] : '',
            body: is_string($data['body'] ?? null) ? $data['body'] : '',
            author: is_string($data['author'] ?? null) ? $data['author'] : '',
            publishedAt: is_string($data['publishedAt'] ?? null) ? $data['publishedAt'] : '',
            tags: $tags,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'author' => $this->author,
            'publishedAt' => $this->publishedAt,
            'tags' => $this->tags,
        ];
    }

    /**
     * List card payload (omit full body).
     *
     * @return array<string, mixed>
     */
    public function toListItem(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'author' => $this->author,
            'publishedAt' => $this->publishedAt,
            'tags' => $this->tags,
        ];
    }
}
