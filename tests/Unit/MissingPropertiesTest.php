<?php
/**
 * Tests for properties that disappear from the CRM listing.
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Missing-property reconciliation test case.
 */
class MissingPropertiesTest extends WP_UnitTestCase {

	/**
	 * Applies the configured unavailable action when a property is absent from the API.
	 *
	 * @dataProvider unavailable_action_provider
	 *
	 * @param string $sold_action Configured action for unavailable properties.
	 * @param string $expected_status Expected WordPress post status.
	 */
	public function test_missing_property_uses_configured_unavailable_action( $sold_action, $expected_status ) {
		update_option(
			'ccrmre_settings',
			array(
				'post_type'   => 'post',
				'sold_action' => $sold_action,
			)
		);
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, 'ccrmre_property_id', 'MISSING-' . $sold_action );

		set_transient(
			'ccrmre_query_property_ids_inmovilla',
			array(
				'AVAILABLE-1' => array( 'status' => true ),
			),
			MINUTE_IN_SECONDS
		);

		$result = SYNC::remove_properties_not_in_api( 'inmovilla' );

		$this->assertSame( 1, $result['count'] );
		$this->assertSame( $sold_action, $result['details'][0]['action'] );
		$this->assertSame( $expected_status, get_post_status( $post_id ) );
	}

	/**
	 * Provides supported unavailable actions and their expected post statuses.
	 *
	 * @return array
	 */
	public function unavailable_action_provider() {
		return array(
			'draft' => array( 'draft', 'draft' ),
			'trash' => array( 'trash', 'trash' ),
			'keep'  => array( 'keep', 'publish' ),
		);
	}

	/**
	 * A listed property with an unknown status is not treated as missing.
	 */
	public function test_listed_property_without_status_remains_published() {
		update_option( 'ccrmre_settings', array( 'post_type' => 'post' ) );
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, 'ccrmre_property_id', 'LISTED-UNKNOWN' );

		set_transient(
			'ccrmre_query_property_ids_inmovilla',
			array(
				'LISTED-UNKNOWN' => array( 'status' => null ),
			),
			MINUTE_IN_SECONDS
		);

		$result = SYNC::remove_properties_not_in_api( 'inmovilla' );

		$this->assertSame( 0, $result['count'] );
		$this->assertSame( 'publish', get_post_status( $post_id ) );
	}
}
