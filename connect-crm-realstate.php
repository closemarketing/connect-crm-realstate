<?php
/**
 * Plugin Name: Connect CRM RealState
 * Plugin URI: https://close.technology/wordpress-plugins/conecta-crm-realstate/
 * Description: Connect Properties from Inmovilla/Anaconda to a Custom Post Type.
 * Author: closetechnology
 * Author URI: https://close.technology/
 * Version: 1.2.0
 *
 * @package WordPress
 * Text Domain: connect-crm-realstate
 *
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.0
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'CCRMRE_VERSION', '1.2.0' );
define( 'CCRMRE_PLUGIN', __FILE__ );
define( 'CCRMRE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCRMRE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCRMRE_POST_TYPE', 'ccrmre_property' );

/**
 * One-time migration from old option/transient names to ccrmre-prefixed names.
 *
 * @return void
 */
function ccrmre_migrate_options_to_prefix() {
	if ( get_option( 'ccrmre_options_migrated', false ) ) {
		return;
	}

	$old_settings = get_option( 'conncrmreal_settings', null );
	if ( null !== $old_settings ) {
		update_option( 'ccrmre_settings', $old_settings );
	}
	$old_merge = get_option( 'conncrmreal_merge_fields', null );
	if ( null !== $old_merge ) {
		update_option( 'ccrmre_merge_fields', $old_merge );
	}
	$old_tax = get_option( 'conncrmreal_taxonomy_mappings', null );
	if ( null !== $old_tax ) {
		update_option( 'ccrmre_taxonomy_mappings', $old_tax );
	}

	update_option( 'ccrmre_options_migrated', true );
}

add_action( 'plugins_loaded', 'ccrmre_migrate_options_to_prefix', 1 );

/**
 * Initialize plugin functionality.
 */
add_action(
	'plugins_loaded',
	function () {
		// Check if autoloader is loaded.
		if ( ! file_exists( CCRMRE_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p><strong>Connect CRM RealState:</strong> ' . esc_html__( 'Composer autoloader not found. Please run "composer install".', 'connect-crm-realstate' ) . '</p></div>';
				}
			);
			return;
		}

		// Load autoloader.
		require_once CCRMRE_PLUGIN_PATH . 'vendor/autoload.php';

		// Load plugin files.
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-admin.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-helper-api.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-helper-sync.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-import.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-post-type.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-featured-image-url.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-gallery.php';
		require_once CCRMRE_PLUGIN_PATH . 'includes/class-property-info.php';

		// Initialize plugin classes.
		new Close\ConnectCRM\RealState\Admin();
		new Close\ConnectCRM\RealState\Import();
		new Close\ConnectCRM\RealState\PostType();
	},
	5
);
