<?php
/**
 * Tests for Sync::is_property_available().
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Sync availability test case.
 */
class SyncAvailabilityTest extends WP_UnitTestCase {

	/**
	 * APIWEB nodisponible values are normalized before the availability check.
	 *
	 * @dataProvider inmovilla_nodisponible_provider
	 *
	 * @param mixed $nodisponible APIWEB nodisponible value.
	 * @param bool  $expected      Expected availability.
	 */
	public function test_inmovilla_nodisponible_values( $nodisponible, $expected ) {
		$property = array(
			'cod_ofer'     => 123,
			'nodisponible' => $nodisponible,
		);

		$this->assertSame( $expected, SYNC::is_property_available( $property, 'inmovilla' ) );
	}

	/**
	 * Availability statistics ignore import-only filters.
	 */
	public function test_availability_can_skip_import_filters() {
		$property = array(
			'cod_ofer'     => 123,
			'nodisponible' => 0,
		);

		add_filter( 'ccrmre_should_import_property', '__return_false' );

		$this->assertFalse( SYNC::is_property_available( $property, 'inmovilla' ) );
		$this->assertTrue( SYNC::is_property_available( $property, 'inmovilla', false ) );

		remove_filter( 'ccrmre_should_import_property', '__return_false' );
	}

	/**
	 * Provides normal, null, and unexpected APIWEB availability values.
	 *
	 * @return array
	 */
	public function inmovilla_nodisponible_provider() {
		return array(
			'available integer'     => array( 0, true ),
			'sold integer'          => array( 1, false ),
			'available boolean'     => array( false, true ),
			'sold boolean'          => array( true, false ),
			'available string'      => array( '0', true ),
			'sold string'           => array( '1', false ),
			'null defaults to open' => array( null, true ),
			'unexpected value'      => array( 5, false ),
		);
	}

	/**
	 * The anonymized APIWEB sold-property fixture is treated as unavailable.
	 */
	public function test_inmovilla_apiweb_sold_fixture_is_not_available() {
		$path    = UNIT_TESTS_DATA_PLUGIN_DIR . 'inmovilla-apiweb-sold-property.json';
		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test fixture.
		$fixture  = json_decode( $content, true );
		$property = isset( $fixture['paginacion'][1] ) ? $fixture['paginacion'][1] : array();

		$this->assertSame( 'ANON-SOLD-001', $property['ref'] );
		$this->assertFalse( SYNC::is_property_available( $property, 'inmovilla' ) );
	}

	/**
	 * The anonymized APIWEB available-property fixture remains available.
	 */
	public function test_inmovilla_apiweb_available_fixture_is_available() {
		$path     = UNIT_TESTS_DATA_PLUGIN_DIR . 'inmovilla-apiweb-available-property.json';
		$content  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Test fixture.
		$fixture  = json_decode( $content, true );
		$property = isset( $fixture['paginacion'][1] ) ? $fixture['paginacion'][1] : array();

		$this->assertSame( 'ANON-AVAILABLE-001', $property['ref'] );
		$this->assertTrue( SYNC::is_property_available( $property, 'inmovilla' ) );
	}

	/**
	 * APIWEB unavailable properties include the nodisponible reason in sync output.
	 */
	public function test_inmovilla_apiweb_unavailable_property_includes_reason() {
		$post_id = self::factory()->post->create();
		add_post_meta( $post_id, 'ccrmre_property_id', 123 );

		$result = SYNC::handle_unavailable_property(
			array(
				'cod_ofer'     => 123,
				'ref'          => 'ANON-SOLD-001',
				'nodisponible' => 1,
			),
			array(
				'post_type'   => 'post',
				'sold_action' => 'keep',
			),
			array(),
			'inmovilla'
		);

		$this->assertStringContainsString( 'Reason: nodisponible = 1', $result['message'] );
	}
}
