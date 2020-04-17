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

    public static function indexablePostTypes() {
        
        $postTypes =  array_diff(
            (array) get_post_types([
                'public' => true,
                'exclude_from_search' => false
            ]),
            ['attachment']
        ); 

        return apply_filters('AlgoliaIndex/IndexablePostTypes', $postTypes); 
    }
    
}
