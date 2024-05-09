<?php

namespace SEOAIC;

class SeoaicKnowledgeBase
{

    private $seoaic;

    public function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;
        add_action('wp_ajax_seoaic_get_knowledge_base_list', [$this, 'get_knowledge_base_list']);
        add_action('wp_ajax_seoaic_create_knowledge_base', [$this, 'create_knowledge_base']);
        add_action('wp_ajax_seoaic_save_knowledge_base', [$this, 'save_knowledge_base']);
        add_action('wp_ajax_seoaic_remove_knowledge_base', [$this, 'remove_knowledge_base']);
        add_action('wp_ajax_seoaic_save_knowledge_base_data_sources', [$this, 'save_knowledge_base_data_sources']);
        add_action('wp_ajax_seoaic_get_data_sources_list', [$this, 'get_data_sources_list']);

        add_action('wp_ajax_seoaic_rerun_data_source', [$this, 'rerun_data_source']);
        add_action('wp_ajax_seoaic_remove_data_source', [$this, 'remove_data_source']);
        add_action('wp_ajax_seoaic_train_data_source', [$this, 'train_data_source']);
        add_action('wp_ajax_seoaic_train_knowledge_base', [$this, 'train_knowledge_base']);
        add_action('wp_ajax_seoaic_rerun_knowledge_base', [$this, 'rerun_knowledge_base']);
        add_action('wp_ajax_seoaic_get_crawled_pages', [$this, 'get_crawled_pages']);

        add_action('wp_ajax_seoaic_rerun_sources', [$this, 'rerun_sources']);
        add_action('wp_ajax_seoaic_remove_sources', [$this, 'remove_sources']);
    }

    public function get_knowledge_base_list()
    {
        $data = [
            'version' => 5
        ];

        $result = $this->seoaic->curl->init('api/knowledge/get-list', $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function create_knowledge_base()
    {
        $name = sanitize_text_field(trim($_POST['formData']['knowledge-base-name']));
        $description = $_POST['formData']['knowledge-base-description'];
        $recurrenceDays = $_POST['formData']['knowledge-base-rescan-days'];

        if (empty($name)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'Name can`t be empty!',
            ]);
        }

        $data = [
            'name' => $name,
            'description' => $description,
            'recurrenceDays' => $recurrenceDays,
        ];

        $result = $this->seoaic->curl->init('api/knowledge/new', $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function save_knowledge_base()
    {
        $name = sanitize_text_field(trim($_POST['formData']['knowledge-base-name']));
        $description =  sanitize_text_field($_POST['formData']['knowledge-base-description']);
        $id = $_POST['knowledge_id'];

        if (empty($name)) {
            wp_send_json([
                'status' => 'alert',
                'message' => 'Name can`t be empty!',
            ]);
        }

        if (empty($id)) {
            wp_send_json([
                'status' => 'alert',
                'message' => 'ID is missing!',
            ]);
        }

        $data = [
            'name' => $name,
            'description' => $description,
        ];

        $result = $this->seoaic->curl->init('api/knowledge/' . $id .'/update-info', $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function remove_knowledge_base()
    {
        $id = $_REQUEST['item_id'];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID can`t be empty!',
            ]);
        }

        $data = [
            'version' => 5
        ];

        $result = $this->seoaic->curl->init('api/knowledge/' . $id . '/delete', $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function save_knowledge_base_data_sources()
    {
        $data = $_POST['dataSources'];
        foreach ($data['sources'] as $key => &$source) {
            $data['sources'][$key]['data'] = stripslashes(sanitize_textarea_field($source['data']));
        }

        $id = $_POST['data_id'];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'You have not created a Knowledge Base',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/add-data-source';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function get_data_sources_list()
    {
        $id = $_POST['item_id'];
        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $data = [
            'version' => 5
        ];

        $url = 'api/knowledge/' . $id . '/info';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function get_crawled_pages()
    {
        $id = $_POST['item_id'];
        $limit = $_POST['limit'];
        $page = $_POST['page'];
        $searchBy = sanitize_text_field($_POST['searchBy']);
        $status = sanitize_text_field($_POST['status']);

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $data = [
            'version' => 5,
            'limit' => $limit ? $limit : 100,
            'page' => $page ? $page : 1,
            'searchBy' => $searchBy ? $searchBy : '',
            'status' => $status ? $status : null,
        ];

        $url = 'api/knowledge/' . $id . '/get-sources-list';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function rerun_data_source()
    {
        $id = $_POST['item_id'];
        $dsId = $_POST['data_id'];
        $data = [
            'version' => 5
        ];

        $url = 'api/knowledge/' . $id . '/rerun/' . $dsId;

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function remove_data_source()
    {
        $id = $_POST['item_id'];
        $dsId = $_POST['data_id'];
        $data = [
            'version' => 5
        ];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/delete-data-source/' . $dsId;

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function train_data_source()
    {
        $id = $_POST['item_id'];
        $dsId = $_POST['data_id'];
        $data = [
            'version' => 5
        ];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/training/' . $dsId;

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function train_knowledge_base()
    {
        $id = $_POST['item_id'];
        $data = [
            'version' => 5
        ];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/training';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function rerun_knowledge_base()
    {
        $id = $_POST['item_id'];
        $data = [
            'version' => 5
        ];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/rerun';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function rerun_sources()
    {
        $id = $_POST['item_id'];
        $data = $_POST['ids'];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/rescan-source';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }

    public function remove_sources()
    {
        $id = $_POST['item_id'];
        $data = $_POST['ids'];

        if (empty($id)) {
            wp_send_json([
                'status' => 'error',
                'message' => 'ID is missing!',
            ]);
        }

        $url = 'api/knowledge/' . $id . '/delete-source';

        $result = $this->seoaic->curl->init($url, $data, true, true, true);

        wp_send_json(
            $result
        );
    }
}

