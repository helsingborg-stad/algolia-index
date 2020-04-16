<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;

class Bulk
{

    private $prefix = "algolia-index" . " "; 

    public function __construct()
    {
        //Build command
        \WP_CLI::add_command($this->prefix . 'build', array($this, 'build')); //Will not clear index
    }

    /**
     * Build index
     *
     * @param [type] $args
     * @param [type] $assocArgs
     * @return void
     */
    public function build($args, $assocArgs) {

        // Clear index if flag is true
        if(isset($assocArgs['clearindex']) && $assocArgs['clearindex'] == "true") {
            \WP_CLI::log("Clearing index...");
            Instance::getIndex()->clearObjects(); 
        }

        \WP_CLI::log("Starting index build..."); 

        $postTypes = $this->getPostTypes();

        if(is_array($postTypes) && !empty($postTypes)) {
            foreach($postTypes as $postType) {
                $posts = (array) $this->getPosts($postType); 
                if(is_array($posts) && !empty($posts)) {
                    foreach($posts as $post) {
                        \WP_CLI::log("Indexing '" . $post->post_title . "' of posttype " . $postType);
                        do_action('algolia_index_post_id', $post->ID);
                    }
                }
            }
        } else {
            \WP_CLI::error("Could not find any indexable posttypes. This will occur when no content is public."); 
        }

        \WP_CLI::success("Build done!");
    }

    /**
     * Get posts to try to index. 
     *
     * @param string $postType
     * @return array 
     */
    public function getPosts($postType) {
        return get_posts([
            'post_type' => $postType, 
            'numberposts' => -1
        ]);
    }

    /**
     * Get all public post types
     *
     * @return array Registered public posttypes. 
     */
    public function getPostTypes() {
        return array_diff(
            (array) get_post_types([
                'public' => true,
                'exclude_from_search' => false
            ]),
            ['attachment']
        ); 
    }
}