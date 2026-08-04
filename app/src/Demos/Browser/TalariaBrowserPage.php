<?php

namespace {

    /**
     * Local harness page for exercising the browser SDK shipped by the Silverstripe module.
     */
    class TalariaBrowserPage extends Page
    {
        private static string $table_name = 'TalariaBrowserPage';

        private static string $description = 'JS buttons that exercise the shipped @newtalaria/browser client';

        private static string $icon_class = 'font-icon-monitor';
    }
}
