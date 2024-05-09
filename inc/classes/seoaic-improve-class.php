<?php

class SEOAIC_IMPROVE
{
    private $seoaic;

    function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;
        add_action('wp_ajax_seoaic_improve_post', [$this, 'improve_post']);
    }

    /**
     * Improve and save post via openai
     */
    public function improve_post()
    {
        global $SEOAIC_OPTIONS, $SEOAIC;

        $editor = $_REQUEST['data_editor'] ?? false;
        $title = $_REQUEST['title'] ?? false;
        $content = $_REQUEST['content'] ?? false;
        $improve_prompt = $_REQUEST['improve_prompt'] ?? false;
        $rollback = $_REQUEST['rollback'];
        $id = intval($_REQUEST['item_id']);

        if (empty($id)) {
            wp_die();
        }

        if ($rollback) {

            $rollback = get_post_meta($id, 'seoaic_rollback_content_improvement', true);

            $result = [
                'content' => [
                    'content' => $rollback
                ]
            ];

        } else {
            $data = [
                'title' => $title,
                'content' => $content,
                'improve_prompt' => $improve_prompt,
                'language' => $SEOAIC->multilang->get_selected_language(),
            ];

            $result = $this->seoaic->curl->init('api/ai/improve_post', $data, true, false, true);

        }

        wp_send_json($result);

        wp_die();
    }

}