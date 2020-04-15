<?php

namespace AlgoliaIndex\Helper;

class Index
{

    private static $_index = null; 

    /**
     * Get the index
     *
     * @return void
     */
    public static function getIndex() {
        
        //Used cached instance
        if(!is_null(self::$_index)) {
            return self::$_index;
        }

        //Connect to account
        $client = \Algolia\AlgoliaSearch\SearchClient::create(
            ALGOLIAINDEX_APPLICATION_ID,
            ALGOLIAINDEX_API_KEY
        );
        
        //Select index
        return self::$_index = $client->initIndex('testindex');
        
    }
    
}
