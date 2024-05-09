<?php

namespace SEOAIC\posts_mass_actions;

use Exception;
use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_POSTS;
use SEOAIC\SEOAIC_SETTINGS;
use SEOAIC\thirdparty_plugins_meta_tags\AIOSEOMetaTags;
use SEOAIC\thirdparty_plugins_meta_tags\RankMathMetaTags;
use SEOAIC\thirdparty_plugins_meta_tags\YoastMetaTags;

class PostsMassTranslate extends AbstractPostsMassAction
{
    private $posts;
    private $languageField;
    private $translatingStatus;
    private $failedStatus;
    private $selectedLanguage;

    public function __construct($seoaic)
    {
        parent::__construct($seoaic);

        $this->backendActionURL = 'api/ai/posts/translate';
        $this->backendCheckStatusURL = 'api/ai/posts/translate/status';
        $this->backendContentURL = 'api/ai/posts/translate/content';
        $this->backendClearURL = 'api/ai/posts/translate/clear';
        $this->statusField = 'seoaic_translate_status';
        $this->cronCheckStatusHookName = 'seoaic/posts/translate/check_status_cron_hook';

        $this->languageField = 'seoaic_translate_selected_language';
        $this->translatingStatus = 'translating';
        $this->failedStatus = 'failed';

        $this->successfullRunMessage = 'Translation started';
        $this->completeMessage = 'All posts have been translated';
    }

    public static function init()
    {
        $self = new self(new SEOAIC());
        add_action($self->cronCheckStatusHookName, [$self, 'cronPostsCheckStatus']);
    }

    public function getLanguageField()
    {
        return $this->languageField;
    }

    public function prepareData($request)
    {
        if (empty($request['post-mass-edit'])) {
            throw new Exception('No posts selected');
        }

        $postIDs = !is_array($request['post-mass-edit']) ? [$request['post-mass-edit']] : $request['post-mass-edit'];
        $postIDs = array_filter($postIDs, function ($id) {
            return is_numeric($id);
        });

        if (empty($postIDs)) {
            throw new Exception('Posts not found');
        }
        // make sure posts are available
        $posts = $this->getAvailablePostsForTranslation($postIDs);

        if (empty($posts)) {
            throw new Exception('Posts not found');
        }

        $data['posts'] = [];
        $this->selectedLanguage = $this->seoaic->multilang->get_language_by($request['seoaic_multilanguages'][0], 'locale');
        $pluginInstance = $this->getAvailableSEOPlugin();

        foreach ($posts as $post) {
            $data['posts'][] = [
                'id' => $post->ID,
                'language' => !empty($this->selectedLanguage['name']) ? $this->selectedLanguage['name'] : '',
                'fields' => array_merge(
                    [
                        'title' => $post->post_title,
                        'content' => $post->post_content,
                    ],
                    $this->getMetaTagsFields($pluginInstance, $post->ID)
                )
            ];
        }

        return $data;
    }

    // public function getStatusResults()
    // {
    //     $checkStatusResult = $this->sendCheckStatusRequest();
    //     $result = $this->pocessCheckStatusResults($checkStatusResult);
    //     $isRunning = $this->isRunning();

    //     if (!$isRunning) {
    //         $this->sendClearRequest(['full' => false]);
    //         $this->unregisterPostsCheckStatusCron();
    //     }

    //     return $result;
    // }

    public function processActionResults($result)
    {
        if (
            !empty($result)
            && 'success' == $result['status']
            && !empty($this->posts)
        ) {
            foreach ($this->posts as $post) {
                $this->updatePostData($post->ID, [
                    $this->getStatusField() => $this->translatingStatus,
                    $this->languageField => $this->selectedLanguage,
                ]);
            }

            if ($this->useCron) {
                $this->registerPostsCheckStatusCron();
            }

            return true;

        } else {
            return false;
        }
    }

    public function pocessCheckStatusResults($result)
    {
        $results = [
            'done' => [],
            'failed' => [],
        ];

        if (!empty($result)) {
            if (
                !empty($result['completed'])
                && is_array($result['completed'])
            ) {
                $results['done'] = $this->processCompleted($result['completed']);
            }

            if (!empty($result['failed'])) {
                $this->processFailed($result['failed']);
                $results['failed'] = array_merge($results['failed'], $result['failed']);
            }

            return $results;
        }

        return $results;
    }

