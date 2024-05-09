<?php
if (!current_user_can('seoaic_edit_plugin')) {
    return;
}

if (isset($_GET['settings-updated'])) {
    add_settings_error('seoaic_messages', 'seoaic_message', __('Settings Saved', 'mdsf'), 'updated');
}

settings_errors('seoaic_messages');

global $SEOAIC, $SEOAIC_OPTIONS;
$ideas = get_posts([
    'numberposts' => -1,
    'post_type' => 'seoaic-post',
    'post_status' => 'seoaic-idea',
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'seoaic_idea_postdate',
            'value' => [''],
            'compare' => 'NOT IN'
        ]
    ],
    'orderby' => 'meta_value',
    'order' => 'ASC',
    //'order' => 'DESC',
]);

$args = [
    'numberposts' => -1,
    'post_type' => 'seoaic-post',
    'post_status' => 'seoaic-idea',
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'seoaic_idea_postdate',
            'value' => [''],
            'compare' => 'NOT IN'
        ]
    ],
    'orderby' => 'meta_value',
    'order' => 'ASC',
];

$query = new \WP_Query( $args );

$d = [];
foreach ($ideas as $date) {
$d[] = date("m/d/Y", strtotime(get_post_meta($date->ID, 'seoaic_idea_postdate', true)));
}

//print_r($d);

$ds = $d ? min($d) : '';
$dl = $d ? max($d) : '';
//print_r($ds);
//print_r($dl);
?>
<div id="seoaic-admin-container" class="wrap">
    <h1 id="seoaic-admin-title">
        <?php echo seoai_get_logo('logo.svg'); ?>
        <span>
            <?php echo esc_html(get_admin_page_title()); ?>
        </span>
    </h1>
    <?=$SEOAIC->get_background_process_loader();?>
    <div id="seoaic-admin-body" class="seoaic-with-loader sch-bg">
        <form class="top">
            <h2><span><?php echo $query->found_posts ?></span> scheduled posts</h2>
            <div class="search">
                <input type="text" placeholder="Search...">
                <button type="submit" data-search="1" data-action="seoaic_filter_schedule">
            </div>
            <div class="sort-dated">
                <input type="text" id="seoaic-from-date" readonly="readonly" value="<?php echo $ds;?>" data-default="<?php echo $ds;?>">
                <span class="sep"></span>
                <input type="text" id="seoaic-to-date" readonly="readonly" value="<?php echo $dl;?>" data-default="<?php echo $dl;?>">
                <div class="actions">
                    <div class="clear" data-clear-date="1" data-action="seoaic_filter_schedule"></div>
                    <div class="complete" data-set-date="1" data-action="seoaic_filter_schedule"></div>
                </div>
            </div>
            <div class="sort">
                <a href="#" data-order="ASC" data-current="ASC" data-action="seoaic_filter_schedule">Sort by date</a>
            </div>
        </form>

        <div class="lds-dual-ring"></div>
        <div class="seoaic-table-idea-box">

            <?php foreach ($ideas as $idea) : ?>
                <?php
                //print_r(get_post_meta($idea->ID, 'seoaic_idea_postdate', true));
                $idea_time = strtotime(get_post_meta($idea->ID, 'seoaic_idea_postdate', true));
                $idea_post_date = date("F j, Y, g:i a", $idea_time);
                ?>
                <div class="post">
                    <div class="content">
                        <div class="title"><?= $idea->post_title; ?></div>
                    </div>

                    <div class="seoaic-change-posting-idea-date-td">
                        <input id="seoaic-posting-idea-date-checkbox-<?= $idea->ID ?>"
                               class="seoaic-posting-idea-date-checkbox" name="seoaic-posting-idea-date-checkbox"
                               type="checkbox">
                        <label for="seoaic-posting-idea-date-checkbox-<?= $idea->ID ?>">
                            <span class="seoaic-posting-idea-date-string"><?= $idea_post_date; ?></span>
                            <span class="dashicons dashicons-edit"></span>
                        </label>
                        <button title="Remove idea from posting schedule" type="button" data-post-id="<?= $idea->ID ?>"
                                class="seoaic-remove-idea-post-date-button"
                                data-action="seoaic_remove_idea_posting_date">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                        <input type="datetime-local" class="seoaic-change-posting-idea-date"
                               name="seoaic-change-posting-idea-date" data-value="<?= date("Y-m-d\TH:i", $idea_time) ?>"
                               value="<?= date("Y-m-d\TH:i", $idea_time) ?>">
                        <button title="Change post date" type="button" data-post-id="<?= $idea->ID ?>"
                                class="seoaic-change-idea-post-date-button button button-success"
                                data-action="seoaic_save_content_idea">
                            <span class="dashicons dashicons-saved"></span>
                        </button>
                        <button title="Close" type="button"
                                class="seoaic-change-idea-post-date-button-close button button-danger"
                                style="display: none !important;">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>