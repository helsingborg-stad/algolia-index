<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Options as Options;

class Settings
{

    private $algolia_index_options;

    public function __construct()
    {
    //Local settings
        add_action('admin_menu', array( $this, 'addPluginPage'));
        add_action('admin_init', array( $this, 'pluginPageInit'));
    
    //Algolia settings
        add_action('admin_init', array($this, 'sendSearchableAttributes'));
    }
  
  /**
   * Send searchable attributes.
   *
   * @return void
   */
    public function sendSearchableAttributes()
    {

      //Limit to on submit settings form.
        if (!isset($_GET['sendAlgoliaSettings'])) {
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

      //Send settings
        Instance::getIndex()->setSettings(['searchableAttributes' => $searchableAttributes]);
    }

  /**
   * Register the plugins page
   *
   * @return void
   */
    public function addPluginPage()
    {
        add_options_page(
            __("Algolia Index", 'algolia-index'),
            __("Algolia Index", 'algolia-index'),
            'manage_options',
            'algolia-index',
            array( $this, 'algoliaIndexCreateAdminPage' )
        );
    }

  /**
   * View
   *
   * @return void
   */
    public function algoliaIndexCreateAdminPage()
    {
        ?>
      <div class="wrap">
        <h2><?php _e("Algolia Index", 'algolia-index'); ?></h2>
        <p><?php _e("Settings for indexing to algolia.", 'algolia-index'); ?></p>
        <form method="post" action="options.php?sendAlgoliaSettings=true">
          <?php
            settings_fields('algolia_index_option_group');
            do_settings_sections('algolia-index-admin');
            submit_button();
            ?>
        </form>
      </div>
        <?php
    }

  /**
   * Register settings
   *
   * @return void
   */
    public function pluginPageInit()
    {
    
        register_setting(
            'algolia_index_option_group',
            'algolia_index',
            array($this, 'algoliaIndexSanitize')
        );
    
        add_settings_section(
            'algolia_index_setting_section',
            'Settings',
            array( $this, 'algoliaSettingsSectionCallback' ),
            'algolia-index-admin'
        );

        add_settings_field(
            'application_id',
            'Application ID<small style="display:block; font-weight: normal;">May be overridden by ALGOLIAINDEX_APPLICATION_ID constant<small>',
            array( $this, 'algoliaApplicationIdCallback' ),
            'algolia-index-admin',
            'algolia_index_setting_section'
        );

        add_settings_field(
            'api_key', // id
            'API Key<small style="display:block; font-weight: normal;">May be overridden by ALGOLIAINDEX_API_KEY constant</small>', // title
            array( $this, 'algoliaApiKeyCallback' ),
            'algolia-index-admin',
            'algolia_index_setting_section'
        );

        add_settings_field(
            'index_name',
            'Index name<small style="display:block; font-weight: normal;">May be overridden by ALGOLIAINDEX_INDEX_NAME constant. Leave blank to create one for you.</small>',
            array( $this, 'algoliaIndexNameCallback' ),
            'algolia-index-admin',
            'algolia_index_setting_section'
        );
    
        add_settings_section(
            'algolia_index_summary_section',
            'Summary',
            array( $this, 'algoliaSettingsSummaryCallback' ),
            'algolia-index-admin'
        );
    }

  /**
   * Load option
   *
   * @return void
   */
    public function algoliaSettingsSectionCallback()
    {
        $this->algolia_index_options = get_option('algolia_index');
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
        echo '<tr><td style="min-width: 100px;"><strong>Application ID: </strong></td><td>' . Options::applicationId() .'</td></tr>';
        echo '<tr><td><strong>API Key: </strong></td><td>' . Options::apiKey() .'</td></tr>';
        echo '<tr><td><strong>Index Name: </strong></td><td>' . Options::indexName() .'</td></tr>';
        echo '</table>';
    }

  /**
   * Sanitize
   *
   * @param  array $input             Unsanitized values
   * @return array $sanitary_values   Sanitized values
   */
    public function algoliaIndexSanitize($input)
    {
        $sanitary_values = array();
    
        if (isset($input['application_id'])) {
            $sanitary_values['application_id'] = sanitize_text_field($input['application_id']);
        }

        if (isset($input['api_key'])) {
            $sanitary_values['api_key'] = sanitize_text_field($input['api_key']);
        }

        if (isset($input['index_name'])) {
            $sanitary_values['index_name'] = sanitize_text_field($input['index_name']);
        }

        return $sanitary_values;
    }

  /**
   * Print field, with data.
   *
   * @return void
   */
    public function algoliaApplicationIdCallback()
    {
        printf(
            '<input class="regular-text" type="text" name="algolia_index[application_id]" id="application_id" value="%s">',
            isset($this->algolia_index_options['application_id']) ? esc_attr($this->algolia_index_options['application_id']) : ''
        );
    }
  
  /**
   * Print field, with data.
   *
   * @return void
   */
    public function algoliaApiKeyCallback()
    {
        printf(
            '<input class="regular-text" type="text" name="algolia_index[api_key]" id="api_key" value="%s">',
            isset($this->algolia_index_options['api_key']) ? esc_attr($this->algolia_index_options['api_key']) : ''
        );
    }

  /**
   * Print field, with data.
   *
   * @return void
   */
    public function algoliaIndexNameCallback()
    {
        printf(
            '<input class="regular-text" type="text" name="algolia_index[index_name]" id="index_name" value="%s">',
            isset($this->algolia_index_options['index_name']) ? esc_attr($this->algolia_index_options['index_name']) : ''
        );
    }
}