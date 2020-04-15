<?php

namespace AlgoliaIndex;

class App
{
    public function __construct()
    {
        new \AlgoliaIndex\Index(); 
        new \AlgoliaIndex\Search(); 

        if(defined('WP_CLI') && WP_CLI == true) {
            new \AlgoliaIndex\Bulk();
        }
    }
}
