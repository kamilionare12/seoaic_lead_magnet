<?php

namespace SEOAIC\DB;

class KeywordsPostsTable extends WPDB
{
    public const TABLE_NAME = 'seoaic_keywords_posts';

    public static function createIfNotExists()
    {
        $columns = "
            id bigint(20) unsigned NOT NULL auto_increment,
            keyword_id bigint(20) unsigned NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (id),
            KEY keyword_id (keyword_id),
            KEY post_id (post_id)";

        return (new self())->createIfNotExistsTable(self::TABLE_NAME, $columns);
    }

    public static function truncate()
    {
        (new self())->truncateTable(self::TABLE_NAME);
    }

    public static function drop()
    {
        (new self())->dropTable(self::TABLE_NAME);
    }
}
