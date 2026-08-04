<?php

namespace {

    /**
     * Holder page for the JSON-backed blog list (rendered by TypeScript).
     */
    class BlogHolderPage extends Page
    {
        private static string $table_name = 'BlogHolderPage';

        private static string $description = 'Blog list holder — posts come from a local JSON fixture';

        private static string $icon_class = 'font-icon-news';

        private static array $allowed_children = [
            BlogDetailPage::class,
        ];
    }
}
