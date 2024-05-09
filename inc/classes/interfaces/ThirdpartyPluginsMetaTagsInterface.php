<?php

namespace SEOAIC\interfaces;

interface ThirdpartyPluginsMetaTagsInterface
{
    public function isPluginActive();
    public function getDescription($postID);
    public function getKeyword($postID);
}
