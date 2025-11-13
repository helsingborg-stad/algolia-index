<?php

/**
 * Plugin Name:       Algolia Index
 * Plugin URI:        https://github.com/helsingborg-stad/algolia-index
 * Description:       Manages algolia index (with ms-support and mixed indexes)
 * Version: 3.3.1
 * Author:            Sebastian Thulin
 * Author URI:        https://github.com/sebastianthulin
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

add_action('init', function () {
    load_plugin_textdomain('algolia-index', false, plugin_basename(dirname(__FILE__)) . '/languages');
});

// Acf auto import and export
add_action('acf/init', function () {
    if (class_exists('\AcfExportManager\AcfExportManager')) {
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        $acfExportManager = new \AcfExportManager\AcfExportManager();
        $acfExportManager->setTextdomain('algolia-index');
        $acfExportManager->setExportFolder(
            ALGOLIAINDEX_PATH.'source/php/AcfFields/'
        );
        $acfExportManager->autoExport([
            'algolia-index-general-settings'        => 'group_68bfb24b7a4a2',
            'algolia-index-algolia-provider'        => 'group_68bfad0b6fc7b',
            'algolia-index-facetting-settings'      => 'group_690dfe46d9b6e',
        ]);
        $acfExportManager->import();
    }
});

// Autoload from plugin
if (file_exists(ALGOLIAINDEX_PATH . 'vendor/autoload.php')) {
    require_once ALGOLIAINDEX_PATH . 'vendor/autoload.php';
}

require_once ALGOLIAINDEX_PATH . 'Public.php';

// Start application
new AlgoliaIndex\App();