=== Connect CRM RealState ===
Contributors: closemarketing
Tags: properties, inmovilla

Import Inmovilla and Anaconda properties to your WordPress Installation.

Docs Inmovilla: https://procesos.apinmo.com/apiweb/doc/index.html

== Description ==


== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your
WordPress installation and then activate the Plugin from Plugins page.

== Developers ==


== Changelog ==

= 1.2.0 =
* Added rate limit detection during import for API errors 429 and 408.
* Import waits and retries automatically when the API requests a pause.
* User is informed with a countdown while waiting for rate limit to expire.
* Shows detailed reason when a property is skipped as unavailable.

= 1.1.0 =
* Added option to download property images to your server for better performance.
* Three download modes: no download, featured image only, or all images.
* Gallery now uses local images when available, with automatic fallback to external links.
* Property gallery, info box, and meta boxes now work with any custom post type.
* Simplified featured image handling for better compatibility with the block editor.

= 1.0.0 =
* Imports all properties from Anaconda.
* Option to filter by Postal code.
*	First released.
* Saves logs in folder.
* Syncs from last date of sync.

== Links ==

*	[Closemarketing](https://www.closemarketing.net/)


== Closemarketing plugins ==

*	[Send SMS to WordPress Users via Arsys](https://wordpress.org/plugins/send-sms-arsys/)
*	[Clean HTML Code in the Editor](https://wordpress.org/plugins/clean-html/)
