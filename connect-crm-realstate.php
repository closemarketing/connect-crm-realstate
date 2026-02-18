<?php
/**
 * Plugin Name: Connect CRM Real State PRO
 * Plugin URI: https://close.technology/wordpress-plugins/conecta-crm-realstate/
 * Description: Connect Properties from Inmovilla/Anaconda to a Custom Post Type.
 * Author: closemarketing
 * Author URI: https://close.technology/
 * Version: 1.1.0-beta.1
 *
 * @package WordPress
 * Text Domain: connect-crm-realstate
 * Domain Path: /languages
 * License: GNU General Public License version 3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'CCRMRE_VERSION', '1.1.0-beta.1' );
define( 'CCRMRE_PLUGIN', __FILE__ );
define( 'CCRMRE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCRMRE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CCRMRE_SYNC_PERIOD', 1800 );

/**
 * License Manager instance.
 *
 * @var \Closemarketing\WPLicenseManager\License|null
 */
$ccrmre_license = null;
define( 'CCRMRE_LICENSE_API_URL', 'https://close.technology/' );
define( 'CCRMRE_LICENSE_API_KEY', 'ck_857ef2cf419641b2741ed4ea4d5a750aa979113a' );
define( 'CCRMRE_LICENSE_API_SECRET', 'cs_851fd6126de05a967fc8abb949afe74344faee71' );
define( 'CCRMRE_LICENSE_PRODUCT_UUID', 'CONINMO-5F3A954F-0717-4B13-8305-8AE2AAC060EF' );

/**
 * Check if license is active.
 *
 * @return bool
 */
function ccrmre_is_license_active() {
	global $ccrmre_license;

	if ( ! $ccrmre_license ) {
		return false;
	}

	return $ccrmre_license->is_license_active();
}

/**
 * Initialize License Manager and plugin functionality.
 */
add_action(
	'plugins_loaded',
	function () {
		// Load translations.
		load_plugin_textdomain( 'connect-crm-realstate', false, dirname( plugin_basename( CCRMRE_PLUGIN ) ) . '/languages/' );

		global $ccrmre_license;

		// Check if autoloader is loaded.
		if ( ! file_exists( CCRMRE_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p><strong>Connect CRM Real State:</strong> ' . esc_html__( 'Composer autoloader not found. Please run "composer install".', 'connect-crm-realstate' ) . '</p></div>';
				}
			);
			return;
		}

		// Load autoloader.
		require_once CCRMRE_PLUGIN_PATH . 'vendor/autoload.php';

		if ( ! class_exists( '\Closemarketing\WPLicenseManager\License' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p><strong>Connect CRM Real State:</strong> ' . esc_html__( 'License Manager class not found. Please check Composer dependencies.', 'connect-crm-realstate' ) . '</p></div>';
				}
			);
			return;
		}

		try {
			$ccrmre_license = new \Closemarketing\WPLicenseManager\License(
				array(
					'api_url'         => CCRMRE_LICENSE_API_URL,
					'rest_api_key'    => CCRMRE_LICENSE_API_KEY,
					'rest_api_secret' => CCRMRE_LICENSE_API_SECRET,
					'product_uuid'    => CCRMRE_LICENSE_PRODUCT_UUID,
					'file'            => CCRMRE_PLUGIN,
					'version'         => CCRMRE_VERSION,
					'slug'            => 'connect-crm-realstate',
					'name'            => 'Connect CRM Real State',
					'text_domain'     => 'connect-crm-realstate',
					'settings_page'   => 'iip-options',
					'default_tab'     => 'iip-license',
					'tab_param'       => 'tab',
				)
			);

			// Initialize Settings class early so admin_init hooks are registered.
			$ccrmre_license_settings = new \Closemarketing\WPLicenseManager\Settings(
				$ccrmre_license,
				array(
					'title'        => __( 'Connect CRM Real State License', 'connect-crm-realstate' ),
					'description'  => __( 'Manage your license to receive updates and support.', 'connect-crm-realstate' ),
					'plugin_name'  => 'Connect CRM Real State',
					'purchase_url' => 'https://close.technology/wordpress-plugins/connect-crm-realstate/',
					'renew_url'    => 'https://close.technology/mi-cuenta/',
					'benefits'     => array(
						__( 'Automatic plugin updates', 'connect-crm-realstate' ),
						__( 'Access to new features', 'connect-crm-realstate' ),
						__( 'Priority support', 'connect-crm-realstate' ),
						__( 'Security patches', 'connect-crm-realstate' ),
					),
				)
			);

			// Render license settings content.
			add_action(
				'ccrmre_license_settings_content',
				function () use ( $ccrmre_license_settings ) {
					if ( ! $ccrmre_license_settings ) {
						echo '<div class="notice notice-error"><p>' . esc_html__( 'License manager not initialized.', 'connect-crm-realstate' ) . '</p></div>';
						return;
					}

					$ccrmre_license_settings->render();
				}
			);

			// Always load Admin class so user can access license tab.
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-admin.php';
			new Close\ConnectCRM\RealState\Admin();

			// Only load plugin functionality if license is active.
			if ( ! ccrmre_is_license_active() ) {
				add_action(
					'admin_notices',
					function () {
						$license_url = admin_url( 'admin.php?page=iip-options&tab=iip-license' );
						echo '<div class="notice notice-error"><p><strong>Connect CRM Real State:</strong> ' . esc_html__( 'This plugin requires an active license to function. Please', 'connect-crm-realstate' ) . ' <a href="' . esc_url( $license_url ) . '">' . esc_html__( 'activate your license', 'connect-crm-realstate' ) . '</a> ' . esc_html__( 'to continue using the plugin.', 'connect-crm-realstate' ) . '</p></div>';
					}
				);
				return;
			}

			// Load plugin files only if license is active.
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-helper-api.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-helper-sync.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-import.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-post-type.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-cron.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-featured-image-url.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-gallery.php';
			require_once CCRMRE_PLUGIN_PATH . 'includes/class-property-info.php';

			// Initialize plugin classes only if license is active.
			new Close\ConnectCRM\RealState\Import();
			new Close\ConnectCRM\RealState\PostType();
			new Close\ConnectCRM\RealState\Cron();
		} catch ( \Exception $e ) {
			add_action(
				'admin_notices',
				function () use ( $e ) {
					echo '<div class="notice notice-error"><p><strong>Connect CRM Real State:</strong> ' . esc_html( $e->getMessage() ) . '</p></div>';
				}
			);
		}
	},
	5
);
