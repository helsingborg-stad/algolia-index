<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;

class Settings
{
    
  public function __construct() {
    add_action('admin_init', array($this, 'sendSearchableAttributes'));
  }

  /**
   * Send searchable attributes. 
   *
   * @return void
   */
  public function sendSearchableAttributes() {
  
    // Define searchable attributes
    $searchableAttributes = applyFilters('AlgoliaIndex/SearchableAttributes',[
      'post_title',
      'post_excerpt',
      'content',
      'permalink',
    ]);

    //Send settings 
    $index->setSettings(['searchableAttributes' => $searchableAttributes]);

  }
}


