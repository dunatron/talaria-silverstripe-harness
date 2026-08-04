<?php

namespace {

    /**
     * Feature-complete bookstore demo showcasing Talaria\Logger.
     */
    class BookstorePage extends Page
    {
        private static string $table_name = 'BookstorePage';

        private static string $description = 'Bookstore catalog, cart, and checkout instrumented with Talaria\\Logger';

        private static string $icon_class = 'font-icon-book';
    }
}
