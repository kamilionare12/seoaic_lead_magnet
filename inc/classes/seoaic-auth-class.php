<?php

use SEOAIC\DB\KeywordsPostsTable;

class SEOAIC_AUTH
{
    private $seoaic;
    function __construct ( $_seoaic )
    {
        $this->seoaic = $_seoaic;

        add_action('wp_ajax_seoaic_registration', [$this, 'registration']);
        add_action('wp_ajax_seoaic_login', [$this, 'login']);
        add_action('wp_ajax_seoaic_forgot', [$this, 'forgot']);
        add_action('wp_ajax_seoaic_disconnect', [$this, 'disconnect']);

    }

    /**
     * Set api token
     *
     * @param string $email
     * @param string $api_token
     */
    public function set_api_token ( $email, $api_token = '', $refresh_token = '' )
    {
        global $SEOAIC_OPTIONS;

        if ( !is_array($SEOAIC_OPTIONS) ) {
            $SEOAIC_OPTIONS = [];
        }

        $SEOAIC_OPTIONS['seoaic_api_email'] = $email;
        $SEOAIC_OPTIONS['seoaic_api_token'] = $api_token;
        $SEOAIC_OPTIONS['seoaic_refresh_token'] = $refresh_token;

        update_option('seoaic_options', $SEOAIC_OPTIONS);
    }

    /**
     * Check api token
     *
     * @param string $email
     * @param string $api_token
     */
    public function check_api_token( $email, $api_token )
    {
        global $SEOAIC_OPTIONS;

        $current_email = $SEOAIC_OPTIONS['seoaic_api_email'];
        $current_token = $SEOAIC_OPTIONS['seoaic_api_token'];

        if ( $current_email === $email && $current_token === $api_token ) {
            return true;
        }

        return false;
    }

    /**
     * Ajax action - registration process
     */
    public function registration()
    {
        if (empty($_POST['email']) || empty($_POST['password']) || empty($_POST['repeat_password'])) {
            wp_send_json( [
                'status'   => 'error',
                'message'  => 'Fields are required!',
            ] );
        }

        if ($_POST['password'] !== $_POST['repeat_password']) {
            wp_send_json( [
                'status'   => 'error',
                'message'  => 'Passwords should be equal!',
            ] );
        }

        $data = [
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'repeat_password' => $_POST['repeat_password'],
            'domain' => $_SERVER['HTTP_HOST'],
            'request_url' => admin_url('admin-ajax.php'),
            'blogname' => get_option('blogname'),
        ];

        $result = $this->seoaic->curl->init('api/auth/create', $data, true);

        if ($result['status'] === 'success' && !empty($result['api_token'])) {
            $this->set_api_token($_POST['email'], $result['api_token'], $result['refresh_token']);

            wp_send_json( [
                'status'  => 'success',
            ] );
        }

        wp_send_json( [
            'status'  => 'error',
            'message' => $result
        ] );
    }

    /**
     * Ajax action - login
     */
    public function login($email = false, $password = false, $die = true)
    {
        $email = !empty($_POST['email']) ? $_POST['email'] : $email;
        $password = !empty($_POST['password']) ? $_POST['password'] : $password;

        if (empty($email) || empty($password)) {
            wp_send_json( [
                'status'  => 'alert',
                'message' => 'Fields are required!',
            ] );
        }

        $data = [
            'email' => $email,
            'password' => $password,
            'domain' => $_SERVER['HTTP_HOST'],
            'request_url' => admin_url('admin-ajax.php'),
            'blogname' => get_option('blogname'),
            'plugin' => true,
        ];

        $result = $this->seoaic->curl->init('api/auth/login', $data, true);

        if ($result['status'] === 'success' && !empty($result['api_token']) && !empty($result['api_token'])) {
            $this->set_api_token($email, $result['api_token'], $result['refresh_token']);

            if ( $die ) {
                wp_send_json([
                    'status' => 'success',
                ]);
            }
        }
    }

    /**
     * Ajax action - forgot password
     */
    public function forgot () {

        if (!current_user_can('administrator')) {
            wp_die();
        }

        if (empty($_POST['email'])) {
            wp_send_json( [
                'status'  => 'alert',
                'message' => 'Email is required!',
            ] );
        }

        $data = [
            'email' => $_POST['email'],
            'domain' => $_SERVER['HTTP_HOST'],
        ];

        $uri = 'api/auth/reset-code';
        if (!empty($_POST['recovery_code'])) {
            $data['token'] = $_POST['recovery_code'];
            $uri = 'api/auth/reset-password';
        }

        if (!empty($_POST['password']) && !empty($_POST['repeat_password'])) {

            if ($_POST['password'] !== $_POST['repeat_password']) {
                wp_send_json( [
                    'status'  => 'alert',
                    'message' => 'Passwords should be equal!',
                ] );
            }

            $data['password'] = $_POST['password'];
        }

        $result = $this->seoaic->curl->init($uri, $data, true);

        if ($result['status'] === 'success' && !empty($result['action']) && $result['action'] === 'login') {
            //$this->login(false, false, false);

            wp_send_json( [
                'status'   => 'alert',
                'message'  => 'Your password successful changed!',
            ] );
        }

        wp_send_json( [
            'status'  => 'success',
        ] );
    }

    /**
     * Ajax action - disconnect from server
     */
    public function disconnect()
    {
        global $SEOAIC_OPTIONS;

        if (
            !empty($_REQUEST['seoaic_clear'])
            && $_REQUEST['seoaic_clear'] === 'yes'
        ) {
            delete_option('seoaic_options');
            KeywordsPostsTable::truncate();
            // KeywordsPostsTable::drop();

        } else {
            unset($SEOAIC_OPTIONS['seoaic_api_email']);
            unset($SEOAIC_OPTIONS['seoaic_api_token']);
            update_option('seoaic_options', $SEOAIC_OPTIONS);
        }

        wp_send_json( [
            'status'  => 'alert',
            'message' => 'You are disconnected!',
        ] );
    }
}
