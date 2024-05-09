<?php

namespace SEOAIC\thirdparty_plugins_meta_tags;

use SEOAIC\interfaces\ThirdpartyPluginsMetaTagsInterface;

class RankMathMetaTags extends AbstractMetaTags implements ThirdpartyPluginsMetaTagsInterface
{
    public function __construct()
    {
        $this->pluginID = 'seo-by-rank-math/rank-math.php';
        $this->descriptionField = 'rank_math_description';
        $this->keywordField = 'rank_math_focus_keyword';
    }
}
