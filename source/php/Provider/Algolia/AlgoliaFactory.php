<?php

namespace AlgoliaIndex\Provider\Algolia;

use AlgoliaIndex\Provider\AbstractProvider;

class AlgoliaFactory
{
    public static function createFromEnv(): AbstractProvider
    {
        return new AlgoliaProvider(
            \AlgoliaIndex\Helper\Options::applicationId(),
            \AlgoliaIndex\Helper\Options::apiKey(),
            \AlgoliaIndex\Helper\Options::indexName()
        );
    }
}