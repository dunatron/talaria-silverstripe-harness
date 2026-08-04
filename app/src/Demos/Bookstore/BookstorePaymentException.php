<?php

declare(strict_types=1);

namespace App\Demos\Bookstore;

/**
 * Simulated payment gateway decline for the bookstore harness.
 */
final class BookstorePaymentException extends \RuntimeException
{
}
