<?php

namespace AlgoliaIndex\Provider\Algolia;

class AlgoliaProvider implements \AlgoliaIndex\Provider\AbstractProvider
{
    protected $client;

    protected $config;

    protected $index;

    public function __construct(
        string $applicationId, 
        string $apiKey, 
        string $indexName
    ){
         //Setup config details with auth
         $config = \Algolia\AlgoliaSearch\Config\SearchConfig::create(
            $applicationId,
            $apiKey
        );

        //Tell algolia if running in cron and/or cli
        $config->setDefaultHeaders([
            'X-Client-Cli' => defined('WP_CLI_VERSION') ? WP_CLI_VERSION : 'false',
            'X-Client-Cron' => defined( 'DOING_CRON' ) ? 'true' : 'false',
            'X-Client-User' => get_current_user_id(),
        ]);

        $this->config = apply_filters('AlgoliaIndex/Config', $config) ?? $config;
        $this->client = \Algolia\AlgoliaSearch\SearchClient::createWithConfig($this->config);
        $this->index = $this->client->initIndex($indexName);
    }

    /**
     * @inheritDoc
     */
    public function clearObjects() {
        return $this->index->clearObjects();
    }

    /**
     * @inheritDoc
     */
    public function deleteObject(string $objectId) {
        return $this->index->deleteObject($objectId);
    }

    /**
     * @inheritDoc
     */
    public function deleteObjects(array $objectIds) {
        return $this->index->deleteObjects($objectIds);
    }

    /**
     * @inheritDoc
     */
    public function getObjects(array $objectIds) {
        return $this->index->getObjects($objectIds);
    }

    /**
     * @inheritDoc
     */
    public function saveObject(array $object, array $options = []) {
        return $this->index->saveObject($object, $options);
    }

    /**
     * @inheritDoc
     */
    public function saveObjects(array $objects, array $options = []) {
        return $this->index->saveObjects($objects, $options);
    }

    /**
     * @inheritDoc
     */
    public function search(string $query) {
        return $this->index->search($query);
    }

    /**
     * @inheritDoc
     */
    public function setSettings(array $settings) {
        return $this->index->setSettings($settings);
    }
}