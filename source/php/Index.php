<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Id as Id;
use \AlgoliaIndex\Helper\Indexable as Indexable;
use \AlgoliaIndex\Helper\Log as Log;

class Index
{
    //Priority on hooks
    private static $_priority = 999;

    //Keys for keeping track of partial records
    private static $partialObjectDistinctKey = "partial_object_distinct_key";
    private static $partialObjectTotalAmount = "partial_object_total_amount";

    //Maximum size of record
    private static $_nearMaxLimitSize = 9999;

    /**
     * Constructor, runs code on wordpress hooks.
     */
    public function __construct()
    {
        //Add & update
        add_action('save_post', array($this, 'index'), self::$_priority);

        //Remove
        add_action('delete_post', array($this, 'delete'), self::$_priority);
        add_action('wp_trash_post', array($this, 'delete'), self::$_priority);

        //Bulk action
        add_action('AlgoliaIndex/IndexPostId', array($this, 'index'), self::$_priority, 1);
    }

    /**
     * Delete post from index
     *
     * @param int $postId
     * @return void
     */
    public function delete($postId, $isSplitRecord = false)
    {

        if ($isSplitRecord && is_numeric($isSplitRecord)) {

          //Declarations
            $ids  = [];

          //Create all id's
            for ($x = 0; $x <= $isSplitRecord; $x++) {
                $ids[] = self::createChunkId(Id::getId($postId), $x);
            }

          //Delete split records
            if(!empty($ids)) {
                return Instance::getIndex()->deleteObjects($ids);
            } else {
                Log::error('Could not create array of ids for deletion (splitrecord). Trying to delete single post.');
            }
        }

      //Delete normal records
        return Instance::getIndex()->deleteObject(Id::getId($postId));
    }

