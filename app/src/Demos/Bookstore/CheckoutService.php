<?php

declare(strict_types=1);

namespace App\Demos\Bookstore;

use SilverStripe\Security\Security;
use Talaria\Logger;
use Talaria\TalariaClient;

/**
 * Bookstore checkout workflow instrumented with Talaria\Logger.
 *
 * Demonstrates feature-scoped loggers, child scopes, tags vs extra,
 * isLevelEnabled, withMinLevel, captureException, and PSR-3 placeholders.
 */
final class CheckoutService
{
    public function __construct(
        private readonly TalariaClient $client,
        private readonly Catalog $catalog,
        private readonly Cart $cart,
    ) {
    }

    public function baseLogger(): Logger
    {
        return new Logger($this->client, [
            'tags' => [
                'source' => 'talaria_ss_test',
                'entity' => 'BookstorePage',
                'feature' => 'bookstore',
            ],
        ]);
    }

    /**
     * Logger for cart mutations (add/update/remove).
     */
    public function cartLogger(): Logger
    {
        return $this->baseLogger()->child([
            'tags' => ['component' => 'cart', 'surface' => 'cart'],
        ]);
    }

    /**
     * @return array{order_id: string, message: string}
     */
    public function placeOrder(): array
    {
        $orderId = $this->newOrderId();
        $logger = $this->orderLogger($orderId);
        $this->logUser($logger);

        $this->validateCart($logger, $orderId);
        $this->reserveStock($logger, $orderId, forceFail: false);
        $this->chargePayment($logger, $orderId, forceFail: false);
        $this->confirmOrder($logger, $orderId);

        $this->cart->clear();

        return [
            'order_id' => $orderId,
            'message' => "Order {$orderId} confirmed: validate → reserve → charge → confirm",
        ];
    }

    /**
     * @return array{order_id: string, message: string}
     */
    public function failPayment(): array
    {
        $orderId = $this->newOrderId();
        $logger = $this->orderLogger($orderId);
        $this->logUser($logger);

        $this->validateCart($logger, $orderId);
        $this->reserveStock($logger, $orderId, forceFail: false);

        $payments = $logger->child([
            'tags' => ['component' => 'payments', 'operation' => 'charge'],
        ]);

        try {
            throw new BookstorePaymentException('Payment gateway declined card for order ' . $orderId);
        } catch (BookstorePaymentException $e) {
            $payments->captureException($e, [
                'title' => 'Bookstore payment failed',
                'tags' => ['step' => 'charge_payment'],
                'extra' => [
                    'order_id' => $orderId,
                    'amount_cents' => $this->cart->subtotalCents(),
                    'payment_method' => 'card_demo',
                ],
                'mechanism' => [
                    'type' => 'generic',
                    'handled' => true,
                    'synthetic' => false,
                ],
            ]);
        }

        $logger->warn('Checkout aborted after payment failure', [
            'tags' => ['step' => 'checkout_aborted', 'operation' => 'abort'],
            'order_id' => $orderId,
        ]);

        return [
            'order_id' => $orderId,
            'message' => "Payment failure for {$orderId}: validate → reserve → captureException → abort (cart kept)",
        ];
    }

    /**
     * @return array{order_id: string, message: string}
     */
    public function failStock(): array
    {
        $orderId = $this->newOrderId();
        $logger = $this->orderLogger($orderId);
        $this->logUser($logger);

        $this->validateCart($logger, $orderId);

        // Noisy inventory child: floor raised so debug chatter is dropped.
        $inventoryNoise = $logger->child([
            'tags' => ['component' => 'inventory'],
            'minLevel' => 'error',
        ]);
        $inventoryNoise->debug('SKU probe chatter (should not enqueue — withMinLevel error)');
        $inventoryNoise->info('Warehouse ping (should not enqueue — withMinLevel error)');

        try {
            $this->reserveStock($logger, $orderId, forceFail: true);
        } catch (BookstoreStockException $e) {
            $logger->child([
                'tags' => ['component' => 'inventory', 'operation' => 'reserve'],
            ])->captureException($e, [
                'title' => 'Bookstore stock reservation failed',
                'tags' => ['step' => 'reserve_stock'],
                'extra' => [
                    'order_id' => $orderId,
                    'cart' => $this->cart->toArray(),
                ],
                'mechanism' => [
                    'type' => 'generic',
                    'handled' => true,
                    'synthetic' => false,
                ],
            ]);

            $logger->error('Checkout aborted after stock failure', [
                'tags' => ['step' => 'checkout_aborted', 'operation' => 'abort'],
                'order_id' => $orderId,
            ]);

            return [
                'order_id' => $orderId,
                'message' => "Stock failure for {$orderId}: validate → withMinLevel noise dropped → captureException → abort",
            ];
        }

        return [
            'order_id' => $orderId,
            'message' => "Unexpected stock path for {$orderId}",
        ];
    }

