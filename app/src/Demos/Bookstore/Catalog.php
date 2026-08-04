<?php

declare(strict_types=1);

namespace App\Demos\Bookstore;

use RuntimeException;

/**
 * Loads books from a local JSON fixture.
 */
final class Catalog
{
    /** @var list<Book>|null */
    private ?array $books = null;

    public function __construct(
        private readonly string $fixturePath = '',
    ) {
    }

    /**
     * @return list<Book>
     */
    public function all(): array
    {
        return $this->load();
    }

    public function findById(string $id): ?Book
    {
        foreach ($this->load() as $book) {
            if ($book->id === $id) {
                return $book;
            }
        }

        return null;
    }

    public function findBySku(string $sku): ?Book
    {
        foreach ($this->load() as $book) {
            if ($book->sku === $sku) {
                return $book;
            }
        }

        return null;
    }

    /**
     * @return list<Book>
     */
    private function load(): array
    {
        if ($this->books !== null) {
            return $this->books;
        }

        $path = $this->fixturePath !== ''
            ? $this->fixturePath
            : dirname(__DIR__, 3) . '/data/books.json';

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Bookstore fixture not found or unreadable: ' . $path);
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Failed to read bookstore fixture: ' . $path);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Bookstore fixture is not valid JSON: ' . $path);
        }

        $items = $decoded['books'] ?? [];
        if (!is_array($items)) {
            throw new RuntimeException('Bookstore fixture missing books array: ' . $path);
        }

        $books = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $book = Book::fromArray($item);
            if ($book->id === '') {
                continue;
            }
            $books[] = $book;
        }

        $this->books = $books;

        return $this->books;
    }
}
