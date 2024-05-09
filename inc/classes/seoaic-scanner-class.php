<?php

use SEOAIC\SEOAIC_SETTINGS;

class SEOAIC_SCANNER
{
    private $seoaic;
    function __construct ( $_seoaic )
    {
        $this->seoaic = $_seoaic;

        add_action('wp_ajax_seoaic_scan_site', [$this, 'scan_site']);
    }

    /**
     * Ajax action - scannig site
     */
    public function scan_site()
    {
        $this->seoaic->set_option('seoaic_scanned', 1);

        $steps = 4;
        $step = !empty($_REQUEST['step']) ? intval($_REQUEST['step']) : 1;
        $step_content = !empty($_REQUEST['step_content']) ? sanitize_text_field($_REQUEST['step_content']) : '';

        switch ( $step ) {
            case 1:
                $step_content = $this->generate_company_description();
                break;
            case 2:
                $step_content = $this->seoaic->ideas->generate(true, 1);
                break;
            case 3:
                $step_content = $this->seoaic->frames->generate($step_content, true);
                break;
            case 4:
                $location = SEOAIC_SETTINGS::getLocation();
                $lang = $this->seoaic->multilang->getFirstLanguageByLocationName($location);
                $_REQUEST['location'] = $location;
                $_REQUEST['language'] = $lang['name'];
                $step_content = $this->seoaic->keywords->generate(10, '');
                break;
        }

        $data = [
            'status'        => 'scanning',
            'step_content'  => $step_content,
            'steps'         => $steps,
            'step'          => $step,
        ];

        if ( $steps === $step ) {
            $data['message'] = 'Your site has been scanned!';
        }

        wp_send_json( $data );
    }

    public function generate_company_description () {

        $data = [
            'site_url' => get_home_url()
        ];

        $result = $this->seoaic->curl->init('api/ai/scanning', $data, true, false, true);

        $description = sanitize_textarea_field($result['description']);
        $this->seoaic->set_option('seoaic_business_description', $description);

        return $description;
    }
}
