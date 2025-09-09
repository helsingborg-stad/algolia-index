<?php

namespace AlgoliaIndex\Admin;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Options as Options;

class Settings
{   
    private const OPTIONS_PAGE_SLUG = 'algolia-index-settings';
    private const ACF_TO_LEGACY_OPTIONS_MAP  = [
        'algolia_index_application_id'    => 'application_id',
        'algolia_index_api_key'           => 'api_key',
        'algolia_index_public_api_key'    => 'public_api_key',
        'algolia_index_index_name'        => 'index_name',
    ];


    public function __construct()
    {
        add_action('acf/init', [$this, 'registerOptionsPage']);
        add_action('acf/save_post', [$this, 'pushSettingsOnSave'], 20);
        
        // Migrate legacy options to ACF fields
        add_filter('acf/load_value', [$this, 'loadLegacyOptionValues'], 10, 3);
        add_filter('acf/update_value', [$this, 'clearLegacyOptionsOnSave'], 10, 4);
        
        // Trigger settings send for algolia provider
        add_action('AlgoliaIndex/SendSettings', array($this, 'sendAlgoliaSettings'));
    }


    public function registerOptionsPage()
    {
        if (function_exists('acf_add_options_sub_page')) {
            acf_add_options_sub_page([
                'page_title'        => __('Algolia Index', 'algolia-index'),
                'menu_title'        => __('Algolia Index', 'algolia-index'),
                'menu_slug'         => Settings::OPTIONS_PAGE_SLUG,
                'capability'        => 'manage_options',
                'parent_slug'       => 'options-general.php',
                'autoload'          => true,
            ]);
        }
    }

    public function loadLegacyOptionValues($value, $post_id, $field)
    {
        if (array_key_exists($field['name'], Settings::ACF_TO_LEGACY_OPTIONS_MAP)) {
            return !empty($value) ? $value : get_option('algolia_index')[Settings::ACF_TO_LEGACY_OPTIONS_MAP[$field['name']]] ?? '';
        }
        return $value;
    }

    public function clearLegacyOptionsOnSave($value, $post_id, $field, $original)
    {
        if (array_key_exists($field['name'], Settings::ACF_TO_LEGACY_OPTIONS_MAP)) {
            $legacyOptions = get_option('algolia_index', []);
            $legacyKey = Settings::ACF_TO_LEGACY_OPTIONS_MAP[$field['name']];
            if (isset($legacyOptions[$legacyKey])) {
                unset($legacyOptions[$legacyKey]);
                update_option('algolia_index', $legacyOptions);
            }
        }
    
        return $value;
    }

    public function pushSettingsOnSave($post_id)
    {
        if ($post_id !== 'options' || empty($_GET['page']) || ($_GET['page'] ?? '') !== Settings::OPTIONS_PAGE_SLUG) {
            return;
        }

        do_action('AlgoliaIndex/SendSettings'); 
    }

    /**
    * Send searchable attributes.
    *
    * @return void
    */
    public function sendAlgoliaSettings()
    {
        if (!Options::isConfigured()) {
            return;
        }

        // Define searchable attributes
        $searchableAttributes = apply_filters('AlgoliaIndex/SearchableAttributes', [
          'post_title',
          'post_excerpt',
          'content',
          'permalink',
          'tags',
          'categories'
        ]);

        //AttributesToSnippet
        $attributesToSnippet = apply_filters('AlgoliaIndex/AttributesToSnippet', [
          'content:40',
          'permalink:15',
          'post_title:7'
        ]);

        //Facetingattributes
        $attributesForFaceting = apply_filters('AlgoliaIndex/AttributesToSnippet', [
          'searchable(origin_site)'
        ]);

        //Send settings
        Instance::getIndex()->setSettings([
          'searchableAttributes'    => $searchableAttributes,
          'attributeForDistinct'    => 'partial_object_distinct_key',
          'distinct'                => true,
          'hitsPerPage'             => apply_filters('AlgoliaIndex/HitsPerPage', 15),
          'paginationLimitedTo'     => apply_filters('AlgoliaIndex/PaginationLimitedTo', 200),
          'attributesToSnippet'     => $attributesToSnippet,
          'snippetEllipsisText'     => apply_filters('AlgoliaIndex/SnippetEllipsisText', "..."),
          'attributesForFaceting'   => $attributesForFaceting,
          'indexLanguages'          => !empty(get_bloginfo('language')) ? [substr(get_bloginfo('language'), 0, 2)] : [],
          'removeWordsIfNoResults'  => 'allOptional'
        ]);
    }

    /**
    * Display summary
    *
    * @return void
    */
    public function algoliaSettingsSummaryCallback()
    {
        echo '<p>The following data is used by the algoia integration.</p>';
        echo '<table>';
        echo '
          <tr><td style="min-width: 100px;">
            <strong>Application ID: </strong>
          </td><td>' . Options::applicationId() .'</td></tr>';
        echo '<tr><td><strong>API Key: </strong></td><td>' . Options::apiKey() .'</td></tr>';
        echo '<tr><td><strong>Public API Key: </strong></td><td>' . Options::PublicApiKey() .'</td></tr>';
        echo '<tr><td><strong>Index Name: </strong></td><td>' . Options::indexName() .'</td></tr>';
        echo '</table>';
    }
}
