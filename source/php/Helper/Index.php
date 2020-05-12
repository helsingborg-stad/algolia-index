<?php

namespace AlgoliaIndex\Helper;

use \AlgoliaIndex\Helper\Options as Options;

class Index
{
    private static $_index = null;

    /**
     * Get the index
     *
     * @return AlgoliaIndex
     */
    public static function getIndex()
    {
        
        //Used cached instance
        if (!is_null(self::$_index)) {
            return self::$_index;
        }

        //Setup config details with auth
        $config = \Algolia\AlgoliaSearch\Config\SearchConfig::create(
            Options::applicationId(),
            Options::apiKey()
        );

        //Tell algolia if running in cron and/or cli
        $config->setDefaultHeaders([
            'X-Client-Cli' => defined('WP_CLI_VERSION') ? WP_CLI_VERSION : 'false',
            'X-Client-Cron' => defined( 'DOING_CRON' ) ? 'true' : 'false',
            'X-Client-User' => get_current_user_id(),
        ]);

        //Init client with config
        $client = \Algolia\AlgoliaSearch\SearchClient::createWithConfig($config);

        //Select index
        return self::$_index = $client->initIndex(Options::indexName());
    }
}
