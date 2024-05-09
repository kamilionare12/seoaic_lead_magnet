<?php

namespace SEOAIC\thirdparty_plugins_meta_tags;

abstract class AbstractMetaTags
{
    protected $pluginID = '';
    protected $descriptionField = '';
    protected $keywordField = '';

    private function getPluginID()
    {
        return $this->pluginID;
    }

    protected function getMetaFieldValue($postID = null, $fieldName = '')
    {
        if (
            !empty($postID)
            && is_numeric($postID)
            && !empty($fieldName)
        ) {
            return get_post_meta($postID, $fieldName, true);
        }
    }

    // default handler, can be overwritten in child classes
    public function setDescription($postID = null, $description = '')
    {
        if (
            !empty($postID)
            && is_numeric($postID)
        ) {
            update_post_meta($postID, $this->descriptionField, $description);
        }
    }

    // default handler, can be overwritten in child classes
    public function setKeyword($postID = null, $keyword = '')
    {
        if (
            !empty($postID)
            && is_numeric($postID)
        ) {
            update_post_meta($postID, $this->keywordField, $keyword);
        }
    }

    public function isPluginActive()
    {
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));

        return in_array($this->getPluginID(), $activePlugins);
    }

    public function getMetaDescriptionFieldName()
    {
        return $this->descriptionField;
    }

    public function getMetaKeywordFieldName()
    {
        return $this->keywordField;
    }

    public function getDescription($postID)
    {
        return $this->getMetaFieldValue($postID, $this->getMetaDescriptionFieldName());
    }

    public function getKeyword($postID)
    {
        return $this->getMetaFieldValue($postID, $this->getMetaKeywordFieldName());
    }
}