    protected function processCompleted($ids = [])
    {
        $return = [];

        if (
            !empty($ids)
            && is_array($ids)
        ) {
            // doublecheck the IDs to exist
            $posts = $this->getTranslatingPostsByIDs($ids);
            if (empty($posts)) {
                return;
            }

            $postsByIDs = array_combine(array_column($posts, 'ID'), $posts);

            // change status
            foreach ($postsByIDs as $origPost) {
                $this->updatePostData($origPost->ID, [
                    $this->getStatusField() => 'completed',
                ]);
            }

            $postIDs = array_map(function ($item) {
                return $item->ID;
            }, $posts);

            $data = [
                'post_ids' => $postIDs,
            ];
            $contentResult = $this->sendContentRequest($data);

            if (
                !empty($contentResult)
                && is_array($contentResult)
            ) {
                foreach ($contentResult as $item) {
                    if (
                        !empty($item['id'])
                        && !empty($item['fields'])
                    ) {
                        $args = [
                            'post_type'     => SEOAIC_SETTINGS::getSEOAICPostType(),
                            // 'post_status'   => $postsByIDs[$item['id']]->post_status, // parent status
                            'post_status'   => 'draft',
                            'post_title'    => !empty($item['fields']['title']) ? wp_strip_all_tags($item['fields']['title']) : 'Post #' . wp_strip_all_tags($item['id']) . ' translation',
                            'post_content'  => !empty($item['fields']['content']) ? $item['fields']['content'] : '',
                        ];
                        $insertID = wp_insert_post($args);

                        if (is_wp_error($insertID)) {
                            $errors = $insertID->get_error_messages();
                            foreach ($errors as $error) {
                                $this->seoaic->posts->debugLog('[ERROR] Mass post translate: post #' . $item['id'] . '; err: ' . print_r($error, true));
                            }
                        } else {
                            $updData = [
                                'seoaic_posted'             => '1',
                                'translated_from_post_id'   => $item['id'],
                            ];

                            // set translation lang
                            $this->seoaic->multilang->setPostLanguage($insertID, [
                                'language'  => $item['language'] ?? '',
                                'post_type' => SEOAIC_SETTINGS::getSEOAICPostType(),
                                'parent_id' => $item['id'],
                            ], true);

                            if ($seoPlugin = $this->getAvailableSEOPlugin()) {
                                // $updData[$seoPlugin->getMetaDescriptionFieldName()] = !empty($item['meta_description']) ? $item['meta_description'] : '';
                                // $updData[$seoPlugin->getMetaKeywordFieldName()] = !empty($item['meta_key']) ? $item['meta_key'] : '';
                                $this->setMetaTagsFields($seoPlugin, $insertID, [
                                    'meta_description' => !empty($item['fields']['meta_description']) ? $item['fields']['meta_description'] : '',
                                    'meta_key' => !empty($item['fields']['meta_key']) ? $item['fields']['meta_key'] : '',
                                ], $item['id']);
                            }

                            $this->updatePostData($insertID, $updData);

                            $return[$item['id']] = [
                                'id' => $insertID,
                                'language' => $item['language'],
                                'href' => get_edit_post_link($insertID)
                            ];
                        }
                    }
                }
            }
        }

        return $return;
    }

    protected function processFailed($ids = [])
    {
        if (
            !empty($ids)
            && is_array($ids)
        ) {
            // doublecheck the IDs to exist
            $posts = $this->getTranslatingPostsByIDs($ids);
            if (empty($posts)) {
                return;
            }

            $postsByIDs = array_combine(array_column($posts, 'ID'), $posts);

            // change status
            foreach ($postsByIDs as $origPost) {
                $this->updatePostData($origPost->ID, [
                    $this->getStatusField() => $this->failedStatus,
                ]);
            }
        }
    }

    public function isRunning()
    {
        $posts = $this->getTranslatingPostsAll();

        return !empty($posts);
    }

    public function cronPostsCheckStatus()
    {
        $this->seoaic->posts->debugLog('[CRON]', __CLASS__);
        $this->getStatusResults();
    }

    /**
     * Gets needed meta tags fields from the external SEO plugin
     * @param int|string $postID
     * @retur narray
     */
    private function getMetaTagsFields($pluginInstance = null, $postID = null)
    {
        $metaTagFields = [
            'meta_description' => '',
            'meta_key' => '',
        ];

        if (
            null == $pluginInstance
            || empty($postID)
            || !is_numeric($postID)
        ) {
            return $metaTagFields;
        }

        $metaTagFields['meta_description'] = $pluginInstance->getDescription($postID);
        $metaTagFields['meta_key'] = $pluginInstance->getKeyword($postID);

        return $metaTagFields;
    }

