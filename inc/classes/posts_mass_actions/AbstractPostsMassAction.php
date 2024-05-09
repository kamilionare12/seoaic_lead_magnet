<?php

namespace SEOAIC\posts_mass_actions;

abstract class AbstractPostsMassAction
{
    public $successfullRunMessage;
    public $completeMessage;
    public $stopMessage;

    protected $seoaic;
    protected $backendActionURL = '';
    protected $backendCheckStatusURL = '';
    protected $backendContentURL = '';
    protected $backendClearURL = '';
    protected $statusField = '';
    protected $cronCheckStatusHookName = '';
    protected $useCron = true;
    protected $errors = [];
    protected $loader = null;

    public function __construct($seoaic)
    {
        $this->seoaic = $seoaic;

        add_filter('cron_schedules', [$this, 'addCronInterval']);
    }

    abstract protected function prepareData($incomeData);
    abstract protected function processActionResults($results);
    abstract protected function pocessCheckStatusResults($results);
    abstract protected function processCompleted($ids);
    abstract protected function processFailed($ids);
    abstract protected function cronPostsCheckStatus();
    abstract public function isRunning();

    public function getBackendActionURL()
    {
        return $this->backendActionURL;
    }

    public function getBackendCheckStatusURL()
    {
        return $this->backendCheckStatusURL;
    }

    public function getBackendContentURL()
    {
        return $this->backendContentURL;
    }

    public function getBackendClearURL()
    {
        return $this->backendClearURL;
    }

    public function getStatusField()
    {
        return $this->statusField;
    }

    public function getcronCheckStatusHookName()
    {
        return $this->cronCheckStatusHookName;
    }

    public function sendActionRequest($data = [])
    {
        return $this->sendRequest($this->getBackendActionURL(), $data);
    }

    protected function sendCheckStatusRequest($data = [])
    {
        return $this->sendRequest($this->getBackendCheckStatusURL(), $data);
    }

    protected function sendContentRequest($data = [])
    {
        return $this->sendRequest($this->getBackendContentURL(), $data);
    }

    protected function sendClearRequest($data = [])
    {
        return $this->sendRequest($this->getBackendClearURL(), $data);
    }

    protected function sendRequest($url, $data = [])
    {
        if (!empty($url)) {
            $result = $this->seoaic->curl->initWithReturn($url, $data, true);

            return $result;
        }

        return false;
    }

    /**
     * Updates Post's data (meta fields)
     * @param array|int $post WP_Post in array format or ID
     * @param array $data meta fields to update. Assoc array in a "key => value" format
     * @return bool
     */
    protected function updatePostData($post, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        if (
            is_numeric($post)
            && (int) $post == $post
        ) {
            $id = $post;
        } else {
            $id = $post['id'];
        }

        // $updateRes = wp_update_post([
        //     'ID'            => $id,
        //     'meta_input'    => $data,
        // ]);
        // if (is_wp_error($updateRes)) {
        //     return false;
        // }

        // return true;

        foreach ($data as $key => $value) {
            update_post_meta($id, $key, $value);
        }
    }

    public function getStatusResults()
    {
        $this->seoaic->posts->debugLog(get_class($this), 'RUN');

        $checkStatusResult = $this->sendCheckStatusRequest();
        $this->seoaic->posts->debugLog(get_class($this), 'status_result', $checkStatusResult);
        $result = $this->pocessCheckStatusResults($checkStatusResult);
        $isRunning = $this->isRunning();
        $result['is_running'] = $isRunning;

        if (!$isRunning) {
            $this->sendClearRequest(['full' => false]);
            $this->unregisterPostsCheckStatusCron();
        }

        return $result;
    }

    protected function registerPostsCheckStatusCron()
    {
        if (
            !empty($this->cronCheckStatusHookName)
            && !wp_next_scheduled($this->cronCheckStatusHookName)
        ) {
            $this->seoaic->posts->debugLog('[CRON]', get_class($this), 'hook: ' . $this->cronCheckStatusHookName);
            $result = wp_schedule_event(time() + 4 * 60, '5_minutes', $this->cronCheckStatusHookName, [], true);
            // $result = wp_schedule_event(time(), '30_seconds', $this->cronCheckStatusHookName, [], true);

            if (is_wp_error($result)) {
                $this->seoaic->posts->debugLog('[CRON]', get_class($this), '[Error]: ' . $result->get_error_message());
            }
        }
    }

    public function unregisterPostsCheckStatusCron()
    {
        if (!empty($this->cronCheckStatusHookName)) {
            $this->seoaic->posts->debugLog('[CRON]', get_class($this));
            $timestamp = wp_next_scheduled($this->cronCheckStatusHookName);
            wp_unschedule_event($timestamp, $this->cronCheckStatusHookName);
        }
    }

    public function addCronInterval($schedules)
    {
        $schedules['5_minutes'] = [
            'interval' => 5 * 60,
            'display'  => esc_html__('Every 5 minutes'),
        ];
        $schedules['30_seconds'] = [
            'interval' => 30,
            'display'  => esc_html__('Every 30 seconds'),
        ];

        return $schedules;
    }

    public function getErrors()
    {
        return implode('<br>', array_map(function ($err) {
            return esc_html($err);
        }, $this->errors));
    }

    protected function addPostIDsToLoader($ids = [])
    {
        if (
            $this->loader
            && !empty($ids)
        ) {
            $this->loader->addIDs($ids);
        }
    }

    protected function completePostIDsInLoader($ids = [])
    {
        if (
            $this->loader
            && !empty($ids)
        ) {
            $this->loader->completeIDs($ids);
        }
    }
}
