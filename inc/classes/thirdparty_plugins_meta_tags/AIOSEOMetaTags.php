<?php

namespace SEOAIC\thirdparty_plugins_meta_tags;

use RankMath\Helpers\Arr;
use SEOAIC\interfaces\ThirdpartyPluginsMetaTagsInterface;

class AIOSEOMetaTags extends AbstractMetaTags implements ThirdpartyPluginsMetaTagsInterface
{
    private $custom_posts_table;

    public function __construct()
    {
        global $wpdb;

        $this->custom_posts_table = $wpdb->prefix . 'aioseo_posts';

        $this->pluginID = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
        $this->descriptionField = '_aioseo_description';
        $this->keywordField = '_aioseo_keywords';
    }

    public function getKeyword($postID)
    {
        global $wpdb;

        $value = '';
        $table = $this->custom_posts_table;

        // custom table value
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $preparedQuery = $wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", [$postID]);
            $row = $wpdb->get_row($preparedQuery);

            if (
                !empty($row)
                && !empty($row->keyphrases)
            ) {
                $keyphrases = json_decode($row->keyphrases);
                $value = !empty($keyphrases->focus->keyphrase) ? $keyphrases->focus->keyphrase : '';
            }
        }

        if (empty($value)) { // custom keyword is empty - check regular meta field
            $value = $this->getMetaFieldValue($postID, $this->keywordField);
        }

        return $value;
    }

    public function setKeyword($postID = null, $keyword = '', $origID = null)
    {
        global $wpdb;

        if (
            !empty($postID)
            && is_numeric($postID)
            && !empty($keyword)
        ) {
            // custom table value
            $table = $this->custom_posts_table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $preparedQuery = $wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", [$postID]);
                $row = $wpdb->get_row($preparedQuery, ARRAY_A);

                if (!empty($row)) {
                    $keyphrases = !empty($row['keyphrases']) ? json_decode($row['keyphrases'], true) : [];
                    $value = !empty($keyphrases) && !empty($keyphrases['focus']) && !empty($keyphrases['focus']['keyphrase']) ? $keyphrases['focus']['keyphrase'] : '';

                    if (!empty($value)) { // update field only if there is some value
                        $keyphrases['focus']['keyphrase'] = $keyword;
                        $result = $wpdb->update(
                            $table,
                            ['keyphrases' => json_encode($keyphrases)],
                            ['post_id' => $postID],
                            ['%s'],
                            ['%d']
                        );
                        ob_start();var_dump($result);
                    }

                } else {
                    $preparedQuery = $wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", [$origID]);
                    $origRow = $wpdb->get_row($preparedQuery, ARRAY_A);

                    if (!empty($origRow)) {
                        $keyphrases = json_decode($origRow['keyphrases'], true);
                        $keyphrases['focus']['keyphrase'] = $keyword;

                        unset($origRow['id']);
                        $origRow['post_id'] = $postID;
                        $origRow['keyphrases'] = json_encode($keyphrases);

                        $wpdb->insert($table, $origRow);
                    }
                }
            }
        }
    }

    public function setDescription($postID = null, $description = '', $origID = null)
    {
        global $wpdb;

        if (
            !empty($postID)
            && is_numeric($postID)
            && !empty($description)
        ) {
            // custom table value
            $table = $this->custom_posts_table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $preparedQuery = $wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", [$postID]);
                $row = $wpdb->get_row($preparedQuery, ARRAY_A);

                if (!empty($row)) {
                    $value = !empty($row['description']) ? $row['description'] : '';

                    if (!empty($value)) { // update field only if there is some value
                        $result = $wpdb->update(
                            $table,
                            ['description' => $description],
                            ['post_id' => $postID],
                            ['%s'],
                            ['%d']
                        );
                        ob_start();var_dump($result);
                    }

                } else {
                    $preparedQuery = $wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", [$origID]);
                    $origRow = $wpdb->get_row($preparedQuery, ARRAY_A);
                    if (!empty($origRow)) {
                        unset($origRow['id']);
                        $origRow['post_id'] = $postID;
                        $origRow['description'] = $description;

                        $wpdb->insert($table, $origRow);
                    }
                }
            }
        }
    }
}
