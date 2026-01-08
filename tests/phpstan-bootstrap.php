<?php
/**
 * PHPStan Bootstrap File
 *
 * This file defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

// Define plugin constants that are used throughout the codebase.
if ( ! defined( 'CCRMRE_VERSION' ) ) {
	define( 'CCRMRE_VERSION', '1.0.0' );
}

if ( ! defined( 'CCRMRE_PLUGIN' ) ) {
	define( 'CCRMRE_PLUGIN', __FILE__ );
}

if ( ! defined( 'CCRMRE_PLUGIN_URL' ) ) {
	define( 'CCRMRE_PLUGIN_URL', 'http://localhost/wp-content/plugins/connect-crm-realstate/' );
}

if ( ! defined( 'CCRMRE_PLUGIN_PATH' ) ) {
	define( 'CCRMRE_PLUGIN_PATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'CCRMRE_SYNC_PERIOD' ) ) {
	define( 'CCRMRE_SYNC_PERIOD', 1800 );
}

// License Manager constants.
if ( ! defined( 'CCRMRE_LICENSE_API_URL' ) ) {
	define( 'CCRMRE_LICENSE_API_URL', 'https://close.technology/' );
}

if ( ! defined( 'CCRMRE_LICENSE_API_KEY' ) ) {
	define( 'CCRMRE_LICENSE_API_KEY', 'ck_857ef2cf419641b2741ed4ea4d5a750aa979113a' );
}

if ( ! defined( 'CCRMRE_LICENSE_API_SECRET' ) ) {
	define( 'CCRMRE_LICENSE_API_SECRET', 'cs_851fd6126de05a967fc8abb949afe74344faee71' );
}

if ( ! defined( 'CCRMRE_LICENSE_PRODUCT_UUID' ) ) {
	define( 'CCRMRE_LICENSE_PRODUCT_UUID', '8e76ed3a-66ba-47e4-8b95-18bca2e8f6a3' );
}

// Define WordPress constants that might be missing.
if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', false );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/path/to/wordpress/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

// Mock WordPress functions that PHPStan can't find.
if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

// Mock Action Scheduler function.
if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
	function as_schedule_recurring_action( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
		return true;
	}
}

if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
	function as_unschedule_all_actions( $hook, $args = array(), $group = '' ) {
		return null;
	}
}

if ( ! function_exists( 'as_has_scheduled_action' ) ) {
	function as_has_scheduled_action( $hook, $args = array(), $group = '' ) {
		return false;
	}
}

// Mock WP_CLI class.
if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		public static function line( $message ) {
			echo $message . "\n";
		}
		public static function add_command( $command, $class ) {
			return true;
		}
	}
}

if ( ! function_exists( 'ccrmre_is_license_active' ) ) {
	function ccrmre_is_license_active() {
		return true;
	}
}
