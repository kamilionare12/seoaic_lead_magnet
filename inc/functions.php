<?php

/**
 * Get backend server url
 *
 * @param string $uri
 * @return string
 */
function seoai_get_backend_url( $uri = '' ) {
    $url = defined( 'SEOAIC_BACK_URL' ) && SEOAIC_BACK_URL ? untrailingslashit( SEOAIC_BACK_URL ) : '';

    if ( $uri ) {
        $uri = ltrim( untrailingslashit( $uri ), '/' );
        $url .= '/' . $uri;
    }

    return trim($url);
}

function seoaic_get_post_types() {
    $post_types = get_post_types(['public' => true]);
    unset($post_types['attachment']);
    sort($post_types);
    return $post_types;
}

function seoai_get_logo( $image ) {
    $image = '<img src="' . SEOAIC_URL . 'assets/img/' . $image . '">';

    return $image;
}

function seoaic_get_languages () {
    $languages = [
        'Arabic',
        'Bulgarian',
        'Croatian',
        'Czech',
        'Danish',
        'Dutch',
        'English',
        'Estonian',
        'Finnish',
        'French',
        'German',
        'Greek',
        'Hungarian',
        'Irish',
        'Italian',
        'Japanese',
        'Lithuanian',
        'Norwegian (Bokmål)',
        'Polish',
        'Portuguese',
        'Romanian',
        'Russian',
        'Spanish',
        'Swedish',
        'Turkish'
    ];
    sort($languages);
    return $languages;
}

function seoaic_get_writing_styles () {
    $styles = [
        'Default',
        'Friendly',
        'Professional',
        'Educational',
        'Formal',
        'Easily approachable',
    ];
    return $styles;
}

function seoaic_get_locations () {
    $locations = [
        "Afghanistan",
        "Albania",
        "Algeria",
        "Andorra",
        "Angola",
        "Antigua and Barbuda",
        "Argentina",
        "Armenia",
        "Austria",
        "Azerbaijan",
        "Bahrain",
        "Bangladesh",
        "Barbados",
        "Belarus",
        "Belgium",
        "Belize",
        "Benin",
        "Bhutan",
        "Bolivia",
        "Bosnia and Herzegovina",
        "Botswana",
        "Brazil",
        "Brunei",
        "Bulgaria",
        "Burkina Faso",
        "Burundi",
        "Cabo Verde",
        "Cambodia",
        "Cameroon",
        "Canada",
        "Central African Republic",
        "Chad",
        "Channel Islands",
        "Chile",
        "China",
        "Colombia",
        "Comoros",
        "Congo",
        "Costa Rica",
        "Côte d'Ivoire",
        "Croatia",
        "Cuba",
        "Cyprus",
        "Czech Republic",
        "Denmark",
        "Djibouti",
        "Dominica",
        "Dominican Republic",
        "DR Congo",
        "Ecuador",
        "Egypt",
        "El Salvador",
        "Equatorial Guinea",
        "Eritrea",
        "Estonia",
        "Eswatini",
        "Ethiopia",
        "Faeroe Islands",
        "Finland",
        "France",
        "French Guiana",
        "Gabon",
        "Gambia",
        "Georgia",
        "Germany",
        "Ghana",
        "Gibraltar",
        "Greece",
        "Grenada",
        "Guatemala",
        "Guinea",
        "Guinea-Bissau",
        "Guyana",
        "Haiti",
        "Holy See",
        "Honduras",
        "Hong Kong",
        "Hungary",
        "Iceland",
        "India",
        "Indonesia",
        "Iran",
        "Iraq",
        "Ireland",
        "Isle of Man",
        "Israel",
        "Italy",
        "Jamaica",
        "Japan",
        "Jordan",
        "Kazakhstan",
        "Kenya",
        "Kuwait",
        "Kyrgyzstan",
        "Laos",
        "Latvia",
        "Lebanon",
        "Lesotho",
        "Liberia",
        "Libya",
        "Liechtenstein",
        "Lithuania",
        "Luxembourg",
        "Macao",
        "Madagascar",
        "Malawi",
        "Malaysia",
        "Maldives",
        "Mali",
        "Malta",
        "Mauritania",
        "Mauritius",
        "Mayotte",
        "Mexico",
        "Moldova",
        "Monaco",
        "Mongolia",
        "Montenegro",
        "Morocco",
        "Mozambique",
        "Myanmar",
        "Namibia",
        "Nepal",
        "Netherlands",
        "Nicaragua",
        "Niger",
        "Nigeria",
        "North Korea",
        "North Macedonia",
        "Norway",
        "Oman",
        "Pakistan",
        "Panama",
        "Paraguay",
        "Peru",
        "Philippines",
        "Poland",
        "Portugal",
        "Qatar",
        "Réunion",
        "Romania",
        "Russia",
        "Rwanda",
        "Saint Helena",
        "Saint Kitts and Nevis",
        "Saint Lucia",
        "Saint Vincent and the Grenadines",
        "San Marino",
        "Sao Tome & Principe",
        "Saudi Arabia",
        "Senegal",
        "Serbia",
        "Seychelles",
        "Sierra Leone",
        "Singapore",
        "Slovakia",
        "Slovenia",
        "Somalia",
        "South Africa",
        "South Korea",
        "South Sudan",
        "Spain",
        "Sri Lanka",
        "State of Palestine",
        "Sudan",
        "Suriname",
        "Sweden",
        "Switzerland",
        "Syria",
        "Taiwan",
        "Tajikistan",
        "Tanzania",
        "Thailand",
        "The Bahamas",
        "Timor-Leste",
        "Togo",
        "Trinidad and Tobago",
        "Tunisia",
        "Turkiye",
        "Turkmenistan",
        "Uganda",
        "Ukraine",
        "United Arab Emirates",
        "United Kingdom",
        "United States",
        "Uruguay",
        "Uzbekistan",
        "Venezuela",
        "Vietnam",
        "Western Sahara",
        "Yemen",
        "Zambia",
        "Zimbabwe"
    ];
    return $locations;
}

