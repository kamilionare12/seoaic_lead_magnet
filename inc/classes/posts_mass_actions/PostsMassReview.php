<?php

namespace SEOAIC\posts_mass_actions;

use Exception;
use SEOAIC\interfaces\PostsMassActionStoppable;
use SEOAIC\loaders\PostsReviewLoader;
use SEOAIC\SEOAIC;
use SEOAIC\SEOAIC_POSTS;

class PostsMassReview extends AbstractPostsMassAction implements PostsMassActionStoppable
{
    private const PER_REQUEST__REVIEW = 10;
    private const PER_REQUEST__REVIEW_CONTENT = 10;
    private const REVIEWING_STATUS = 'reviewing';
    private const FAILED_STATUS = 'failed';
    private const COMPLETED_STATUS = 'completed';
    private const REVIEW_STATUS_TIME_FIELD = 'seoaic_review_time';
    private const REVIEW_RESULT_ORIGINAL_FIELD = 'seoaic_review_result_original';
    private const REVIEW_RESULT_FIELD = 'seoaic_review_result';

    public function __construct($seoaic)
    {
        parent::__construct($seoaic);

        $this->backendActionURL = 'api/ai/posts/review';
        $this->backendCheckStatusURL = 'api/ai/posts/review/status';
        $this->backendContentURL = 'api/ai/posts/review/content';
        $this->backendClearURL = 'api/ai/posts/review/clear';
        $this->statusField = 'seoaic_review_status';
        $this->cronCheckStatusHookName = 'seoaic/posts/review/check_status_cron_hook';
        $this->loader = new PostsReviewLoader();

        $this->successfullRunMessage = 'Review started';
        $this->completeMessage = 'All posts have been reviewed.';
        $this->stopMessage = 'Posts review have been stopped.';
    }

    public static function init()
    {
        $self = new self(new SEOAIC());
        add_action($self->cronCheckStatusHookName, [$self, 'cronPostsCheckStatus']);
    }

    public function getReviewingStatus()
    {
        return self::REVIEWING_STATUS;
    }

    public function prepareData($request)
    {
        $postIDs = [];
        $posts = [];

        if (!empty($request['post-mass-edit'])) {
            $selectedIDs = $request['post-mass-edit'];

            if (is_array($selectedIDs)) {
                $postIDs = $selectedIDs;
            } elseif (
                is_numeric($selectedIDs)
                && intval($selectedIDs) == $selectedIDs
            ) {
                $postIDs = [$selectedIDs];
            }
        }

        if (empty($postIDs)) {
            throw new Exception('No posts selected');
        }

        // make sure posts are available
        $posts = $this->getAvailablePostsForReview($postIDs);

        if (empty($posts)) {
            throw new Exception('Posts not found');
        }

        $data['posts'] = [];
        $postsData = [];
        $prompt = !empty($request['mass_prompt']) ? $request['mass_prompt'] : '';

        foreach ($posts as $post) {
            $postsData[] = [
                'id'        => $post->ID,
                'content'   => $post->post_content,
                'language'  => 'English', // use default language to be able to parse response
            ];
        }

        $postsChunks = array_chunk($postsData, self::PER_REQUEST__REVIEW);
        $dataChunks = [];

        foreach ($postsChunks as $postsChunk) {
            $dataChunks[] = [
                'prompt'    => $prompt,
                'posts'     => $postsChunk,
            ];
        }

        return $dataChunks;
    }

    /**
     * Override parent's method to be able to make requests in a loop
     */
    public function sendActionRequest($dataChunks = [])
    {
        foreach ($dataChunks as &$dataChunk) {
            $this->seoaic->posts->debugLog(__CLASS__, array_map(function ($item) {
                if (
                    !empty($item['posts'])
                    && is_array($item['posts'])
                ) {
                    foreach ($item['posts']as &$post) {
                        $post['content'] = substr($post['content'], 0, 50) . '...';
                    }
                }
                return $item;
            }, [$dataChunk]));

            $dataChunk['result'] = $this->sendRequest($this->getBackendActionURL(), $dataChunk);
        }

        return $dataChunks;
    }

    public function processActionResults($dataChunks = [])
    {
        $successResults = false;
        $loaderIDs = [];

        if (!empty($dataChunks)) {
            foreach ($dataChunks as $dataChunk) {
                $result = $dataChunk['result'];

                if (
                    !empty($result['status'])
                    && 'success' == $result['status']
                    && !empty($dataChunk['posts'])
                ) {
                    $successResults = true;

                    foreach ($dataChunk['posts'] as $post) {
                        $postMeta = [
                            $this->getStatusField() => self::REVIEWING_STATUS,
                            'seoaic_review_prompt'  => $dataChunk['prompt'],
                        ];
                        $this->updatePostData($post['id'], $postMeta);
                        $loaderIDs[] = $post['id'];
                    }

                } else {
                    if (!empty($result['message'])) {
                        $this->errors[] = $result['message'];
                    }

                    foreach ($dataChunk['posts'] as $post) {
                        $this->updatePostData($post['id'], [
                            $this->getStatusField() => '',
                        ]);
                    }
                }
            }

            if ($successResults) {
                if ($this->useCron) {
                    $this->registerPostsCheckStatusCron();
                }

                $this->addPostIDsToLoader($loaderIDs);
            }

            return true;

        } else {
            return false;
        }
    }

