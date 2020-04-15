<?php

namespace AlgoliaIndex\Helper;

class Id
{

    private static $_index = null; 

    /**
     * Get the index
     *
     * @return void
     */
    public static function recordId($postId) {
      if(is_multisite()) {
        return str_replace(".", "-", parse_url(network_site_url())['host']) . "-" . get_current_blog_id() . "-" . $postId; 
      }
      return str_replace(".", "-", parse_url(home_url())['host']) . "-0-" . $postId; 
    }
}