// Available countries Google ads
function seoaic_google_ads_available_locations() {
    $locations = [
        "Algeria",
        "Angola",
        "Azerbaijan",
        "Argentina",
        "Australia",
        "Austria",
        "Bahrain",
        "Bangladesh",
        "Armenia",
        "Belgium",
        "Bolivia",
        "Brazil",
        "Bulgaria",
        "Myanmar (Burma)",
        "Cambodia",
        "Cameroon",
        "Canada",
        "Sri Lanka",
        "Chile",
        "Taiwan",
        "Colombia",
        "Costa Rica",
        "Croatia",
        "Cyprus",
        "Czechia",
        "Denmark",
        "Ecuador",
        "El Salvador",
        "Estonia",
        "Finland",
        "France",
        "Germany",
        "Ghana",
        "Greece",
        "Guatemala",
        "Hong Kong",
        "Hungary",
        "India",
        "Indonesia",
        "Ireland",
        "Israel",
        "Italy",
        "Cote d'Ivoire",
        "Japan",
        "Kazakhstan",
        "Jordan",
        "Kenya",
        "South Korea",
        "Latvia",
        "Lithuania",
        "Malaysia",
        "Malta",
        "Mexico",
        "Morocco",
        "Netherlands",
        "New Zealand",
        "Nicaragua",
        "Nigeria",
        "Norway",
        "Pakistan",
        "Panama",
        "Paraguay",
        "Peru",
        "Philippines",
        "Poland",
        "Portugal",
        "Romania",
        "Saudi Arabia",
        "Senegal",
        "Serbia",
        "Singapore",
        "Slovakia",
        "Vietnam",
        "Slovenia",
        "South Africa",
        "Spain",
        "Sweden",
        "Switzerland",
        "Thailand",
        "United Arab Emirates",
        "Tunisia",
        "Turkiye",
        "Ukraine",
        "North Macedonia",
        "Egypt",
        "United Kingdom",
        "United States",
        "Burkina Faso",
        "Uruguay",
        "Venezuela",
    ];
    return $locations;
}

