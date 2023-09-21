<?php
/**
 * Plugin Name: Connect CRM Real State
 * Plugin URI: https://www.closemarketing.es
 * Description: Connect Properties from Inmovilla/Anaconda to a Custom Post Type.
 * Author: closemarketing
 * Author URI: https://close.technology/
 * Version: 0.1
 *
 * @package WordPress
 * Text Domain: import-inmovilla-properties
 * Domain Path: /languages
 * License: GNU General Public License version 3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'CCRMRE_VERSION', '0.1' );
define( 'CCRMRE_PLUGIN', __FILE__ );
define( 'CCRMRE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CCRMRE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Loads translation.
load_plugin_textdomain( 'connect-crm-realstate', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

// Includes files.
require_once CCRMRE_PLUGIN_PATH . 'includes/class-helper-api.php';
require_once CCRMRE_PLUGIN_PATH . 'includes/class-helper-sync.php';
require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-admin.php';
require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-import.php';
require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-post-type.php';

new Close\ConnectCRM\RealState\Admin();
new Close\ConnectCRM\RealState\Import();
new Close\ConnectCRM\RealState\PostType();