    /**
     * Submit post to index
     *
     * @param int $postId
     * @return void
     */
    public function index($postId)
    {
        
        //Check if post should be removed
        $shouldPostBeRemoved = [isset($_POST['exclude-from-search']) && $_POST['exclude-from-search'] == "true", get_post_status($postId) !== 'publish'];
        
         if(in_array(true, $shouldPostBeRemoved)) {
            if ($isSplitRecord = self::isSplitRecord($postId)) {
                self::delete($postId, $isSplitRecord);
            } else {
                self::delete($postId);
            }
        } 
        
        //Check if is indexable post
        if (!self::shouldIndex($postId)) {
            return;
        } 

        //Delete split record (no check if has changed)
        if ($isSplitRecord = self::isSplitRecord($postId)) {
            self::delete($postId, $isSplitRecord);
        } else {
          //Check if the new post differs from indexed record (not applicable for split records)
            if (!self::hasChanged($postId)) {
                return;
            }
        }

        //Get post data
        $post = self::getPost($postId);

        //Sanity check (convert data)
        $post = _wp_json_sanity_check($post, 10);

        //Esape html entities
        array_walk_recursive($post, function (&$value) {
            $value = htmlentities($value);
        });

        //Index post
        if (self::recordToLarge($post)) {
            $splitRecord = self::splitRecord($post);

            if (is_array($splitRecord) && !empty($splitRecord)) {
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
    private static function shouldIndex($post)
    {

        //Do not index on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE == true) {
            return false;
        }

        //Do no index revisions
        if (wp_is_post_revision($post)) {
            return false;
        }

        //Check if published post (or any other allowed value)
        if(!in_array(get_post_status($post), Indexable::postStatuses())) {
            return false;
        }

        //Get post type details
        if (!in_array(get_post_type($post), Indexable::postTypes())) {
            return false;
        }

        //Do not index checkbox
        if(get_post_meta($post, 'exclude_from_search', true)) {
            return false;
        }

        //Anything else
        if (!apply_filters('AlgoliaIndex/ShouldIndex', true, $post)) {
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
    private static function hasChanged($postId)
    {

        //Make search
        $response = (object) Instance::getIndex()->getObjects([Id::getId($postId)]);

        //Get result
        if (isset($response->results) && is_array($response->results) && !empty($response->results)) {
            $indexRecord = is_array($response->results) ? array_pop($response->results) : [];
        } else {
            $indexRecord = [];
        }

        //Get stored record
        $storedRecord = self::getPost($postId);

        //Check for null responses, update needed
        if (is_null($indexRecord) || is_null($storedRecord)) {
            return true;
        }

        //Filter out everything that dosen't matter
        $indexRecord    = self::streamlineRecord($indexRecord);
        $storedRecord   = self::streamlineRecord(self::getPost($postId));

        //Diff posts
        if (serialize($indexRecord) != serialize($storedRecord)) {
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
    private static function streamlineRecord($record)
    {

      //List of fields to compare
        $comparables = apply_filters('AlgoliaIndex/Compare', [
        'ID',
        'post_title',
        'post_excerpt',
        'content',
        'permalink',
        'thumbnail'
        ]);

      //Prepare comparables
        $record = (array) array_intersect_key($record, array_flip($comparables));

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
    private static function getPost($postId)
    {
        if ($post = get_post($postId)) {
            //Tags
            $tags = array_map(function (\WP_Term $term) {
                return $term->name;
            }, wp_get_post_terms($postId, 'post_tag'));

            //Categories
            $categories = array_map(function (\WP_Term $term) {
                return $term->name;
            }, wp_get_post_terms($postId, 'category'));

            //Post details
            $result =  array(
              'uuid' => Id::getId($postId),
              'ID' => $post->ID,
              'post_title' => apply_filters('the_title', $post->post_title),
              'post_excerpt' => strip_tags(get_the_excerpt($post)),
              'content' => strip_tags(apply_filters('the_content', $post->post_content)),
              'permalink' => get_permalink($post->ID),
              'post_date' => strtotime($post->post_date),
              'post_date_formatted' => date(get_option('date_format'), strtotime($post->post_date)),
              'post_modified' => strtotime($post->post_modified),
              'thumbnail' => get_the_post_thumbnail_url($post) ? get_the_post_thumbnail_url($post, 'medium') : '',
              'tags' => $tags,
              'categories' => $categories,
              'algolia_timestamp' => current_time("Y-m-d H:i:s"),
              'post_type' => get_post_type($postId),
              'post_type_name' => get_post_type_labels(get_post_type_object(get_post_type($postId)))->name
            );

            //Site
            $result['origin_site'] = get_bloginfo('name');
            $result['origin_site_url'] = get_bloginfo('url');

            //Add blog id
            if (is_multisite()) {
                $result['blog_id'] = get_current_blog_id();
            }

            //Remove multiple spaces
            foreach($result as $key => $field) {
                if(in_array($key, array('post_title', 'post_excerpt', 'content'))) {
                    $result[$key] = preg_replace('/\s+/', ' ', $field);
                }
            }

            return apply_filters('AlgoliaIndex/Record', $result, $postId);
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
    private static function recordToLarge($record)
    {
        if (mb_strlen(serialize((array) $record), '8bit') >= self::$_nearMaxLimitSize) {
            return apply_filters('AlgoliaIndex/RecordToLarge', true);
        }
        return apply_filters('AlgoliaIndex/RecordToLarge', false);
        ;
    }

    /**
     * Split record in multiple chunks.
     *
     * @param [type] $record
     * @return void
     */
    private static function splitRecord($record)
    {

      //Response storage
        $result = array();

      //Calculation of parts
        $contentSize    = mb_strlen($record['content'], '8bit');
        $additionalSize = mb_strlen(serialize(array_diff_key($record, array_flip(['content']))), '8bit');
        $numberOfChunks = (int) ceil($contentSize / (self::$_nearMaxLimitSize - $additionalSize));
        $contentChunks = str_split($record['content'], $contentSize/$numberOfChunks);

      //Create final object to be indexed
        foreach ($contentChunks as $chunkKey => $chunk) {
            $result[$chunkKey] = array_merge($record, [
            'content' => $chunk,
            self::$partialObjectDistinctKey => $record['uuid'],
            self::$partialObjectTotalAmount => count($contentChunks)
            ]);

            $result[$chunkKey]['uuid'] = self::createChunkId($record['uuid'], $chunkKey);
        }

      //Return chunked (or original record if failed to create chunked record).
        return !empty($result) ? $result : [$record];
    }

    /**
     * Creates a chunk id
     *
     * @param   string  $uuid   The id of the post
     * @param   integer $chunk  The integer for chunk part
     * @return  string          New unique string of item
     */
    private static function createChunkId($uuid, $chunk)
    {
        if ($chunk != 0) {
            return $uuid . "-part-" . $chunk;
        }
        return $uuid;
    }

    /**
     * Check if stored record in algolia is a split record.
     * If a split record is found, the number of parts will be
     * returned.
     *
     * @param int $postId
     * @return boolean / integer
     */
    private static function isSplitRecord($postId)
    {
        $response = (object) Instance::getIndex()->getObjects([Id::getId($postId)]);

        if (!is_null($response->results[0]) && array_key_exists(self::$partialObjectDistinctKey, $response->results[0])) {
            return $response->results[0][self::$partialObjectTotalAmount];
        }

        return false;
    }
}
