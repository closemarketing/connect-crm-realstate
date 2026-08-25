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
	 * Inmovilla sold listings use nodisponible with inverse semantics.
	 */
	public function test_inmovilla_unavailable_property_is_not_available() {
		$property = array(
			'cod_ofer'     => 123,
			'nodisponible' => 1,
		);

		$this->assertFalse( SYNC::is_property_available( $property, 'inmovilla' ) );
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
}
