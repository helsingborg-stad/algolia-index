<?php

namespace AlgoliaIndex;

class Facetting
{
    public function __construct()
    {
        add_filter('AlgoliaIndex/Facets', array($this, 'addFacettingOptions'));
    }

    /**
     * Add facetting options from settings to facets
     *
     * @param   array $existingFacets   The existing facets
     * @return  array                   The merged facets
     */
    public function addFacettingOptions($existingFacets): null|array
    {
        $facets = \AlgoliaIndex\Helper\Options::facetting() ?? [];
        return array_merge($facets, $existingFacets);
    }
}
