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

* `[ccrmre_property_gallery]` - Display property photo gallery
* `[ccrmre_property_info]` - Display property information box with price, bedrooms, bathrooms, area, and location

**PRO Features (via [Connect CRM RealState PRO](https://close.technology/wordpress-plugins/conecta-crm-realstate/) add-on):**

* Automatic background synchronization via cron
* SEO-optimized property content *(coming soon)*
* AI-powered property descriptions with LLM *(coming soon)*

== External Services ==

This plugin connects to third-party real estate CRM APIs to import property data. Connection only occurs when you run an import.

**Anaconda (api.anaconda.guru)**  
Used to fetch property listings and details. The plugin sends your configured API credentials and request parameters (e.g. filters, pagination) when you use the Anaconda CRM type. Data is sent only when importing or syncing. Check your Anaconda provider or contract for [terms](https://www.anacondasolutions.es/aviso-legal/) and [privacy](https://www.anacondasolutions.es/politica-de-privacidad/).

**Inmovilla**  
Used to fetch property data when you use the Inmovilla Procesos CRM type. The plugin sends your API credentials and request parameters only during import or sync. Inmovilla [terms](https://inmovilla.com/aviso-legal/) and [privacy policy](https://www.inmovilla.com/politica-de-privacidad/)

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

Either enable auto-display in Settings or use the `[ccrmre_property_gallery]` shortcode in your templates.

= Can I filter which properties are imported? =

Yes, you can filter by postal code. Use wildcards like `18*` to include all properties from an area.

== Screenshots ==

1. Import Properties dashboard with statistics
2. Settings page for CRM configuration
3. Merge Variables field mapping interface
4. Property gallery frontend display

== Changelog ==

= Unreleased =
-   Added Taxonomy Mapping feature: repeater field to map CRM fields to WordPress taxonomies.
-   Added automatic taxonomy term assignment during property synchronization.

= 1.2.0 =
* Major refactor. Created the free version of the plugin.
* Error in property API does not stop the import.

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