    /**
     * Clears the cart, then attempts checkout validation (always exercises the empty path).
     *
     * @return array{order_id: string|null, message: string}
     */
    public function failEmpty(): array
    {
        $this->cart->clear();

        $logger = $this->baseLogger()->child([
            'tags' => ['operation' => 'checkout', 'step' => 'validate_cart'],
        ]);
        $this->logUser($logger);

        try {
            $this->validateCart($logger, null);
        } catch (BookstoreValidationException $e) {
            $logger->warn('Cannot checkout empty cart', [
                'tags' => ['step' => 'validate_cart'],
                'item_count' => 0,
            ]);
            $logger->captureException($e, [
                'title' => 'Bookstore empty cart',
                'tags' => ['step' => 'validate_cart'],
                'mechanism' => [
                    'type' => 'generic',
                    'handled' => true,
                    'synthetic' => false,
                ],
            ]);

            return [
                'order_id' => null,
                'message' => 'Empty cart blocked at validate (warn + captureException; no charge)',
            ];
        }

        return [
            'order_id' => null,
            'message' => 'Unexpected: empty cart validation passed',
        ];
    }

    public function logBrowse(Logger $logger): void
    {
        $logger->info('Browsing bookstore catalog', [
            'tags' => ['operation' => 'browse', 'surface' => 'catalog'],
            'book_count' => count($this->catalog->all()),
        ]);

        if ($logger->isLevelEnabled('debug')) {
            // Expensive context only when debug is enabled (YAML minLevel may be higher).
            $logger->debug('Cart snapshot for browse', [
                'tags' => ['operation' => 'browse'],
                'extra' => ['cart' => $this->cart->toArray()],
            ]);
        }
    }

    private function orderLogger(string $orderId): Logger
    {
        return $this->baseLogger()->child([
            'tags' => [
                'operation' => 'checkout',
            ],
        ]);
    }

    private function logUser(Logger $logger): void
    {
        $member = Security::getCurrentUser();
        $logger->info('Checkout session started', [
            'tags' => ['step' => 'session'],
            'userId' => $member ? (string) $member->ID : 'guest-harness',
            'item_count' => $this->cart->itemCount(),
            'subtotal_cents' => $this->cart->subtotalCents(),
        ]);
    }

    private function validateCart(Logger $logger, ?string $orderId): void
    {
        $step = $logger->child([
            'tags' => ['component' => 'cart', 'step' => 'validate_cart', 'operation' => 'validate'],
        ]);

        if ($this->cart->isEmpty()) {
            throw new BookstoreValidationException('Cart is empty');
        }

        $step->info('Cart validated for order {order_id}', [
            'order_id' => $orderId ?? '(none)',
            'item_count' => $this->cart->itemCount(),
            'subtotal_cents' => $this->cart->subtotalCents(),
        ]);
    }

    private function reserveStock(Logger $logger, string $orderId, bool $forceFail): void
    {
        $inventory = $logger->child([
            'tags' => ['component' => 'inventory', 'step' => 'reserve_stock', 'operation' => 'reserve'],
        ]);

        foreach ($this->cart->detailedLines() as $line) {
            $book = $line['book'];
            $qty = $line['qty'];

            if ($forceFail || !$book->inStock() || $qty > $book->stock) {
                throw new BookstoreStockException(
                    "Insufficient stock for {$book->sku}: requested {$qty}, available {$book->stock}",
                );
            }

            $inventory->info('Stock reserved for {sku}', [
                'sku' => $book->sku,
                'qty' => $qty,
                'order_id' => $orderId,
                // High-cardinality diagnostics belong in extra, not tags.
                'extra' => [
                    'book_id' => $book->id,
                    'title' => $book->title,
                ],
            ]);
        }
    }

    private function chargePayment(Logger $logger, string $orderId, bool $forceFail): void
    {
        $payments = $logger->child([
            'tags' => ['component' => 'payments', 'step' => 'charge_payment', 'operation' => 'charge'],
        ]);

        if ($forceFail) {
            throw new BookstorePaymentException('Forced payment failure');
        }

        $amount = $this->cart->subtotalCents();
        $payments->info('Payment charged for order {order_id}', [
            'order_id' => $orderId,
            'amount_cents' => $amount,
            'payment_method' => 'card_demo',
        ]);
    }

    private function confirmOrder(Logger $logger, string $orderId): void
    {
        $logger->child([
            'tags' => ['step' => 'confirm_order', 'operation' => 'confirm'],
        ])->info('Order {order_id} confirmed', [
            'order_id' => $orderId,
            'item_count' => $this->cart->itemCount(),
            'subtotal_cents' => $this->cart->subtotalCents(),
        ]);
    }

    private function newOrderId(): string
    {
        return 'order-' . bin2hex(random_bytes(4));
    }
}
