<?php

class SEOAIC_FRAMES
{
    private $seoaic;
    function __construct ( $_seoaic )
    {
        $this->seoaic = $_seoaic;

        add_action('wp_ajax_seoaic_generate_skeleton', [$this, 'generate']);
    }

    /**
     * Get (generate from server, using openai) post skeleton
     *
     * @param int $id
     * @param bool $return
     */
    public function generate($id = 0, $return = false) {
        global $SEOAIC_OPTIONS;

        $id = !empty($id) ? $id : intval($_REQUEST['item_id']);

        $lang = $this->seoaic->multilang->get_post_language($id);

        $name = !empty($SEOAIC_OPTIONS['seoaic_business_name']) ? $SEOAIC_OPTIONS['seoaic_business_name'] : get_option('blogname', true);
        $industry = !empty($SEOAIC_OPTIONS['seoaic_industry']) ? " on the industry of " . $SEOAIC_OPTIONS['seoaic_industry'] : '';
        $desc = !empty($SEOAIC_OPTIONS['seoaic_business_description']) ? $SEOAIC_OPTIONS['seoaic_business_description'] : get_option('blogdescription', true);

        //$editorFrame = $_REQUEST['data_editor'];
        $title = isset($_REQUEST['get_title']) ? $_REQUEST['get_title'] : get_the_title($id);
        $keys = get_post_meta( $id, '_idea_keywords_data', true );
        $prompt = get_post_meta( $id, '_idea_prompt_data', true );
        $title = $title ? $title : get_the_title($id);

        $data = [
            'idea' => $title,
            'language' => $lang,
            'idea_prompt' => $prompt ? $prompt : '',
            'idea_keys' => is_array($keys) ? implode(',', $keys) : $keys,
            'name' => $name,
            'desc' => $desc,
            'industry' => $industry,
            'subtitles_min' => !empty(intval($SEOAIC_OPTIONS['seoaic_subtitles_range_min'])) ? intval($SEOAIC_OPTIONS['seoaic_subtitles_range_min']) : 0,
            'subtitles_max' => !empty(intval($SEOAIC_OPTIONS['seoaic_subtitles_range_max'])) ? intval($SEOAIC_OPTIONS['seoaic_subtitles_range_max']) : 6,
        ];

        $result = $this->seoaic->curl->init('api/ai/idea_frame', $data, true, true, true);

        $content = !empty($result['content']) ? $result['content'] : '';

        $thumbnail_generator = !empty($SEOAIC_OPTIONS['seoaic_image_generator']) ? $SEOAIC_OPTIONS['seoaic_image_generator'] : 'no_image';
        $old_idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        if ( !empty($old_idea_content) ) {
            $old_idea_content = json_decode($old_idea_content, true);
            if ( !empty($old_idea_content['idea_thumbnail_generator']) ) {
                $thumbnail_generator = $old_idea_content['idea_thumbnail_generator'];
            }
        }

        if (!empty($content)) {
            $idea_content = [
                'idea_thumbnail' => isset($content['image-description']) ? str_replace('"', '\"', $content['image-description']) : '',
                'idea_thumbnail_generator' => str_replace('"', '\"', $thumbnail_generator),
                'idea_skeleton' => isset($content['subtitles']) ? str_replace('"', '\"', $content['subtitles']) : '',
                'idea_keywords' => isset($content['keywords']) ? str_replace('"', '\"', $content['keywords']) : '',
                'idea_description' => isset($content['description']) ? str_replace('"', '\"', $content['description']) : '',
            ];
        } else {
            $idea_content = [];
        }

        update_post_meta($id, 'seoaic_idea_content', json_encode($idea_content, JSON_UNESCAPED_UNICODE));

        if ($return) {
            return $idea_content;
        }

        wp_send_json([
            'status'  => 'success',
            'content' => [
                'idea_content'   => $idea_content,
                'idea_postdate'  => '',
                //'seoaic_credits' => $this->seoaic->get_api_credits(),
                'idea_id'        => $id,
                'idea_icons'     => $this->seoaic->ideas->get_idea_icons($id),
            ],
        ], null, JSON_UNESCAPED_UNICODE);
    }
}
