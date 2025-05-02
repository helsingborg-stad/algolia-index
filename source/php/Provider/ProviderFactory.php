<?php

namespace AlgoliaIndex\Provider;

use AlgoliaIndex\Provider\Algolia\AlgoliaProvider;

class ProviderFactory
{
    public static function createFromEnv(): AbstractProvider
    {
        return \AlgoliaIndex\Provider\Algolia\AlgoliaFactory::createFromEnv();
    }    
}