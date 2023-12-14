<?php
/**
 * Plugin Name: Connect CRM Real State
 * Plugin URI: https://www.closemarketing.es
 * Description: Connect Properties from Inmovilla/Anaconda to a Custom Post Type.
 * Author: closemarketing
 * Author URI: https://close.technology/
 * Version: 1.0.0-beta.6
 *
 * @package WordPress
 * Text Domain: connect-crm-realstate
 * Domain Path: /languages
 * License: GNU General Public License version 3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'CCRMRE_VERSION', '1.0.0-beta.6' );
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
require_once CCRMRE_PLUGIN_PATH . 'includes/class-iip-cron.php';

require 'vendor/autoload.php';

new Close\ConnectCRM\RealState\Admin();
new Close\ConnectCRM\RealState\Import();
new Close\ConnectCRM\RealState\PostType();
new Close\ConnectCRM\RealState\Cron();
