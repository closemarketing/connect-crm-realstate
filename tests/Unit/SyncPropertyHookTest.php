<?php
/**
 * Tests for the post-property-sync extension hook.
 *
 * @package Connect_CRM_RealState
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Sync property hook test case.
 */
class SyncPropertyHookTest extends WP_UnitTestCase {

	/**
	 * The action fires after base media metadata is persisted.
	 *
	 * @return void
	 */
	public function test_property_synced_action_receives_complete_context_after_media_is_saved() {
		$settings = array(
			'type'            => 'inmovilla',
			'post_type'       => 'post',
			'download_images' => 'no',
		);
		$property = array(
			'cod_ofer'      => 100001,
			'ref'           => 'HOOK-TEST-001',
			'fechaact'      => '2026-09-21 12:00:00',
			'nodisponible'  => 0,
			'ciudad'        => 'Test city',
			'descripciones' => array(
				'titulo'  => 'Hook test property',
				'descrip' => 'Property content.',
			),
			'fotos'         => array( 'https://example.test/property.jpg' ),
		);
		$received = array();
		$callback = function ( $post_id, $synced_property, $synced_settings, $crm ) use ( &$received ) {
			$received = array(
				'post_id'         => $post_id,
				'property'        => $synced_property,
				'settings'        => $synced_settings,
				'crm'             => $crm,
				'gallery_urls'    => get_post_meta( $post_id, 'ccrmre_gallery_urls', true ),
				'featured_image'  => get_post_meta( $post_id, 'ccrmre_featured_image_url', true ),
			);
		};
		add_action( 'ccrmre_property_synced', $callback, 10, 4 );
		$post_id = 0;

		try {
			$result = SYNC::sync_property( $property, $settings, array() );
			$post_id = isset( $result['post_id'] ) ? (int) $result['post_id'] : 0;

			$this->assertSame( $post_id, $received['post_id'] );
			$this->assertSame( $property, $received['property'] );
			$this->assertSame( $settings, $received['settings'] );
			$this->assertSame( 'inmovilla', $received['crm'] );
			$this->assertSame( $property['fotos'], $received['gallery_urls'] );
			$this->assertSame( $property['fotos'][0], $received['featured_image'] );
		} finally {
			remove_action( 'ccrmre_property_synced', $callback, 10 );
			if ( ! empty( $post_id ) ) {
				wp_delete_post( $post_id, true );
			}
		}
	}
}
