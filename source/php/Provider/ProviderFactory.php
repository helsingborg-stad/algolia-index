<?php

namespace AlgoliaIndex\Provider;

use AlgoliaIndex\Provider\Algolia\AlgoliaProvider;

class ProviderFactory
{
    public static function getProviders()
    {
        return apply_filters("AlgoliaIndex/Provider/Factory", [
            'algolia' => fn () => \AlgoliaIndex\Provider\Algolia\AlgoliaFactory::createFromEnv()    
        ]);
    }
    
    public static function createFromEnv($provider = null): AbstractProvider
    {
        $providers = self::getProviders();
        $provider = !empty($provider) 
            ? $provider 
            : get_field('algolia_index_search_provider', 'option') ?? 'algolia';

        if (!is_string($provider)) {
            throw new \InvalidArgumentException('Provider name must be a string');
        }

        if (!array_key_exists($provider, $providers)) {
            throw new \InvalidArgumentException('Provider not found');
        }
        if (!is_callable($providers[$provider])) {
            throw new \InvalidArgumentException('Provider is not callable');
        }

        $factory = $providers[$provider];

        return $factory();
    }
}