<?php

namespace SEOAIC\interfaces;

interface PostsLoaderInterface
{
    public function getID();
    public function getBackgroundColor();
    public function getFillColor();
    public function getTitle();
    public function getCloseAction();
    public function getStopAction();
    public function getOptionField();
    public static function getPostsOption();
    public static function setPostsOption($value);
    public static function deletePostsOption();
}
