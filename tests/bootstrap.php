<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package Connect_CRM_RealState
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'UNIT_TESTS_DATA_PLUGIN_DIR', TESTS_PLUGIN_DIR . '/tests/Data/' );

// Define WP_CORE_DIR if not already defined.
if ( ! defined( 'WP_CORE_DIR' ) ) {
	$_wp_core_dir = getenv( 'WP_CORE_DIR' );
	if ( ! $_wp_core_dir ) {
		$_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
	}
	define( 'WP_CORE_DIR', $_wp_core_dir );
}

// Define WP_TESTS_DIR if not already defined.
if ( ! defined( 'WP_TESTS_DIR' ) ) {
	$_wp_tests_dir = getenv( 'WP_TESTS_DIR' );
	if ( ! $_wp_tests_dir ) {
		$_wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
	}
	define( 'WP_TESTS_DIR', $_wp_tests_dir );
}

// Give access to tests_add_filter() function.
require_once WP_TESTS_DIR . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require TESTS_PLUGIN_DIR . '/connect-crm-realstate.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require WP_TESTS_DIR . '/includes/bootstrap.php';
