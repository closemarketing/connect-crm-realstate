<?php
/**
 * Tests for SYNC::filter_active_properties() method
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Test case for filter_active_properties() method
 */
class FilterActivePropertiesTest extends WP_UnitTestCase {

	/**
	 * Test filtering properties with all active status
	 */
	public function test_filter_all_active_properties() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => true,
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => '1',
			),
			'PROP003' => array(
				'last_updated' => '2024-01-03 10:00:00',
				'status'       => 1,
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 3, $filtered, 'Should return all 3 active properties' );
		$this->assertEquals( array( 'PROP001', 'PROP002', 'PROP003' ), $filtered );
	}

	/**
	 * Test filtering properties with all inactive status
	 */
	public function test_filter_all_inactive_properties() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => false,
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => '0',
			),
			'PROP003' => array(
				'last_updated' => '2024-01-03 10:00:00',
				'status'       => 0,
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 0, $filtered, 'Should return empty array when all properties are inactive' );
		$this->assertIsArray( $filtered, 'Should return array type' );
	}

	/**
	 * Test filtering properties with mixed status
	 */
	public function test_filter_mixed_status_properties() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => true,
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => false,
			),
			'PROP003' => array(
				'last_updated' => '2024-01-03 10:00:00',
				'status'       => '1',
			),
			'PROP004' => array(
				'last_updated' => '2024-01-04 10:00:00',
				'status'       => '0',
			),
			'PROP005' => array(
				'last_updated' => '2024-01-05 10:00:00',
				'status'       => 1,
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 3, $filtered, 'Should return only 3 active properties' );
		$this->assertEquals( array( 'PROP001', 'PROP003', 'PROP005' ), $filtered );
	}

	/**
	 * Test filtering with empty properties array
	 */
	public function test_filter_empty_properties() {
		$properties = array();

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 0, $filtered, 'Should return empty array when input is empty' );
		$this->assertIsArray( $filtered, 'Should return array type' );
	}

	/**
	 * Test filtering properties with missing status field
	 */
	public function test_filter_properties_with_missing_status() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => true,
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 1, $filtered, 'Should only return property with explicit active status' );
		$this->assertEquals( array( 'PROP002' ), $filtered );
	}

	/**
	 * Test filtering properties with null status
	 */
	public function test_filter_properties_with_null_status() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => null,
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => true,
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 1, $filtered, 'Should exclude properties with null status' );
		$this->assertEquals( array( 'PROP002' ), $filtered );
	}

	/**
	 * Test filtering works regardless of CRM type
	 */
	public function test_filter_with_different_crm_types() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => true,
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => false,
			),
		);

		// Test filtering works regardless of CRM type.
		$filtered = SYNC::filter_active_properties( $properties );
		$this->assertCount( 1, $filtered, 'Should filter active properties' );
		$this->assertEquals( array( 'PROP001' ), $filtered );
	}

	/**
	 * Test filtering preserves property ID order
	 */
	public function test_filter_preserves_order() {
		$properties = array(
			'PROP003' => array(
				'last_updated' => '2024-01-03 10:00:00',
				'status'       => true,
			),
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => true,
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => false,
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertEquals( array( 'PROP003', 'PROP001' ), $filtered, 'Should preserve original array order' );
	}

	/**
	 * Test filtering with string status values
	 */
	public function test_filter_with_string_status_values() {
		$properties = array(
			'PROP001' => array(
				'last_updated' => '2024-01-01 10:00:00',
				'status'       => 'yes',
			),
			'PROP002' => array(
				'last_updated' => '2024-01-02 10:00:00',
				'status'       => 'no',
			),
			'PROP003' => array(
				'last_updated' => '2024-01-03 10:00:00',
				'status'       => '',
			),
		);

		$filtered = SYNC::filter_active_properties( $properties );

		$this->assertCount( 2, $filtered, 'Should return non-empty string status values' );
		$this->assertEquals( array( 'PROP001', 'PROP002' ), $filtered );
	}
}