function seoaic_get_preferred_language($location)
{
    switch ($location) {
        case "Bulgaria":
            $preferred_language = "Bulgarian";
            break;
        case "Croatia":
            $preferred_language = "Croatian";
            break;
        case "Czech Republic":
            $preferred_language = "Czech";
            break;
        case "Denmark":
            $preferred_language = "Danish";
            break;
        case "Netherlands":
            $preferred_language = "Dutch";
            break;
        case "Norway":
            $preferred_language = "Norwegian (Bokmål)";
            break;
        case "Estonia":
            $preferred_language = "Estonian";
            break;
        case "Finland":
            $preferred_language = "Finnish";
            break;
        case "France":
            $preferred_language = "French";
            break;
        case "Germany":
            $preferred_language = "German";
            break;
        case "Greece":
            $preferred_language = "Greek";
            break;
        case "Hungary":
            $preferred_language = "Hungarian";
            break;
        case "Ireland":
            $preferred_language = "Irish";
            break;
        case "Italy":
            $preferred_language = "Italian";
            break;
        case "Japan":
            $preferred_language = "Japanese";
            break;
        case "Lithuania":
            $preferred_language = "Lithuanian";
            break;
        case "Poland":
            $preferred_language = "Polish";
            break;
        case "Portugal":
            $preferred_language = "Portuguese";
            break;
        case "Romania":
            $preferred_language = "Romanian";
            break;
        case "Russia":
            $preferred_language = "Russian";
            break;
        case "Spain":
            $preferred_language = "Spanish";
            break;
        case "Sweden":
            $preferred_language = "Swedish";
            break;
        case "Turkiye":
            $preferred_language = "Turkish";
            break;
        default:
            $preferred_language = "English";
    }

    return $preferred_language;
}

function seoaic_get_image_generators () {
    return [
        'gpt' => 'GPT',
        'clipdrop' => 'Clipdrop',
        'no_image' => 'No image',
    ];
}

function seoaic_get_default_image_generator () {
    global $SEOAIC_OPTIONS;
    if ( !empty($SEOAIC_OPTIONS['seoaic_image_generator']) ) {
        return $SEOAIC_OPTIONS['seoaic_image_generator'];
    }

    return 'no_image';
}

function seoaic_get_categories ( $post_type = 'post', $id = 0 ) {
    global $SEOAIC, $SEOAIC_OPTIONS;

    $terms = $SEOAIC->multilang->get_terms( $post_type );
    $termsArray = [];
    foreach ( $terms as $term ) {
        if ( !empty($SEOAIC_OPTIONS['seoaic-exclude-taxonomy']) && in_array($term->taxonomy, $SEOAIC_OPTIONS['seoaic-exclude-taxonomy']) )
            continue;
        $termsArray[] = $term;
    }

    $checkboxes = '<div class="toggle-choose-role">
                       <div class="checkbox-list">';

    if ( !empty($id) ) {
        $idea_content = get_post_meta($id, 'seoaic_idea_content', true);
        if ( empty($idea_content) ) {
            $choosen_categories = $SEOAIC_OPTIONS['seoaic_default_category'] ?? [];
        } else {
            $idea_content = json_decode($idea_content, true);
            $choosen_categories = $idea_content['idea_category'] ?? [];
        }
    } else {
        $choosen_categories = $SEOAIC_OPTIONS['seoaic_default_category'] ?? [];
    }

    usort($termsArray, 'seoaic_compare_terms_taxanomy');

    $last_taxonomy = '';
    foreach ( $termsArray as $term ) {
        if ( $term->slug === 'uncategorized' ) continue;

        if ( $last_taxonomy !== $term->taxonomy ) {

            $taxonomy = get_taxonomy( $term->taxonomy );
            $last_taxonomy = $taxonomy->name;

            $checkboxes .= '<div class="checkbox-wrapper-mc-group">' . $taxonomy->label . '</div>';
        }

        $id = $term->term_id . '-' . rand(1000, 5000);
        $checkboxes .= '
        <div class="checkbox-wrapper-mc">
            <input id="seoaic-default-category-' . $id . '" class="seoaic-form-item"
                    name="seoaic_default_category[]"
                    type="checkbox"
                    value="' . $term->term_id . '"';


        if ( !empty($choosen_categories) && is_array($choosen_categories) && in_array($term->term_id, $choosen_categories ) ) {
            $checkboxes .= ' checked ';
        }

        $checkboxes .= '/>
            <label for="seoaic-default-category-' . $id . '" class="check">
                <svg width="18px" height="18px" viewBox="0 0 18 18">
                    <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                    <polyline points="1 9 7 14 15 4"></polyline>
                </svg>
                <span>' . $term->name . '</span>
            </label>
        </div>';

    }

    if ( $last_taxonomy === '' ) {
        $checkboxes .= 'No terms for this post type';
    }

    $checkboxes .= '</div></div>';

    return $checkboxes;
}

