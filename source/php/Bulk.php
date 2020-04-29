<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Indexable as Indexable;

class Bulk
{

    private $prefix = "algolia-index" . " ";

    public function __construct()
    {
        //Build command
        \WP_CLI::add_command($this->prefix . 'build', array($this, 'build')); //Will not clear index
        \WP_CLI::add_command($this->prefix . 'networkbuild', array($this, 'networkbuild')); //Will not clear index
    }

    /**
     * Build index for whole network
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function networkbuild($args, $assocArgs)
    {
        if(is_multisite()) {

            \WP_CLI::log("Building network...");

            $sites = get_sites(); 

            foreach($sites as $site){
                switch_to_blog($site->blog_id);
                $this->build($args, $assocArgs); 
                restore_current_blog();
            }

            return true;
        }

        \WP_CLI::error("No network detected, please use command build.");
    }

    /**
     * Build index
     *
     * @param array $args
     * @param array $assocArgs
     * @return void
     */
    public function build($args, $assocArgs)
    {

        //Send settings
        if (isset($assocArgs['settings']) && $assocArgs['settings'] == "true") {
            \WP_CLI::log("Sending settings...");
            do_action('AlgoliaIndex/SendSettings', false);
        }

        // Clear index if flag is true
        if (isset($assocArgs['clearindex']) && $assocArgs['clearindex'] == "true") {
            \WP_CLI::log("Clearing index...");
            Instance::getIndex()->clearObjects();
        }

        \WP_CLI::log("Starting index build...");

        $postTypes = Indexable::postTypes();

        if (is_array($postTypes) && !empty($postTypes)) {
            foreach ($postTypes as $postType) {
                $posts = (array) $this->getPosts($postType);
                if (is_array($posts) && !empty($posts)) {
                    foreach ($posts as $post) {
                        \WP_CLI::log("Indexing '" . $post->post_title . "' of posttype " . $postType);
                        do_action('AlgoliaIndex/IndexPostId', $post->ID);
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
    public function getPosts($postType)
    {
        return get_posts([
            'post_type' => $postType,
            'numberposts' => -1
        ]);
    }
}
