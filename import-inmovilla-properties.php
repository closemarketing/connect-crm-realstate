<?php
/**
 * Plugin Name: Import Inmovilla Properties
 * Plugin URI: https://www.closemarketing.es
 * Description: Imports Properties from Inmovilla to a Custom Post Type.
 * Author: closemarketing, davidperez
 * Author URI: https://www.closemarketing.net/
 * Version: 0.1
 *
 * @package WordPress
 * Text Domain: import-inmovilla-properties
 * Domain Path: /languages
 * License: GNU General Public License version 3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define( 'IIP_VERSION', '0.1' );

// Loads translation.
load_plugin_textdomain( 'import-inmovilla-properties', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

// Includes files.
require_once dirname( __FILE__ ) . '/includes/helpers.php';
require_once dirname( __FILE__ ) . '/includes/class-iip-admin.php';
require_once dirname( __FILE__ ) . '/includes/class-iip-import.php';
