<?php
/**
 * PHPStan Bootstrap File
 *
 * Defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

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

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

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