function seoaic_get_prompt_template_types () {
    return [
        'default' => 'Default',
        'location' => 'Location based content',
        'educational' => 'Educational content',
        'how_to' => 'How-to content',
        'service' => 'Service introduction content',
        'also_asked' => 'Also asked content',
        'news' => 'News content',
        'microkeywords' => 'Microkeywords content'
    ];
}

add_action('pre_get_posts', 'seoaic_exclude_posts', 999, 1);
function seoaic_exclude_posts(WP_Query $query)
{
    global $SEOAIC_OPTIONS;
    $q_post_type = $query->get('post_type');

    if (seoaic_is_post_type_invalid($q_post_type)) {
        return;
    }

    if (
        (!is_admin() || (is_admin() && wp_doing_ajax()))
        && !((strpos($_SERVER['REQUEST_URI'], 'sitemap') !== false)
        && (strpos($_SERVER['REQUEST_URI'], '.xml') !== false))
        && (!empty($SEOAIC_OPTIONS['seoaic_hide_posts']))
        && !$query->is_feed()
    ) {
        //Hide from ajax responses
        if (is_admin() && wp_doing_ajax() && (strpos($_REQUEST['action'], 'seoaic') !== 0)) {
            seoaic_modify_hiding_query($query);
            return;
        }

        if (is_singular() && $query->is_main_query()) {
            return;
        }

        seoaic_modify_hiding_query($query);
    }
}

function seoaic_is_post_type_invalid($post_type)
{
    $invalid_post_types = [
        'nav_menu_item', 'x-portfolio', 'gwp_campaign', 'product', 'event',
        'cs_header', 'cs_footer', 'cs_layout_archive',
        'cs_layout_single', 'cs_layout_archive_wc', 'cs_layout_single_wc',
        'eeco-volume-discount', 'eeco-volume-discount-campaign', 'eeco-volume-discount-rule',
        'eeco-volume-discount-rule-group', 'eeco-volume-discount-rule-product', 'eeco-vd'
    ];
    return in_array($post_type, $invalid_post_types);
}

function seoaic_modify_hiding_query(WP_Query $query)
{
    $meta_query = $query->get('meta_query');

    if (seoaic_is_skip_hiding($meta_query)) {
        return;
    }

    $updated_query = [
        'relation' => 'OR',
        [
            'key' => 'seoaic_posted',
            'compare' => 'NOT EXISTS'
        ],
        [
            'key' => 'seoaic_visible_post',
            'compare' => '=',
            'value' => 1
        ]
    ];

    if (!$meta_query) {
        $query->set('meta_query', $updated_query);
    } else {
        $query->set('meta_query', [
            'relation' => 'AND',
            $updated_query,
            $meta_query
        ]);
    }
}

function seoaic_is_skip_hiding($meta_query)
{
    $isSkip = false;
    if ($meta_query) {
        array_map(function($item) use (&$isSkip) {
            if (is_array($item) && array_key_exists('key', $item) && $item['key'] === 'seoaic_posted') {
                $isSkip = true;
            }
        }, $meta_query);
    }
    return $isSkip;
}

