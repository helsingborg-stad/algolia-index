<?php

namespace AlgoliaIndex;

use \AlgoliaIndex\Helper\Index as Instance;
use \AlgoliaIndex\Helper\Indexable as Indexable;
use \AlgoliaIndex\Helper\Options as Options;

class Bulk
{

    private $prefix = "algolia-index" . " ";

    public function __construct()
    {
        //Build command
        \WP_CLI::add_command($this->prefix . 'build', array($this, 'build'));
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
        if (!Options::isConfigured()) {
            \WP_CLI::log("Search must be configured before indexing, terminating...");
            return;
        }

        //Send settings
        if (isset($assocArgs['settings']) && $assocArgs['settings'] == "true") {
            \WP_CLI::log("Sending settings...");
            do_action('AlgoliaIndex/SendSettings');
        }

        // Clear index if flag is true
        if (isset($assocArgs['clearindex']) && $assocArgs['clearindex'] == "true") {
            \WP_CLI::log("Clearing index...");
            Instance::getIndex()->clearObjects();
        }

        \WP_CLI::log("Starting index build for site " . get_option('home'));

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
