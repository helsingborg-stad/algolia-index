<?php

namespace AlgoliaIndex\Helper;

use \AlgoliaIndex\Helper\Options as Options;
use \AlgoliaIndex\Provider\AbstractProvider;
use AlgoliaIndex\Provider\ProviderFactory;

class Index
{
    private static $_index = null;

    /**
     * Get the index
     *
     * @return AbstractProvider
     */
    public static function getIndex(): AbstractProvider
    {
        if (!is_null(self::$_index)) {
            return self::$_index;
        }

        return self::$_index = ProviderFactory::createFromEnv();
    }
}