function seoaic_additional_related_posts($content) {
    global $SEOAIC_OPTIONS;
    $post_id = get_the_ID();

    if (!is_admin() && !empty($SEOAIC_OPTIONS['seoaic_show_related_articles'])) {
        $meta_value = get_post_meta( $post_id, 'seoaic_posted', true );

        $query = new WP_Query( [
            'posts_per_page'    => $SEOAIC_OPTIONS['seoaic_related_articles_count'],
            'post__not_in'      => array( $post_id ),
            'post_type'         => 'any',
            'orderby'           => 'rand',
            'meta_query' => [
                [
                    'key' => 'seoaic_posted',
                    'value' => 1,
                    'compare' => '='
                ]
            ]
        ] );

        if ( $meta_value && $query->have_posts() ) {
            $content .= '<h2>' . __('Related Articles', 'seoaic') . '</h2>';
            $content .= '<ul>';
            while ( $query->have_posts() ) {
                $query->the_post();
                $content .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            }
            wp_reset_postdata();
            $content .= '</ul>';
        }
    }

    return $content;
}
add_filter('the_content', 'seoaic_additional_related_posts', 100, 1);


function seoaic_ssl_verifypeer () {
    global $SEOAIC_OPTIONS;

    $ssl_verifypeer = false;

    if ( !empty($SEOAIC_OPTIONS['seoaic_ssl_verifypeer']) ) {
        $ssl_verifypeer = true;
    }

    return $ssl_verifypeer;
}

function seoaic_compare_terms_taxanomy ($a, $b) {
    return strnatcmp($a->taxonomy, $b->taxonomy);
}


/**
 * Get all registered taxonomies.
 * @return array
 */
function seoaic_get_taxonomies_checkboxes () {
    global $SEOAIC_OPTIONS;

    $post_types = get_post_types(['public' => true], 'objects');

    $checkboxes = '<div class="toggle-choose-role">
                       <div class="checkbox-list">';
    foreach ( $post_types as $key => $post_type ) {
        $taxonomies = get_object_taxonomies(['post_type' => $key]);
        $taxonomiesArray = [];
        foreach ( $taxonomies as $taxonomy) {

            if ( $taxonomy === 'language' ) continue;
            if ( $taxonomy === 'post_translations' ) continue;

            $taxonomiesArray[] = $taxonomy;
        }

        if ( count($taxonomiesArray) === 0 ) continue;

        $checkboxes .= '<div class="checkbox-wrapper-mc-group">' . $post_type->label . '</div>';

        foreach ( $taxonomiesArray as $taxonomy ) {

            $taxonomy = get_taxonomy( $taxonomy );

            $checkboxes .= '
            <div class="checkbox-wrapper-mc">
                <input id="seoaic-exclude-taxonomy-' . $taxonomy->name . '" class="seoaic-form-item"
                        name="seoaic-exclude-taxonomy[]"
                        type="checkbox"
                        value="' . $taxonomy->name . '"';

            if ( !empty($SEOAIC_OPTIONS['seoaic-exclude-taxonomy']) && in_array($taxonomy->name, $SEOAIC_OPTIONS['seoaic-exclude-taxonomy'] ) ) {
                $checkboxes .= ' checked ';
            }

            $checkboxes .= '/>
                <label for="seoaic-exclude-taxonomy-' . $taxonomy->name . '" class="check">
                    <svg width="18px" height="18px" viewBox="0 0 18 18">
                        <path d="M1,9 L1,3.5 C1,2 2,1 3.5,1 L14.5,1 C16,1 17,2 17,3.5 L17,14.5 C17,16 16,17 14.5,17 L3.5,17 C2,17 1,16 1,14.5 L1,9 Z"></path>
                        <polyline points="1 9 7 14 15 4"></polyline>
                    </svg>
                    <span>' . $taxonomy->label . '</span>
                </label>
            </div>';

        }
    }

    $checkboxes .= '</div></div>';
    return $checkboxes;
}

add_action('init', 'seoaic_load_textdomain');

function seoaic_load_textdomain()
{
    load_plugin_textdomain(
        'seoaic',
        false,
        dirname( plugin_basename( SEOAIC_FILE ) ) . '/languages'
    );
}