<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;

class Search
{
    public function __construct()
    {
      add_action( 'pre_get_posts', array($this, 'doAlgoliaQuery'));

    }
     
    /**
     * Do algolia query
     *
     * @param $query
     * @return void
     */
    public function doAlgoliaQuery($query) {
      if (!is_admin() && $query->is_main_query() && self::isSearchPage()) {
        
        if($query->is_search) {

          $query = new \WP_Query(array('post__in' => self::getPostIdArray(
            Instance::getIndex()->search(
              $query->query['s']
            )['hits']
          ) ) );

          var_dump($query); 

/*
          $query = new WP_Query( array(
            'post__in' => array(
                'relation' => 'AND',
                'year' => array(
                    'key' => 'year',
                ),
                'month' => array(
                    'key' => 'month',
                    'compare' => 'EXISTS',
                ), 
            ),
            'orderby' => array(
                'year' => 'DESC',
                'month' => 'DESC',
            ) 
        ) );
          }
        




          //Query algolia for search result
          $query->query_vars['post__in'] = self::getPostIdArray(
            Instance::getIndex()->search(
              $query->query['s']
            )['hits']
          );

          //Query (locally) for a post that dosen't exist, if empty response from algolia
          if(empty($query->query_vars['post__in'])) {
            $query->query_vars['post__in'] = PHP_INT_MAX; //Fake post id
            $query->set('posts_per_page', 1); //Limit to 1 result
          }

          //Disable local search
          $query->query_vars['s'] = false;
          $query->query['s'] = false;

          //Order by respomse order algolia
          $query->set('orderby', 'ORDER BY FIELD(id, [1349, 697, 924])');

*/




        }
      }
    }

    /**
     * Get id's if result array
     *
     * @param   array $response   The full response array
     * @return  array             Array containing results
     */
    private static function getPostIdArray($response) {
      $result = array();
      foreach($response as $item) {
        $result[] = $item['ID']; 
      }
      return $result; 
    }

    /**
     * Check if search page is active page
     *
     * @return boolean
     */
    private static function isSearchPage() {
      return is_search();
    }
}