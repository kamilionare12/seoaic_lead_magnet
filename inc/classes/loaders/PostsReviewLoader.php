<?php

namespace SEOAIC\loaders;

use Exception;
use SEOAIC\interfaces\PostsLoaderInterface;

class PostsReviewLoader extends PostsBaseLoader implements PostsLoaderInterface
{
    function __construct()
    {
        $this->optionField = 'seoaic_background_post_review';
        $this->id = 'seoaic-admin-review-loader';
        $this->bgColor = '#7b99ff';
        $this->fillColor = '#587cf6';
        $this->title = 'Background posts review';
        $this->closeAction = 'seoaic_clear_review_background_option';
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