    public function pocessCheckStatusResults($result)
    {
        $returnData = [
            'done' => [],
            'failed' => [],
        ];


        if (!empty($result)) {
            if (
                !empty($result['completed'])
                && is_array($result['completed'])
            ) {
                $this->processCompleted($result['completed']);
                $returnData['done'] = $result['completed'];
            }

            if (!empty($result['failed'])) {
                $this->processFailed($result['failed']);
                $returnData['failed'] = array_merge($returnData['failed'], $result['failed']);
            }
        }

        return $returnData;
    }

    protected function processCompleted($ids = [])
    {
        $loaderIDs = [];

        if (
            !empty($ids)
            && is_array($ids)
        ) {
            // doublecheck the IDs to exist
            $posts = $this->getReviewingPostsByIDs($ids);
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
                        empty($item)
                        || empty($item['id'])
                        || empty($item['content'])
                        || !in_array($item['id'], $postIDs)
                    ) {
                        continue;
                    }

                    $this->updatePostData($item['id'], [
                        $this->getStatusField() => self::COMPLETED_STATUS,
                        self::REVIEW_STATUS_TIME_FIELD => time(),
                        self::REVIEW_RESULT_ORIGINAL_FIELD => $item['content'],
                        self::REVIEW_RESULT_FIELD => $this->makeReviewResult($item['content']),
                    ]);

                    $loaderIDs[] = $item['id'];
                }
            }

            $this->completePostIDsInLoader($loaderIDs);
        }
    }

    private function makeReviewResult($str = '')
    {
        $str = strtolower(trim($str, '.'));
        if (
            'yes' == $str
            || 'no' == $str
        ) {
            return $str;
        }

        return 'unknown';
    }

    protected function processFailed($ids = [])
    {
        if (
            !empty($ids)
            && is_array($ids)
        ) {
            // doublecheck the IDs to exist
            $posts = $this->getReviewingPostsByIDs($ids, $IDsOnly = false, $all = true);
            if (empty($posts)) {
                return;
            }

            $postsByIDs = array_combine(array_column($posts, 'ID'), $posts);

            // change status
            foreach ($postsByIDs as $origPost) {
                $this->updatePostData($origPost->ID, [
                    $this->getStatusField() => self::FAILED_STATUS,
                    self::REVIEW_STATUS_TIME_FIELD => time()
                ]);
                $loaderIDs[] = $origPost->ID;
            }

            $this->completePostIDsInLoader($loaderIDs);
        }
    }

    public function isRunning()
    {
        $posts = $this->getReviewingPostsAll();

        return !empty($posts);
    }

    public function cronPostsCheckStatus()
    {
        $this->seoaic->posts->debugLog('[CRON]', __CLASS__);
        $this->getStatusResults();
    }

    public function stop()
    {
        $this->seoaic->posts->debugLog();
        $posts = $this->getReviewingPostsAll();

        if (!empty($posts)) {
            foreach ($posts as $post) {
                $this->resetReviewResults($post->ID);
            }
        }

        $this->sendClearRequest(['full' => true]);
        $this->unregisterPostsCheckStatusCron();
        PostsReviewLoader::deletePostsOption();
    }

    public function resetReviewResults($postID)
    {
        $this->updatePostData($postID, [
            $this->getStatusField()         => '',
            self::REVIEW_STATUS_TIME_FIELD  => '',
            'seoaic_review_prompt'          => '',
            'seoaic_review_result_original' => '',
            'seoaic_review_result'          => '',
        ]);
    }


    /**
     * Gets available posts for Review, e.g. not in "Edit" or "Review" state
     * @param array $postIDs options array of IDs to search among
     * @return array
     */
    private function getAvailablePostsForReview($postIDs = [])
    {
        $args = [
            'numberposts'       => -1,
            'post_type'         => 'any',
            'post_status'       => 'any',
            'lang'              => '', // disable default lang setting
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
                        'value' => self::REVIEWING_STATUS,
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
            ],
        ];

        if (!empty($postIDs)) {
            $postIDs = !is_array($postIDs) ? [$postIDs] : $postIDs;
            $args['post__in'] = $postIDs;
        }
        $posts = get_posts($args);

        // $this->posts = $posts;

        return $posts;
    }

    /**
     * Gets all posts that were sent for review
     * @param array $postIDs options array of IDs to search among
     * @return array
     */
    private function getReviewingPostsAll()
    {
        $args = [
            'numberposts'       => -1,
            'post_type'         => 'any',
            'post_status'       => 'any',
            'lang'              => '', // disable default lang setting
            'meta_query'        => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => $this->getStatusField(),
                    'value' => self::REVIEWING_STATUS,
                    'compare' => '=',
                ],
            ],
        ];

        $posts = get_posts($args);

        // $this->posts = $posts;

        return $posts;
    }

    /**
     * @param array|int $ids Accepts array if IDs or single ID
     */
    private function getReviewingPostsByIDs($ids = [], $returnIDsOnly = false, $all = false)
    {
        $args = [
            'numberposts'       => ($all ? -1 : self::PER_REQUEST__REVIEW_CONTENT),
            'post_type'         => 'any',
            'post_status'       => 'any',
            'lang'              => '', // disable default lang setting
            'meta_query'        => [
                'relation' => 'AND',
                [
                    'key' => 'seoaic_posted',
                    'value' => '1',
                    'compare' => '=',
                ],
                [
                    'key' => $this->getStatusField(),
                    'value' => self::REVIEWING_STATUS,
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

        // $this->posts = $posts;

        return $posts;
    }
}
