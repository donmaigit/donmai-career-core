<?php
/**
 * Plugin Name:       Donmai CAREER Core
 * Plugin URI:        https://www.donmai.com
 * Description:       Enterprise job board engine for Donmai Inc. Extends WP Job Manager with Google-style UI and Advanced Custom Fields.
 * Version:           1.0.0
 * Author:            Donmai Inc.
 * Author URI:        https://www.donmai.com
 * Text Domain:       donmai-career-core
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

// Change 1.0.0 (or 1.0.1) to 1.0.5
//define( 'DCC_VERSION', '1.0.5' );

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define Constants for easy access across the plugin
define( 'DCC_VERSION', '1.0.5' );
define( 'DCC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DCC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * 1. Load Text Domain for WPML
 * This ensures strings like "Minimum Qualifications" can be translated to Japanese.
 */
function dcc_load_plugin_textdomain() {
    load_plugin_textdomain(
        'donmai-career-core',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'dcc_load_plugin_textdomain' );

/**
 * 2. Initialize the Core
 * Only runs if dependencies are met.
 */
function run_donmai_career_core() {
    
    // Include the Dependency Checker
    require_once DCC_PLUGIN_DIR . 'includes/class-dcc-dependencies.php';
    
    // Check if WP Job Manager and ACF are active
    if ( Donmai_Career_Dependencies::check() ) {
        
        // If safe, load the rest of the plugin
        require_once DCC_PLUGIN_DIR . 'includes/class-dcc-loader.php';
        $plugin = new Donmai_Career_Loader();
        $plugin->run();
        
    }
}
add_action( 'plugins_loaded', 'run_donmai_career_core' );