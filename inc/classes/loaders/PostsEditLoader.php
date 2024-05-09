<?php

namespace SEOAIC\loaders;

use Exception;
use SEOAIC\interfaces\PostsLoaderInterface;

class PostsEditLoader extends PostsBaseLoader implements PostsLoaderInterface
{
    function __construct()
    {
        $this->optionField = 'seoaic_background_post_edit';
        $this->id = 'seoaic-admin-edit-loader';
        $this->bgColor = '#b8b6f4';
        $this->fillColor = '#9488fa';
        $this->title = 'Background posts edit';
        $this->closeAction = 'seoaic_clear_edit_background_option';
        $this->stopAction = '';
        $this->isStopButtonDisplayed = false;
    }

    public static function getPostsOption()
    {
        try {
            $instance = new self();
            $value = get_option($instance->getOptionField(), false);

            return [
                'total' => !empty($value['total']) ? $value['total'] : [],
                'done'  => !empty($value['done']) ? $value['done'] : [],
            ];

        } catch (Exception $e) {
            error_log(' '.print_r($e->getMessage(), true));
        }
    }

    public static function setPostsOption($value)
    {
        try {
            $instance = new self();
            update_option($instance->getOptionField(), $value);

        } catch (Exception $e) {
            error_log(' '.print_r($e->getMessage(), true));
        }
    }
}
