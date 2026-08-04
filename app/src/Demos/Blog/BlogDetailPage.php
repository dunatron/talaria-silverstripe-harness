<?php

namespace {

    /**
     * Blog detail viewer page — slug resolved from the URL against the JSON repository.
     */
    class BlogDetailPage extends Page
    {
        private static string $table_name = 'BlogDetailPage';

        private static string $description = 'Blog detail viewer — loads one post by slug from JSON';

        private static string $icon_class = 'font-icon-page-multiple';

        private static bool $can_be_root = false;

        private static array $allowed_children = [];
    }
}
