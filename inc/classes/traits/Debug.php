<?php

trait Debug
{
    /**
     * Writes logs with details like class name and caller function. Uses print_r function.
     * @param any $data message or variable
     */
    private function debugLog(...$args)
    {
        $args_string = '';
        foreach ($args as $arg) {
            $args_string .= print_r($arg, true) . ' ';
        }
        $func_name = debug_backtrace()[1]['function'];
        $str = '[' . wp_date('Y-m-d H:i:s') . '] ' . __CLASS__ . ' -> ' . $func_name . '(): ' . $args_string . "\r\n";
        file_put_contents(SEOAIC_LOG . 'mass_posts_edit.txt', $str, FILE_APPEND);
    }
}