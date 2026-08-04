<?php

namespace {

    /**
     * Harness landing page — intro, setup, and demo catalogue.
     */
    class HomePage extends Page
    {
        private static string $table_name = 'HomePage';

        private static string $description = 'Talaria Silverstripe SDK harness home';

        private static string $icon_class = 'font-icon-home';

        private static bool $can_be_root = true;
    }
}
