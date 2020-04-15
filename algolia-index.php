<?php

/**
 * Plugin Name:       Algolia Index
 * Plugin URI:        (#plugin_url#)
 * Description:       Manages algolia index (with ms-support and mixed indexes)
 * Version:           1.0.0
 * Author:            Sebastian Thulin
 * Author URI:        (#plugin_author_url#)
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       algolia-index
 * Domain Path:       /languages
 */

 // Protect agains direct file access
if (! defined('WPINC')) {
    die;
}

define('ALGOLIAINDEX_PATH', plugin_dir_path(__FILE__));
define('ALGOLIAINDEX_URL', plugins_url('', __FILE__));
define('ALGOLIAINDEX_TEMPLATE_PATH', ALGOLIAINDEX_PATH . 'templates/');

load_plugin_textdomain('algolia-index', false, plugin_basename(dirname(__FILE__)) . '/languages');

require_once ALGOLIAINDEX_PATH . 'vendor/autoload.php';
require_once ALGOLIAINDEX_PATH . 'source/php/Vendor/Psr4ClassLoader.php';
require_once ALGOLIAINDEX_PATH . 'Public.php';

// Instantiate and register the autoloader
$loader = new AlgoliaIndex\Vendor\Psr4ClassLoader();
$loader->addPrefix('AlgoliaIndex', ALGOLIAINDEX_PATH);
$loader->addPrefix('AlgoliaIndex', ALGOLIAINDEX_PATH . 'source/php/');
$loader->register();

// Start application
new AlgoliaIndex\App();
