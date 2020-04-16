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
    public static function getIndex() {
        
        //Used cached instance
        if(!is_null(self::$_index)) {
            return self::$_index;
        }

        //Connect to account
        $client = \Algolia\AlgoliaSearch\SearchClient::create(
            Options::applicationId(),
            Options::apiKey(),
        );

        //Select index
        return self::$_index = $client->initIndex(Options::indexName());
    }
    
}
