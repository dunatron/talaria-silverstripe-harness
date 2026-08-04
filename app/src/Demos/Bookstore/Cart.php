<?php

declare(strict_types=1);

namespace App\Demos\Bookstore;

use SilverStripe\Control\Session;

/**
 * Session-backed bookstore cart.
 *
 * Session key stores book_id => qty.
 */
final class Cart
{
    private const SESSION_KEY = 'BookstoreCart';

    public function __construct(
        private readonly Session $session,
        private readonly Catalog $catalog,
    ) {
    }

    /**
     * @return array<string, int> book_id => qty
     */
    public function lines(): array
    {
        $raw = $this->session->get(self::SESSION_KEY);
        if (!is_array($raw)) {
            return [];
        }

        $lines = [];
        foreach ($raw as $bookId => $qty) {
            if (!is_string($bookId) || $bookId === '') {
                continue;
            }
            $n = is_numeric($qty) ? (int) $qty : 0;
            if ($n > 0) {
                $lines[$bookId] = $n;
            }
        }

        return $lines;
    }

    public function itemCount(): int
    {
        return array_sum($this->lines());
    }

    public function isEmpty(): bool
    {
        return $this->lines() === [];
    }

    public function add(string $bookId, int $qty = 1): void
    {
        if ($qty < 1) {
            return;
        }
        if ($this->catalog->findById($bookId) === null) {
            throw new BookstoreValidationException('Unknown book: ' . $bookId);
        }

        $lines = $this->lines();
        $lines[$bookId] = ($lines[$bookId] ?? 0) + $qty;
        $this->persist($lines);
    }

    public function update(string $bookId, int $qty): void
    {
        $lines = $this->lines();
        if ($qty < 1) {
            unset($lines[$bookId]);
        } else {
            if ($this->catalog->findById($bookId) === null) {
                throw new BookstoreValidationException('Unknown book: ' . $bookId);
            }
            $lines[$bookId] = $qty;
        }
        $this->persist($lines);
    }

    public function remove(string $bookId): void
    {
        $lines = $this->lines();
        unset($lines[$bookId]);
        $this->persist($lines);
    }

    public function clear(): void
    {
        $this->persist([]);
    }

    public function subtotalCents(): int
    {
        $total = 0;
        foreach ($this->detailedLines() as $line) {
            $total += $line['line_total_cents'];
        }

        return $total;
    }

    /**
     * @return list<array{
     *   book: Book,
     *   qty: int,
     *   line_total_cents: int,
     *   price_formatted: string,
     *   line_total_formatted: string
     * }>
     */
    public function detailedLines(): array
    {
        $out = [];
        foreach ($this->lines() as $bookId => $qty) {
            $book = $this->catalog->findById($bookId);
            if ($book === null) {
                continue;
            }
            $lineTotal = $book->priceCents * $qty;
            $out[] = [
                'book' => $book,
                'qty' => $qty,
                'line_total_cents' => $lineTotal,
                'price_formatted' => $book->priceFormatted(),
                'line_total_formatted' => '$' . number_format($lineTotal / 100, 2),
            ];
        }

        return $out;
    }

    /**
     * Snapshot for debug logging / status JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $items = [];
        foreach ($this->detailedLines() as $line) {
            $items[] = [
                'book_id' => $line['book']->id,
                'sku' => $line['book']->sku,
                'title' => $line['book']->title,
                'qty' => $line['qty'],
                'line_total_cents' => $line['line_total_cents'],
            ];
        }

        return [
            'item_count' => $this->itemCount(),
            'subtotal_cents' => $this->subtotalCents(),
            'items' => $items,
        ];
    }

    /**
     * @param array<string, int> $lines
     */
    private function persist(array $lines): void
    {
        $this->session->set(self::SESSION_KEY, $lines);
    }
}
