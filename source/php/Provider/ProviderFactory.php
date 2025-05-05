<?php

namespace AlgoliaIndex\Provider;

use AlgoliaIndex\Provider\Algolia\AlgoliaProvider;

class ProviderFactory
{
    public static function createFromEnv(): AbstractProvider
    {
        return apply_filters(
            "AlgoliaIndex/Provider/Factory", 
            fn () => \AlgoliaIndex\Provider\Algolia\AlgoliaFactory::createFromEnv()
        )();
    }
}