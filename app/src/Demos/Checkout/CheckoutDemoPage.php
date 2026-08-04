<?php

namespace {

    /**
     * Local harness page demonstrating request-scoped TalariaClient checkout instrumentation.
     */
    class CheckoutDemoPage extends Page
    {
        private static string $table_name = 'CheckoutDemoPage';

        private static string $description = 'Best-practice checkout workflow with TalariaClient tags and steps';

        private static string $icon_class = 'font-icon-cart';
    }
}