    private function setMetaTagsFields($pluginInstance = null, $postID = null, $fields = [], $origID = null)
    {
         if (
            null == $pluginInstance
            || empty($postID)
            || !is_numeric($postID)
            || empty($fields)
        ) {
            return false;
        }

        $pluginInstance->setKeyword($postID, $fields['meta_key'], $origID);
        $pluginInstance->setDescription($postID, $fields['meta_description'], $origID);

        return true;
    }

    /**
     * Gets available posts for translation, e.g. not in "Edit" or "Review" state
     * @param array $postIDs options array of IDs to search among
     * @return array
     */
    private function getAvailablePostsForTranslation($postIDs = [])
    {
        $reviewStatusField = (new PostsMassReview($this->seoaic))->getStatusField();
        $args = [
            'posts_per_page'    => -1,
            'post_type'         => 'any',
            'post_status'       => 'any',
            'lang' => '', // disable default lang setting
            'meta_query'        => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => $this->getStatusField(),
                        'value' => $this->translatingStatus,
                        'compare' => '!=',
                    ],
                    [
                        'key' => $this->getStatusField(),
                        'compare' => 'NOT EXISTS',
                    ],
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => SEOAIC_POSTS::EDIT_STATUS_FIELD,
                        'value' => 'pending',
                        'compare' => '!=',
                    ],
                    [
                        'key' => SEOAIC_POSTS::EDIT_STATUS_FIELD,
                        'compare' => 'NOT EXISTS',
                    ],
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => $reviewStatusField,
                        'value' => 'reviewing',
                        'compare' => '!=',
                    ],
                    [
                        'key' => $reviewStatusField,
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ],
        ];

        if (!empty($postIDs)) {
            $postIDs = !is_array($postIDs) ? [$postIDs] : $postIDs;
            $args['post__in'] = $postIDs;
        }
        $posts = get_posts($args);

        $this->posts = $posts;

        return $posts;
    }

    /**
     * Gets all posts that were sent for translation
     * @param array $postIDs options array of IDs to search among
     * @return array
     */
    private function getTranslatingPostsAll()
    {
        $args = [
            'posts_per_page'    => -1,
            'post_type'         => 'any',
            'post_status'       => 'any',
            'lang' => '',
            'meta_query'        => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => $this->getStatusField(),
                    'value' => $this->translatingStatus,
                    'compare' => '=',
                ],
            ],
        ];

        $posts = get_posts($args);

        $this->posts = $posts;

        return $posts;
    }

    /**
     * @param array|int $ids Accepts array if IDs or single ID
     */
    private function getTranslatingPostsByIDs($ids = [], $returnIDsOnly = false)
    {
        $args = [
            'posts_per_page'    => -1,
            'post_type'         => 'any',
            'post_status'       => 'any',
            'lang' => '',
            'meta_query'        => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => $this->getStatusField(),
                    'value' => $this->translatingStatus,
                    'compare' => '=',
                ],
            ],
        ];

        if (!empty($ids)) {
            $args['include'] = !is_array($ids) ? [$ids] : $ids;
        }

        if ($returnIDsOnly) {
            $args['fields'] = 'ids';
        }

        $posts = get_posts($args);

        $this->posts = $posts;

        return $posts;
    }

    /**
     * Gets first available external SEO plugin
     * @return object|null instanse of first available class, or null if there is no activated plugins
     */
    private function getAvailableSEOPlugin()
    {
        $plugins = [
            new YoastMetaTags(),
            new RankMathMetaTags(),
            new AIOSEOMetaTags(),
        ];

        foreach ($plugins as $instance) {
            if ($instance->isPluginActive()) {
                return $instance;
            }
        }

        return null;
    }

    public function isPostTranslating($postID)
    {
        return $this->translatingStatus == $this->getPostStatus($postID);
    }

    public function isPostTranslateFailed($postID)
    {
        return $this->failedStatus == $this->getPostStatus($postID);
    }

    private function getPostStatus($postID)
    {
        return get_post_meta($postID, $this->getStatusField(), true);
    }
}
