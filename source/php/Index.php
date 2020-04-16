<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Id as Id;

class Index
{
    private static $_priority = 999;
    private static $partialObjectDistinctKey = "partial_object_distinct_key"; 
    private static $_nearMaxLimitSize = 9999; 

    public function __construct()
    {
        //Add & update
        add_action('save_post', array($this, 'index'), self::$_priority);

        //Remove
        add_action('delete_post', array($this, 'delete'), self::$_priority);
        add_action('wp_trash_post', array($this, 'delete'), self::$_priority);
    }

    /**
     * Delete post from index
     *
     * @param int $postId
     * @return void
     */
    public function delete($postId, $isSplitRecord = false) {
      if($isSplitRecord) {


        echo "SPL"; 

        Instance::getIndex()->deleteBy(['filters' => self::$partialObjectDistinctKey . ':' . Id::getId($postId)]); //Delete split records
      } else {
        Instance::getIndex()->deleteObject(Id::getId($postId)); //Delete normal records
      }
    } 

    /**
     * Submit post to index
     *
     * @param int $postId
     * @return void
     */
    public function index($postId) {
        
        //Check if is indexable post
        if(!self::shouldIndex($postId)) {
          //return;
        }

        //Delete split record (no check if has changed)
        if($isSplitRecord = self::isSplitRecord($postId)) {
          self::delete($postId, $isSplitRecord); 
        } else {
          //Check if the new post differs from indexed record
          if(!self::hasChanged($postId)) {
            //return;
          }
        }

        //Get post data
        $post = self::getPost($postId);
        
        //Index post
        if(self::recordToLarge($post)) {
          $splitRecord = self::splitRecord($post); 
          if(is_array($splitRecord) && !empty($splitRecord)) {
            Instance::getIndex()->saveObjects(
              $splitRecord,
              ['objectIDKey' => 'uuid']
            ); 
          }
        } else {
          Instance::getIndex()->saveObject(
            $post,
            ['objectIDKey' => 'uuid']
          ); 
        }
        
    }

    /**
     * Determine if the post should be indexed.
     *
     * @param int $post
     * @return boolean
     */
    private static function shouldIndex($post) {

        //Do not index on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE == true) {
            return false; 
        }

        //Do no index revisions
        if(wp_is_post_revision($post)) {
            return false; 
        }

        //Check if published post
        if(get_post_status($post) !== 'publish') {
            return false;
        }

        //Get post type details
        $postType = get_post_type_object(get_post_type($post));

        //Only index public posts
        if($postType->public === false) {
            return false; 
        }

        //Do not index excluded posts
        if($postType->exclude_from_search === true) {
            return false; 
        }

        //Anything else
        if(!apply_filters('AlgoliaIndex/ShouldIndex', true, $post)) {
          return false; 
        }

        return true; 
    }

    /**
     * Check if record in algolia matches locally stored record. 
     *
     * @param int $postId
     * @return boolean
     */
    private static function hasChanged($postId) {
        
        //Make search
        $response = (object) Instance::getIndex()->getObjects([Id::getId($postId)]);

        //Get hit
        if(isset($response->hits) && is_array($response->hits) && !empty($response->hits)) {
            $indexRecord = array_pop($response->hits); 
        } else {
            $indexRecord = [];
        }

        //Filter out everything that dosen't matter
        $indexRecord    = self::streamlineRecord($indexRecord);
        $storedRecord   = self::streamlineRecord(self::getPost($postId)); 

        //Diff posts
        if(serialize($indexRecord) != serialize($storedRecord)) {
            return true; //Post has updates
        }
        return false; //Post has no updates
    }

    /**
     * Streamline record, basicly tells what to use 
     * to compare posts for update checking. 
     *
     * @param array $record
     * @return array
     */
    private static function streamlineRecord($record) {
        
      //List of fields to compare
      $comparables = apply_filters('AlgoliaIndex/Compare',[
        'ID',
        'post_title',
        'post_excerpt',
        'content',
        'permalink',
        'images'
      ]); 

      //Prepare comparables
      $record = array_intersect_key($record, array_flip($comparables));

      //Sort (resolves different orders)
      array_multisort($record);

      //Send back
      return $record;
    }

    /**
     * Get post by ID
     *
     * @param int $postId
     * @return array
     */
    private static function getPost($postId) {

        if($post = get_post($postId)) {

            //Tags 
            $tags = array_map(function (WP_Term $term) {
              return $term->name;
            }, wp_get_post_terms($postId, 'post_tag'));

            //Categories 
            $categories = array_map(function (WP_Term $term) {
              return $term->name;
            }, wp_get_post_terms($postId, 'category'));

            
            //Post details
            $post =  array(
              'uuid' => Id::getId($postId),
              'ID' => $post->ID,
              'post_title' => apply_filters('the_title', $post->post_title),
              'post_excerpt' => get_the_excerpt($post),
              'content' => strip_tags(apply_filters('the_content', $post->post_content)),
              'permalink' => get_permalink($post->ID),
              'post_date' => strtotime($post->post_date),
              'post_date_formatted' => date(get_option('date_format'), strtotime($post->post_date)),
              'post_modified' => strtotime($post->post_modified),
              'images' => array_filter([get_the_post_thumbnail_url($post)]),
              'tags' => $tags,
              'categories' => $categories,
            ); 

            //Site
            $post['origin_site'] = get_bloginfo('name'); 
            $post['origin_site_url'] = get_bloginfo('url'); 

            //Add blog id
            if(is_multisite()) {
              $post['blog_id'] = get_current_blog_id();
            }

            return apply_filters('AlgoliaIndex/Record', $post, $postId); 
        }

        return null;
    }

    /**
     * Check if the record is close to the limit of algolia max record size.
     * This applies for most plans.
     *
     * @param array $record
     * @return void
     */
    private static function recordToLarge($record) {
      if(mb_strlen(serialize((array) $record), '8bit') >= self::$_nearMaxLimitSize) {
        return apply_filters('AlgoliaIndex/RecordToLarge', true); 
      }
      return apply_filters('AlgoliaIndex/RecordToLarge', false); ; 
    }

    /**
     * Split record in multiple chunks. 
     *
     * @param [type] $record
     * @return void
     */
    private static function splitRecord($record) {

      //Response storage
      $result = array();

      //Calculation of parts
      $contentSize    = mb_strlen($record['content'], '8bit'); 
      $additionalSize = mb_strlen(serialize(array_diff_key($record, array_flip(['content']))), '8bit');
      $numberOfChunks = (int) ceil($contentSize / (self::$_nearMaxLimitSize - $additionalSize)); 
      $contentChunks = str_split($record['content'], $contentSize/$numberOfChunks); 

      //Create final object to be indexed
      foreach($contentChunks as $chunkKey => $chunk) {
        $result[$chunkKey] = array_merge($record, [
          'content' => $chunk,
          self::$partialObjectDistinctKey => $record['uuid']
        ]);
        if($chunkKey != 0) {
          $result[$chunkKey]['uuid'] = $record['uuid'] . "-part-" . $chunkKey; 
        }
      }

      return !empty($result) ? $result : $record; 
      
    }

    /**
     * Check if stored record in algolia is a split record. 
     *
     * @param int $postId
     * @return boolean
     */
    private static function isSplitRecord($postId) {
      
      $response = (object) Instance::getIndex()->getObjects([Id::getId($postId)]);

      if(array_key_exists(self::$partialObjectDistinctKey, $response->results[0])) {
        return true;
      }

      return false; 
    }
}