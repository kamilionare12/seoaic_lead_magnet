<?php

class SeoaicAuditData
{

    private $seoaic;

    public function __construct($_seoaic)
    {
        $this->seoaic = $_seoaic;
        add_action('wp_ajax_seoaic_get_seo_audit_data', [$this, 'get_seo_audit_data'], 2);
        add_action('wp_ajax_seoaic_create_seo_audit', [$this, 'create_seo_audit'], 2);
    }

    /**
     * Get seo audit data
     *
     */
    public function get_seo_audit_data()
    {
        global $SEOAIC_OPTIONS;

        $transient_key = 'seoaic_seo_audit_data';
    
        $transient = get_transient( $transient_key );
        if( false !== $transient ) {
            wp_send_json($transient);
        } else {
            //dump data cause backend require at least for something
            $data = [
                'version' => 5
            ];
            $result = $this->seoaic->curl->init('api/audit/latest', $data, true, true, true);

            if (!empty($result['auditInfoPages'])) {
                $previousAverage = $SEOAIC_OPTIONS['seoaic_average_score'];
                $currentAverage = $this->setAveragePerformance($result['auditInfoPages']);

                if ($previousAverage === null || $previousAverage !== $currentAverage) {
                    $this->seoaic->set_option('seoaic_average_score', $currentAverage);
                }

                set_transient( $transient_key, $result, MONTH_IN_SECONDS );
            }

            wp_send_json($result);
        }
    }

    /**
     * Request seo audit
     *
     */
    public function create_seo_audit()
    {
        //dump data cause backend require at least for something
        $data = [
            'id' => 5
        ];
        $result = $this->seoaic->curl->init('api/audit/create', $data, true, true, true);
        wp_send_json(
            $result
        );
    }

    private function setAveragePerformance($data) {
        $scores = array_column($data, 'onpage_score');
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
        return round($averageScore);
    }
}
