<?php

namespace SEOAIC\thirdparty_plugins_meta_tags;

use SEOAIC\interfaces\ThirdpartyPluginsMetaTagsInterface;

class YoastMetaTags extends AbstractMetaTags implements ThirdpartyPluginsMetaTagsInterface
{
    public function __construct()
    {
        $this->pluginID = 'wordpress-seo/wp-seo.php';
        $this->descriptionField = '_yoast_wpseo_metadesc';
        $this->keywordField = '_yoast_wpseo_focuskw';
    }
}
