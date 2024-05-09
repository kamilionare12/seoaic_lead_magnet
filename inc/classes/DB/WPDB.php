<?php

namespace SEOAIC\DB;

class WPDB
{
    protected function createIfNotExistsTable($tableName = '', $columns = '')
    {
        if (
            empty($tableName)
            || empty($columns)
        ) {
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $tableName ($columns);";

        return maybe_create_table($tableName, $sql);
    }

    protected function truncateTable($tableName = '')
    {
        global $wpdb;

        if (empty($tableName)) {
            return false;
        }

        $preparedQuery = $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($tableName));
        $tableExists = $wpdb->get_var($preparedQuery) === $tableName;

        if ($tableExists) {
            $preparedQueryTruncate = $wpdb->prepare("TRUNCATE TABLE %s", $tableName);
            $wpdb->query($preparedQueryTruncate);

            return true;
        }

        return false;
    }

    protected function dropTable($tableName = '')
    {
        global $wpdb;

        if (empty($tableName)) {
            return false;
        }

        $preparedQuery = $wpdb->query("DROP TABLE IF EXISTS %s", $tableName);
        $wpdb->query($preparedQuery);

        return true;
    }

    public function insert($tableName, $data = [])
    {
        global $wpdb;

        if (
            empty($tableName)
            || empty($data)
        ) {
            return false;
        }

        $format = [];
        foreach ($data as $field => $value) {
            if (is_numeric($value)) {
                $format[] = '%d';
            } else {
                $format[] = '%s';
            }
        }

        $wpdb->insert($tableName, $data, $format);
    }

    public function insertBulk($tableName, $fields = [], $data = [])
    {
        global $wpdb;

        if (
            empty($tableName)
            || empty($data)
        ) {
            return false;
        }

        $escapedFields = array_map(function ($field) {
            return esc_sql($field);
        }, $fields);
        $query = "INSERT INTO " . esc_sql($tableName) . " (" . implode(', ', $escapedFields) . ") VALUES ";
        $values = [];
        $placeholders = [];

        foreach ($data as $row) {
            $placeholderStr = "(";
            $formatArr = [];

            foreach ($row as $value) {
                if (is_numeric($value)) {
                    $format = '%d';
                } else {
                    $format = '%s';
                }

                $formatArr[] = $format;
                $values[] = $value;
            }

            $placeholderStr .= implode(',', $formatArr) . ")";
            $placeholders[] = $placeholderStr;
        }

        $query .= implode(', ', $placeholders);
        $wpdb->query($wpdb->prepare($query, $values));
    }
}
