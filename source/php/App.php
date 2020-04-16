<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Options as Options;

class App
{

    public function __construct()
    {
        //Warn for missing api-keys, end execution
        if(empty(Options::applicationId())||empty(Options::apiKey())) {
            add_action('admin_notices', array($this, 'displayAdminNotice'));
            return; 
        }
        
        //Run plugin
        new \AlgoliaIndex\Index(); 
        new \AlgoliaIndex\Search();
        new \AlgoliaIndex\Settings();

        //Cli api (bulk actions)
        if(defined('WP_CLI') && WP_CLI == true) {
            new \AlgoliaIndex\Bulk();
        }
    }

    /**
     * Throw warning for undefined constants. 
     *
     * @return void
     */
    public function displayAdminNotice(){
        echo '<div class="notice notice-error"><p>'; 
        _e("Required constants undefined, algolia will not run before setting ALGOLIAINDEX_APPLICATION_ID and ALGOLIAINDEX_API_KEY constants. Optionally, you may set ALGOLIAINDEX_INDEX_NAME otherwise it will be autogenerated.", 'algolia-index');
        echo '</p></div>';
    }
    
}
