<?php

namespace SEOAIC\loaders;

use Exception;
use SEOAIC\interfaces\PostsLoaderInterface;

class PostsGenerationLoader extends PostsBaseLoader implements PostsLoaderInterface
{
    function __construct()
    {
        $this->optionField = 'seoaic_background_post_generation';
        $this->id = 'seoaic-admin-generate-loader';
        $this->bgColor = '#e9b9ff';
        $this->fillColor = '#c63eff';
        $this->title = 'Background posts generation';
        $this->closeAction = 'seoaic_clear_background_option';
        $this->stopAction = 'seoaic_posts_mass_generate_stop';
        // $this->postsOption = $this->getPostsOption();
        $this->isStopButtonDisplayed = true;
        $this->isCheckManuallyButtonDisplayed = true;
        $this->checkManuallyAction = 'seoaic_posts_mass_generate_check_status_manually';
    }

    public static function getPostsOption()
    {
        try {
            $instance = new self();
            $value = get_option($instance->getOptionField(), false);

            return [
                'total' => !empty($value['ideas']) ? $value['ideas'] : [],
                'done'  => !empty($value['posts']) ? $value['posts'] : [],
            ];

        } catch (Exception $e) {
            error_log(' '.print_r($e->getMessage(), true));
        }
    }

    public static function setPostsOption($value)
    {
        try {
            $instance = new self();
            $new_val = [
                'ideas' => $value['total'],
                'posts'  => $value['done'],
            ];
            update_option($instance->getOptionField(), $new_val);

        } catch (Exception $e) {
            error_log(' '.print_r($e->getMessage(), true));
        }
    }
}
