<?php

namespace AlgoliaIndex;

class Bulk
{

    private $prefix = "algolia-index" . " "; 

    public function __construct()
    {
        //Build command
        \WP_CLI::add_command($this->prefix . 'build', array($this, 'build')); //Will not clear index
    }

    public function build() {

        if($clearIndex) {
            \WP_CLI::log("Clearing index...");
        }

        \WP_CLI::log("Starting index build..."); 
        \WP_CLI::log("Fetching posttypes..."); 

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

    public function getPosts($postType) {
        return get_posts(['post_type' => $postType, 'numberposts' => -1]);
    }

    public function getPostTypes() {
        return get_post_types(); 
    }
}