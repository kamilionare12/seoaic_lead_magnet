<?php
namespace SEOAIC;

class SEOAIC_CURL {

    public $seoaic;
    private $method;

    function __construct($_seoaic, $method = 'post')
    {
        $this->seoaic = $_seoaic;
        $this->method = $method;
    }

    public function setMethodGet()
    {
        $this->method = 'get';

        return $this;
    }

    public function setMethodPost()
    {
        $this->method = 'post';

        return $this;
    }

    private function isPost()
    {
        return 'post' == $this->method;
    }

    public function init($url, $data, $json = false, $only_return = false, $auth = false)
    {
        global $SEOAIC_OPTIONS;

        $url = seoai_get_backend_url($url);
        $request_args = $this->getRequestArgs();
        $request_args['body'] = $json ? wp_json_encode($data) : $data;
        $result = '';

        if ($auth) {
            $request_args['headers']['Authorization'] = $SEOAIC_OPTIONS['seoaic_api_token'];
        }

        if ($this->isPost()) {
            $response = wp_safe_remote_post($url, $request_args);
        } else {
            $response = wp_safe_remote_get($url, $request_args);
        }

        // Handle unauthorized response
        if (wp_remote_retrieve_response_code($response) === 401) {
            $result_token = $this->refreshAuthToken();

            $this->updateAuthToken($result_token);
            $request_args['headers']['Authorization'] = $result_token['api_token'];
            $response = wp_safe_remote_post($url, $request_args);
        }

        // Handle response
        if (!is_wp_error($response)) {
            $result = $this->handleSuccessfulResponse($response);
        } else {
            $this->handleErrorResponse($response);
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code !== 200 && $response_code !== 201 && $response_code !== 400) {
            SEOAICAjaxResponse::alert($result['message'])->wpSend();
        }

        if ($response_code === 400) {
            SEOAICAjaxResponse::error($result['message'])->wpSend();
        }

        return $result;
    }

    /**
     * @param string $url
     * @param array $data Data
     * @param bool $json Encode request body as JSON
     * @param bool $auth Include Authorization header or not
     */
    public function initWithReturn($url, $data, $json = false, $auth = true)
    {
        global $SEOAIC_OPTIONS;

        $url = seoai_get_backend_url($url);
        $request_args = $this->getRequestArgs();
        $request_args['body'] = $json ? wp_json_encode($data) : $data;
        $result = '';

        if ($auth) {
            $request_args['headers']['Authorization'] = $SEOAIC_OPTIONS['seoaic_api_token'];
        }

        if ($this->isPost()) {
            $response = wp_safe_remote_post($url, $request_args);
        } else {
            $response = wp_safe_remote_get($url, $request_args);
        }

        // Handle unauthorized response
        if (wp_remote_retrieve_response_code($response) === 401) {
            $result_token = $this->refreshAuthToken();

            $this->updateAuthToken($result_token);
            $request_args['headers']['Authorization'] = $result_token['api_token'];
            $response = wp_safe_remote_post($url, $request_args);
        }

        // Handle response
        if (!is_wp_error($response)) {
            $result = $this->handleSuccessfulResponse($response);
        } else {
            $this->handleErrorResponse($response);
        }

        return $result;
    }

    private function getRequestArgs()
    {
        return array(
            'timeout' => 80,
            'sslverify' => seoaic_ssl_verifypeer(),
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => 'your-api-key-123',
            ),
        );
    }

    private function refreshAuthToken()
    {
        global $SEOAIC_OPTIONS;

        $refresh_data = [
            'refresh_token' => !empty($SEOAIC_OPTIONS['seoaic_refresh_token']) ? $SEOAIC_OPTIONS['seoaic_refresh_token'] : '',
        ];

        $request_args_refresh = $this->getRequestArgs();
        $request_args_refresh['body'] = wp_json_encode($refresh_data);
        $request_args_refresh['headers']['Authorization'] = $SEOAIC_OPTIONS['seoaic_api_token'];

        $refresh_url = seoai_get_backend_url('api/auth/refresh');
        $response_token = wp_safe_remote_post($refresh_url, $request_args_refresh);

        if (wp_remote_retrieve_response_code($response_token) === 401) {
            $this->handleUnauthorizedResponse();
        }

        $result_raw_token = wp_remote_retrieve_body($response_token);
        return json_decode($result_raw_token, true);
    }

    private function updateAuthToken($result_token)
    {
        global $SEOAIC_OPTIONS;

        $this->seoaic->auth->set_api_token($SEOAIC_OPTIONS['seoaic_api_email'], $result_token['api_token'], $result_token['refresh_token']);
    }

    private function handleUnauthorizedResponse()
    {
        global $SEOAIC_OPTIONS;

        unset($SEOAIC_OPTIONS['seoaic_api_email']);
        unset($SEOAIC_OPTIONS['seoaic_api_token']);
        unset($SEOAIC_OPTIONS['seoaic_refresh_token']);
        update_option('seoaic_options', $SEOAIC_OPTIONS);
        SEOAICAjaxResponse::redirect()->redirectTo('/wp-admin/admin.php?page=seoaic')->wpSend();
    }

    private function handleSuccessfulResponse($response)
    {
        $result_raw = wp_remote_retrieve_body($response);
        $result = json_decode($result_raw, true);
        file_put_contents(SEOAIC_LOG . 'a-wp-http-response.txt', $result_raw);

        if (!empty($result['credits'])) {
            $this->seoaic->set_api_credits($result['credits']);
        }

        if (!empty($result['callback'])) {
            $callback = $result['callback'];
            $this->$callback();
        }

        return $result;
    }

    private function handleErrorResponse($response)
    {
        $error_message = $response->get_error_message();
        echo 'HTTP request failed: ' . $error_message;
        wp_die();
    }

    // TODO: Unused function
    private function user_removed () {
        delete_option('seoaic_options');
    }
}
