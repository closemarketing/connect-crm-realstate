<?php
/**
 * Tests for the property meta box.
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\PostType;
use WP_UnitTestCase;

/**
 * Test case for the property meta box.
 */
class PostTypeMetaBoxTest extends WP_UnitTestCase {
	/**
	 * Shows native CRM fields when merge fields are not configured.
	 */
	public function test_shows_native_fields_without_merge_fields() {
		$post_id = self::factory()->post->create();

		update_option( 'ccrmre_merge_fields', array() );
		update_post_meta( $post_id, 'property_cod_ofer', '1234' );
		update_post_meta( $post_id, 'property_precioinmo', '250000' );
		update_post_meta( $post_id, 'property_synced', true );

		$metabox = new PostType();
		$post    = get_post( $post_id );

		ob_start();
		$metabox->metabox_show_property( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'cod_ofer', $output );
		$this->assertStringContainsString( 'precioinmo', $output );
		$this->assertStringContainsString( 'property_cod_ofer', $output );
		$this->assertStringNotContainsString( 'property_synced', $output );
		$this->assertStringNotContainsString( 'No merge fields configured', $output );
	}
}
