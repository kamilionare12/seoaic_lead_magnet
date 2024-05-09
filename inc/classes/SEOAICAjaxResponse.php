<?php

namespace SEOAIC;

class SEOAICAjaxResponse
{
    private $status;
    private $message;
    private $fields;

    public function __construct()
    {
        $this->status = '';
        $this->message = '';
        $this->fields = [];
    }

    /**
     * @param string $msg optional message text
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function success($msg = '')
    {
        $instance = new self();
        $instance->status = 'success';
        $instance->message = $msg;

        return $instance;
    }

    /**
     * @param string $msg optional message text
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function complete($msg = '')
    {
        $instance = new self();
        $instance->status = 'complete';
        $instance->message = $msg;

        return $instance;
    }

    /**
     * @param string $msg optional message text
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function progress($msg = '')
    {
        $instance = new self();
        $instance->status = 'progress';
        $instance->message = $msg;

        return $instance;
    }

    /**
     * @param string $msg optional message text
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function waiting($msg = '')
    {
        $instance = new self();
        $instance->status = 'waiting';
        $instance->message = $msg;

        return $instance;
    }

    /**
     * @param string $msg optional message text
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function alert($msg = '')
    {
        $instance = new self();
        $instance->status = 'alert';
        $instance->message = $msg;

        return $instance;
    }

    /**
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function reload()
    {
        $instance = new self();
        $instance->status = 'reload';

        return $instance;
    }

    /**
     * @param string $msg error message text
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function error($msg)
    {
        $instance = new self();
        $instance->status = 'error';
        $instance->message = $msg;

        return $instance;
    }

    /**
     * Adds additional fields to response
     * @param array $fields additional fields that will be appended to response. Array format: key => value
     * @return SEOAICAjaxResponse current instanse of class
     */
    public function addFields($fields=[])
    {
        if (
            !empty($fields)
            && is_array($fields)
        ) {
            $this->fields = $fields;
        }

        return $this;
    }

    /**
     * Sends WP response and dies
     * @return void
     */
    public function wpSend()
    {
        $response = [
            'status' => $this->status,
            'message' => $this->message,
        ];

        if (
            !empty($this->fields)
            && is_array($this->fields)
        ) {
            foreach ($this->fields as $key => $value) {
                $response[$key] = $value;
            }
        }

        wp_send_json($response);
    }

     /**
     * @return SEOAICAjaxResponse instanse of class
     */
    public static function redirect()
    {
        $instance = new self();
        $instance->status = 'redirect';

        return $instance;
    }

    /**
     * Create redirect field WP
     * @param string $url
     * @return SEOAICAjaxResponse
     */
    public function redirectTo($url = '')
    {
        $this->addFields(['redirectTo' => $url])->wpSend();
    }
}
