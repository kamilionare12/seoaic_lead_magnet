<?php
add_action( 'admin_init', 'seoaic_settings_init' );

/**
 * Add settings
 */
function seoaic_settings_init() {

    register_setting( 'seoaic', 'seoaic_options' );

    add_settings_section(
        'seoaic_platform_api',
        __( 'SEO AI client settings', 'seoaic' ),
        'seoaic_platform_api_cb',
        'seoaic'
    );

    /*
    add_settings_field(
        'seoaic_platfrom_api_link',
        __( 'seoaic Token', 'seoaic' ),
        'seoaic_input_cb',
        'seoaic',
        'seoaic_platform_api',
        array(
            'label_for'         => 'seoaic_platfrom_api_link',
            'class'             => 'password',
            'type'              => 'text',
        )
    );
    */
}

function seoaic_platform_api_cb( $args ) {
}

function seoaic_input_cb( $args ) {
    global $SEOAIC_OPTIONS;

    ?>
    <input id="<?=esc_attr( $args['label_for'] );?>"
           class="<?=$args['class'];?>"
           type="<?=$args['type'];?>"
           name="seoaic_options[<?=esc_attr( $args['label_for'] );?>]"
           value="<?=( !empty($SEOAIC_OPTIONS) && !empty($SEOAIC_OPTIONS[ $args['label_for'] ]) ) ? $SEOAIC_OPTIONS[ $args['label_for'] ] : '';?>" />
    <?php
}

add_action( 'admin_menu', 'seoaic_options_page' );

/**
 * Add menu and submenu in admin panel menu
 */
function seoaic_options_page() {
    global $SEOAIC_OPTIONS;
    global $plugin_page;

    $capability = 'seoaic_edit_plugin';

    add_menu_page(
        'Connection',
        'SEO AI',
        $capability,
        'seoaic',
        'seoaic_options_html'
    );

    add_submenu_page( 'seoaic', 'Connection', 'SEO AI Connection', $capability, 'seoaic', NULL );

    global $SEOAIC_OPTIONS;

    if (
        !empty($SEOAIC_OPTIONS['seoaic_api_email'])
        && !empty($SEOAIC_OPTIONS['seoaic_api_token'])
        && isset($SEOAIC_OPTIONS['seoaic_scanned'])
        && intval($SEOAIC_OPTIONS['seoaic_scanned']) === 1
    ) {
        add_submenu_page( 'seoaic', 'Statistics', 'Dashboard', $capability, 'seoaic-company-dashboard', 'seoaic_dashboard_html' );
        add_submenu_page( 'seoaic', 'Site Audit', 'Site Audit', $capability, 'seoaic-seo-audit', 'seoaic_seo_audit_html' );
        add_submenu_page( 'seoaic', 'Keywords', 'Keywords', $capability, 'seoaic-keywords', 'seoaic_keywords_html' );
        add_submenu_page( 'seoaic', 'Ideas', 'Ideas', $capability, 'seoaic-ideas', 'seoaic_ideas_html' );
        add_submenu_page( 'seoaic', 'SEO AI created posts', 'Created posts', $capability, 'seoaic-created-posts', 'seoaic_created_posts_html' );
        add_submenu_page( 'seoaic', 'Posting Schedule', 'Schedule', $capability, 'seoaic-posting-schedule', 'seoaic_posting_schedule_html' );
        add_submenu_page( 'seoaic', 'Competitors', 'Competitors', $capability, 'seoaic-competitors', 'seoaic_competitors_html' );
        add_submenu_page( 'seoaic', 'Rank tracker', 'Rank tracker', $capability, 'seoaic-rank-tracker', 'seoaic_rank_tracker_html' );
        add_submenu_page( 'seoaic', 'Knowledge Bases', 'Knowledge Bases', $capability, 'seoaic-knowledge-bases', 'seoaic_knowledge_bases_html' );
        add_submenu_page( 'seoaic', 'Settings', 'Settings', $capability, 'seoaic-settings', 'seoaic_settings_html' );

        // Hide menu items using href styles
        add_submenu_page( 'seoaic', 'Automated Onboarding Wizard', 'Wizard', $capability, 'seoaic-onboarding-wizard', 'seoaic_onboarding_wizard_html' );
        add_submenu_page( 'seoaic', 'locations', 'Locations', $capability, 'seoaic-locations', 'seoaic_locations_html' );
    } else {
        // redirect to login page if user goes to other plugin's pages
        if ($plugin_page && (0 === strpos($plugin_page, 'seoaic-'))) {
            wp_redirect(admin_url('admin.php?page=seoaic'));
            exit;
        }
    }
}

/**
 * Render seoai options page html
 */
