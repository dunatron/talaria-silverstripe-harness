<?php

namespace {

    use SilverStripe\CMS\Model\SiteTree;

    /**
     * Local harness page for exercising Talaria logging / exception ingest.
     */
    class TalariaTestPage extends Page
    {
        private static string $table_name = 'TalariaTestPage';

        private static string $description = 'Buttons that fire Talaria log / exception test cases';

        private static string $icon_class = 'font-icon-check-mark-circle';
    }
}
