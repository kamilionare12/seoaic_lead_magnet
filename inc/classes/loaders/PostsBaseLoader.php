<?php

namespace SEOAIC\loaders;

use Exception;
use SEOAIC\interfaces\PostsLoaderInterface;

abstract class PostsBaseLoader implements PostsLoaderInterface
{
    protected $optionField;
    protected $id;
    protected $title;
    protected $bgColor;
    protected $fillColor;
    protected $postsOption;
    protected $closeAction;
    protected $stopAction;
    public $isStopButtonDisplayed;
    public $isCheckManuallyButtonDisplayed;
    protected $checkManuallyAction;
    public $postsTotal;
    public $postsDone;

    public function __construct()
    {
        $this->optionField = '';
        $this->title = 'seoaic-admin-basic-loader';
        $this->title = '';
        $this->bgColor = '';
        $this->fillColor = '';
        $this->postsOption = [];
        $this->closeAction = '';
        $this->stopAction = '';
        $this->isStopButtonDisplayed = true;
        $this->isCheckManuallyButtonDisplayed = false;
        $this->checkManuallyAction = '';
        $this->postsTotal = [];
        $this->postsDone = [];
    }

    public function getID()
    {
        return $this->id;
    }

    public function getBackgroundColor()
    {
        return $this->bgColor;
    }
    public function getFillColor()
    {
        return $this->fillColor;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getCloseAction()
    {
        return $this->closeAction;
    }

    public function getStopAction()
    {
        return $this->stopAction;
    }

    public function getCheckManualyAction()
    {
        return $this->checkManuallyAction;
    }

    public function getOptionField()
    {
        if (!empty($this->optionField)) {
            return $this->optionField;
        }
        throw new Exception('Empty option field.');
    }

    public abstract static function getPostsOption();

    public abstract static function setPostsOption($value);

    public static function deletePostsOption()
    {
        try {
            $instance = new static();
            delete_option($instance->getOptionField());

        } catch (Exception $e) {
            error_log(' '.print_r($e->getMessage(), true));
        }
    }

    public function addIDs($ids = [])
    {
        if (
            !empty($ids)
            && !is_array($ids)
            && is_numeric($ids)
        ) {
            $ids = [$ids];
        }

        if (!empty($ids)) {
            $option = $this->getPostsOption();
            if (count($option['done']) == count($option['total'])) { // reset
                $option = [
                    'total' => $ids,
                    'done' => [],
                ];
            } else {
                $option['total'] = array_merge($option['total'], $ids);
            }

            update_option($this->getOptionField(), $option);
        }
    }

    public function completeIDs($ids = [])
    {
        if (
            !empty($ids)
            && !is_array($ids)
            && is_numeric($ids)
        ) {
            $ids = [$ids];
        }

        if (!empty($ids)) {
            $option = $this->getPostsOption();
            $option['done'] = !empty($option['done']) ? $option['done'] : [];
            $option['done'] = array_merge($option['done'], $ids);

            update_option($this->getOptionField(), $option);
        }
    }
}
