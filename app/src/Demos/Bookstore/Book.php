<?php

declare(strict_types=1);

namespace App\Demos\Bookstore;

/**
 * Immutable book DTO loaded from the local JSON fixture.
 */
final class Book
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $author,
        public readonly int $priceCents,
        public readonly string $sku,
        public readonly int $stock,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: is_string($data['id'] ?? null) ? $data['id'] : '',
            title: is_string($data['title'] ?? null) ? $data['title'] : '',
            author: is_string($data['author'] ?? null) ? $data['author'] : '',
            priceCents: is_numeric($data['price_cents'] ?? null) ? (int) $data['price_cents'] : 0,
            sku: is_string($data['sku'] ?? null) ? $data['sku'] : '',
            stock: is_numeric($data['stock'] ?? null) ? (int) $data['stock'] : 0,
        );
    }

    public function priceFormatted(): string
    {
        return '$' . number_format($this->priceCents / 100, 2);
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'price_cents' => $this->priceCents,
            'sku' => $this->sku,
            'stock' => $this->stock,
        ];
    }
}
