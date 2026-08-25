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
	 * Inmovilla available listings remain available after normalization.
	 */
	public function test_inmovilla_available_property_is_available() {
		$property = array(
			'cod_ofer'     => 123,
			'nodisponible' => 0,
		);

		$this->assertTrue( SYNC::is_property_available( $property, 'inmovilla' ) );
	}
}
