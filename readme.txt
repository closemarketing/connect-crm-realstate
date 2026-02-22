=== Connect CRM RealState ===
Contributors: closetechnology, davidperez
Tags: real estate, properties, inmovilla, anaconda, crm
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Import real estate properties from Inmovilla and Anaconda CRM systems into WordPress as custom post types.

== Description ==

Connect CRM RealState imports properties from popular real estate CRM systems (Inmovilla and Anaconda) into WordPress. Properties are stored as custom post types with full field mapping, photo galleries, and property information displays.

**Supported CRM Systems:**

* **Anaconda** - Full REST API integration
* **Inmovilla Procesos** - REST API v1 integration
* **Inmovilla APIWEB** - Legacy API support

**Features:**

* Manual property import with progress tracking
* Configurable field mapping between CRM and WordPress custom fields
* Auto-map fields for quick setup
* Property photo gallery with carousel (shortcode and auto-display)
* Property information box with icons (shortcode and auto-display)
* Custom post type registration or use any existing post type
* Filter imports by postal code
* Configure actions for sold/unavailable properties
* Download images locally for better performance
* Import statistics dashboard
* Rate limit detection and automatic retry
* Compatible with Yoast SEO and Rank Math

**Shortcodes:**

* `[property_gallery]` - Display property photo gallery
* `[property_info]` - Display property information box with price, bedrooms, bathrooms, area, and location

**PRO Features (via [Connect CRM RealState PRO](https://close.technology/wordpress-plugins/conecta-crm-realstate/) add-on):**

* Automatic background synchronization via cron
* SEO-optimized property content *(coming soon)*
* AI-powered property descriptions with LLM *(coming soon)*

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/connect-crm-realstate` directory, or install the plugin through the WordPress plugins screen.
2. Run `composer install` in the plugin directory to install dependencies.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to Connect CRM RealState > Settings to configure your CRM API credentials.
5. Go to Merge Variables to map CRM fields to WordPress custom fields.
6. Go to Import Properties to start importing.

== Frequently Asked Questions ==

= Which CRM systems are supported? =

The plugin supports Anaconda, Inmovilla Procesos (REST API), and Inmovilla APIWEB.

= Can I use my own custom post type? =

Yes, in Settings you can choose to use the built-in "Property" post type or any existing public post type.

= How do I display the photo gallery? =

Either enable auto-display in Settings or use the `[property_gallery]` shortcode in your templates.

= Can I filter which properties are imported? =

Yes, you can filter by postal code. Use wildcards like `18*` to include all properties from an area.

== Screenshots ==

1. Import Properties dashboard with statistics
2. Settings page for CRM configuration
3. Merge Variables field mapping interface
4. Property gallery frontend display

== Changelog ==

= 1.0.0 =
* Initial release on WordPress.org.
* Manual property import from Anaconda, Inmovilla Procesos, and Inmovilla APIWEB.
* Configurable field mapping with auto-map.
* Property gallery and info box shortcodes.
* Import statistics dashboard.
* Rate limit detection and retry.
* Image download options.
* Postal code filtering.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
