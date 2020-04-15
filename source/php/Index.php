<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Id as Id;

class Index
{
    private static $_priority = 999; 

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
    public function delete($postId) {
      Instance::getIndex()->deleteObject(Id::getId($postId));
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
            return;
        }

        //Check if the new post differs from indexed record
        if(!self::hasChanged($postId)) {
            return;
        }
        
        //Index post
        Instance::getIndex()->saveObject(
            self::getPost($postId),
            ['objectIDKey' => 'uuid']
        ); 
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
        if(is_array($response->hits) && !empty($response->hits)) {
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
            
            //Post details
            $post =  array(
                'uuid' => Id::getId($postId),
                'id' => $post->ID,
                'post_title' => apply_filters('the_title', $post->post_title),
                'post_excerpt' => get_the_excerpt($post),
                'content' => strip_tags(apply_filters('the_content', $post->post_content)),
                'permalink' => get_permalink($post->ID),
                'post_date' => strtotime($post->post_date),
                'post_date_formatted' => date(get_option('date_format'), strtotime($post->post_date)),
                'post_modified' => strtotime($post->post_modified),
                'images' => [get_the_post_thumbnail_url($post)]
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
}