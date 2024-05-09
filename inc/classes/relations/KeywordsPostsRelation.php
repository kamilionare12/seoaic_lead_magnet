<?php

namespace SEOAIC\relations;

use SEOAIC\DB\KeywordsPostsTable;
use SEOAIC\DB\WPDB;
use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_IDEAS;
use SEOAIC\SEOAIC_SETTINGS;

class KeywordsPostsRelation
{
    /**
     * Set relations between Keywords and Posts/Ideas
     * @param array[int]|int $keywordsIDs
     * @param array[int]|int $postsIDs
     */
    public static function setRelations($keywordsIDs = [], $postsIDs = [])
    {
        if (
            !empty($keywordsIDs)
            && !empty($postsIDs)
        ) {
            if (
                !is_array($keywordsIDs)
                && is_numeric($keywordsIDs)
            ) {
                $keywordsIDs = [$keywordsIDs];
            }

            if (
                !is_array($postsIDs)
                && is_numeric($postsIDs)
            ) {
                $postsIDs = [$postsIDs];
            }

            $values = [];
            foreach ($keywordsIDs as $keywordID) {
                foreach ($postsIDs as $postID) {
                    $values[] = [$keywordID, $postID];
                }
            }

            (new WPDB())->insertBulk(
                KeywordsPostsTable::TABLE_NAME,
                [
                    'keyword_id',
                    'post_id',
                ],
                $values
            );
        }
    }

    /**
     * @param int $keywordID
     * @return array array with records in [WP_Post] format
     */
    public static function getPostsByKeywordId(int $keywordID): array
    {
        global $wpdb;

        $query = "SELECT p.* FROM {$wpdb->prefix}posts p
        LEFT JOIN " . KeywordsPostsTable::TABLE_NAME . " kp_relation on p.ID = kp_relation.post_id
        WHERE kp_relation.keyword_id = %d;";

        return $wpdb->get_results($wpdb->prepare($query, $keywordID));
    }

    /**
     * @param int $postID
     * @return array array with records in [WP_Post] format
     */
    public static function getKeywordsByPostId(int $postID): array
    {
        global $wpdb;

        $query = "SELECT p.* FROM {$wpdb->prefix}posts p
        LEFT JOIN " . KeywordsPostsTable::TABLE_NAME . " kp_relation on p.ID = kp_relation.keyword_id
        WHERE kp_relation.post_id = %d;";

        return $wpdb->get_results($wpdb->prepare($query, $postID));
    }

    public static function getRelatedPostsIDs(int $postID): array
    {
        $results = self::getRelatedPosts($postID);

        return array_map(function ($row) {
            return $row->ID;
        }, $results);
    }

    /**
     * @return stdClass[] array of objects
     */
    public static function getRelatedPosts(int $postID): array
    {
        global $wpdb;

        $query = "SELECT p.* FROM {$wpdb->prefix}posts p"
        ." LEFT JOIN " . KeywordsPostsTable::TABLE_NAME . " kp_relation1 on p.ID = kp_relation1.post_id"
        ." LEFT JOIN " . KeywordsPostsTable::TABLE_NAME . " kp_relation2 on kp_relation1.keyword_id = kp_relation2.keyword_id"
        ." WHERE kp_relation2.post_id = %d"
        ." AND p.post_type = %s"
        // ." AND p.post_status != %s"
        ." AND p.post_status = %s"
        ." AND p.ID != %d"
        .";";
        $preparedQuery = $wpdb->prepare($query, [
            $postID,
            SEOAIC_SETTINGS::getSEOAICPostType(),
            // SEOAIC_IDEAS::IDEA_STATUS,
            'publish',
            $postID,
        ]);

        return $wpdb->get_results($preparedQuery);
    }

    public static function deleteByKeywordID($ID = null): void
    {
        global $wpdb;

        if (is_numeric($ID)) {
            $wpdb->delete(KeywordsPostsTable::TABLE_NAME,
                [
                    'keyword_id' => $ID,
                ],
                ['%d'],
            );
        }
    }

    public static function deleteByPostID($ID = null): void
    {
        global $wpdb;

        if (is_numeric($ID)) {
            $wpdb->delete(KeywordsPostsTable::TABLE_NAME,
                [
                    'post_id' => $ID,
                ],
                ['%d'],
            );
        }
    }
}
