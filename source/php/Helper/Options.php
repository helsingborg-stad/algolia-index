<?php

namespace AlgoliaIndex\Helper;

class Options
{
    /**
     * Get the app-id
     *
     * @return string $appId
     */
    public static function applicationId()
    {
        if (defined('ALGOLIAINDEX_APPLICATION_ID') && !empty(ALGOLIAINDEX_APPLICATION_ID)) {
            return ALGOLIAINDEX_APPLICATION_ID;
        }
        return self::getOption()['application_id'];
    }

    /**
     * Get the api key
     *
     * @return string $apiKey
     */
    public static function apiKey()
    {
        if (defined('ALGOLIAINDEX_API_KEY') && !empty(ALGOLIAINDEX_API_KEY)) {
            return ALGOLIAINDEX_API_KEY;
        }
        return self::getOption()['api_key'];
    }

    /**
     * Get the api key
     *
     * @return string $apiKey
     */
    public static function publicApiKey()
    {
        if (defined('ALGOLIAINDEX_PUBLIC_API_KEY') && !empty(ALGOLIAINDEX_PUBLIC_API_KEY)) {
            return ALGOLIAINDEX_PUBLIC_API_KEY;
        }
        return self::getOption()['public_api_key'];
    }

     /**
     * Get or automatically create a index name, if not set by the developer.
     *
     *  - Index name will be the hostname as default.
     *  - Subfolder sites will share search index by default.
     *
     * @return string $indexName
     */
    public static function indexName()
    {
      
      //Constant
        if (defined('ALGOLIAINDEX_INDEX_NAME') && !empty(ALGOLIAINDEX_INDEX_NAME)) {
            return ALGOLIAINDEX_INDEX_NAME;
        }

      //Database
        $dbOption = self::getOption()['index_name'];
        if (!empty($dbOption) && is_string($dbOption)) {
            return $dbOption;
        }

      //Use autogenerated index name, mutisites using subfolder structure will use the same index by default.
        return apply_filters("AlgoliaIndex/GeneratedIndexName", str_replace(".", "-", parse_url(get_option('home'))['host']));
    }

    /**
     * Get option and enshure that all keys exists.
     *
     * @return array
     */
    private static function getOption()
    {
        return array_merge(
            array_flip(['application_id', 'api_key', 'index_name']),
            array_filter((array) get_option('algolia_index')),
        );
    }
}