function seoaic_options_html() {
    global $SEOAIC_OPTIONS;
    $seoaic_api_email = !empty($SEOAIC_OPTIONS['seoaic_api_email']) ? $SEOAIC_OPTIONS['seoaic_api_email'] : false;
    $seoaic_api_token = !empty($SEOAIC_OPTIONS['seoaic_api_token']) ? $SEOAIC_OPTIONS['seoaic_api_token'] : false;

    if (   empty($seoaic_api_token)
        || empty($seoaic_api_email)
    ) {

        $subpage = !empty($_GET['subpage']) ? $_GET['subpage'] : 'seoaic-login';
        switch ( $subpage ) {
            case 'seoaic-registration':
            case 'seoaic-login':
            case 'seoaic-forgot':
                include(SEOAIC_DIR . 'inc/view/' . $subpage . '-view.php');
                break;
        }

    } else {
        if ( empty($_GET['subpage']) ) {
            include(SEOAIC_DIR . 'inc/view/seoaic-connected-view.php');
        } else {
            switch ( $_GET['subpage'] ) {
                case 'posting-schedule':
                    include(SEOAIC_DIR . 'inc/view/seoaic-posting-schedule-view.php');
                    break;
                case 'ideas':
                    include(SEOAIC_DIR . 'inc/view/seoaic-ideas-view.php');
                    break;
                case 'setinfo':
                    include(SEOAIC_DIR . 'inc/view/seoaic-setinfo-view.php');
                    break;
                default:
                    include(SEOAIC_DIR . 'inc/view/seoaic-connected-view.php');
            }
        }
    }
}

/**
 * Render schedule page html
 */
function seoaic_posting_schedule_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-posting-schedule-view.php' );
}

/**
 * Render ideas page html
 */
function seoaic_ideas_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-ideas-view.php' );
}

/**
 * Render ideas page html
 */
function seoaic_locations_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-locations-view.php' );
}

function seoaic_onboarding_wizard_html() {
    $file = SEOAIC_DIR . 'inc/view/wizard/seoaic-wizard-view.php';
    if (file_exists($file)) {
        include($file);
    }
}

/**
 * Render dashboard page html
 */
function seoaic_dashboard_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-dashboard-view.php' );
}

/**
 * Render ideas page html
 */
function seoaic_seo_audit_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-seo-audit-view.php' );
}

/**
 * Render competitors page html
 */
function seoaic_competitors_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-competitors-view.php' );
}

/**
 * Render created posts html
 */
function seoaic_created_posts_html() {
    include_once(SEOAIC_DIR . 'inc/view/seoaic-created-posts-view.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/post-mass-edit.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/post-mass-review.php');
    require_once(SEOAIC_DIR . 'inc/view/popups/post-mass-translate.php');
}

/**
 * Render created keywords html
 */
function seoaic_keywords_html() {
    include_once(SEOAIC_DIR . 'inc/view/seoaic-keywords-view.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/generate-keywords.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/add-keyword.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/rank-keyword.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/keyword-add-link.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/keywords-manage-categories.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/keywords-set-category.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/keyword-remove-and-reassign-confirm-modal.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/keywords-show-created.php');
}

/**
 * Render created posts html
 */
function seoaic_settings_html() {
    include_once(SEOAIC_DIR . 'inc/view/seoaic-settings-view.php');
    include_once(SEOAIC_DIR . 'inc/view/popups/settings-generate-description.php');
}

/**
 * Render rank tracker html
 */
function seoaic_rank_tracker_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-rank-tracker-view.php' );
}

/**
 * Render created knowledge bases html
 */
function seoaic_knowledge_bases_html() {
    include_once( SEOAIC_DIR . 'inc/view/seoaic-knowledge-bases-view.php' );
}

function seoaic_options_page_html( $page = 'seoaic' ) {

    if ( ! current_user_can( 'edit_others_posts' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'seoaic_messages', 'seoaic_message', __( 'Settings Saved', 'mdsf' ), 'updated' );
    }

    settings_errors( 'seoaic_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( $page );
            do_settings_sections( $page );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}

function seoaic_add_plugin_capability() {
    global $SEOAIC_OPTIONS;
    $capability = 'seoaic_edit_plugin';
    wp_roles()->get_role('administrator')->add_cap($capability);

    if (isset($SEOAIC_OPTIONS['seoaic_access_role'])) {
        $all_roles = wp_roles()->roles;
        $allowed_roles = $SEOAIC_OPTIONS['seoaic_access_role'];

        foreach ($all_roles as $role_name => $role_info) {
            if ($role_name === 'administrator') {
                continue;
            }
            $role = get_role($role_name);
            if (in_array($role_name, $allowed_roles)) {
                $role->add_cap($capability);
            } else {
                $role->remove_cap($capability);
            }
        }
    }
}
add_action('init', 'seoaic_add_plugin_capability');