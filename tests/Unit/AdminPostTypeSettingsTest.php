<?php
/**
 * Tests for plugin post type settings compatibility.
 *
 * @package ConnectCRM\RealState\Tests\Unit
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\Admin;
use WP_UnitTestCase;

/**
 * Admin post type settings test case.
 */
class AdminPostTypeSettingsTest extends WP_UnitTestCase {

	/**
	 * The legacy plugin post type keeps its custom slug field available.
	 *
	 * @return void
	 */
	public function test_legacy_plugin_post_type_registers_slug_field() {
		global $wp_settings_fields;

		$saved_settings_fields = $wp_settings_fields;

		try {
			update_option(
				'ccrmre_settings',
				array(
					'post_type'      => 'property',
					'post_type_slug' => 'legacy-properties',
				)
			);

			$admin = new Admin();
			$admin->register_plugin_settings();

			$this->assertArrayHasKey( 'ccrmre_post_type_slug', $wp_settings_fields['ccrmre_settings']['ccrmre_admin_settings'] );
		} finally {
			$wp_settings_fields = $saved_settings_fields;
			delete_option( 'ccrmre_settings' );
		}
	}
}
