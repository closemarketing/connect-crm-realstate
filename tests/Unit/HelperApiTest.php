<?php
/**
 * Tests for API Helper class
 *
 * @package Connect_CRM_RealState
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\API;
use WP_UnitTestCase;

/**
 * Test API Helper methods
 */
class HelperApiTest extends WP_UnitTestCase {

	/**
	 * Test get_property_info with Anaconda CRM and all fields present
	 */
	public function test_get_property_info_anaconda_all_fields() {
		$property = array(
			'id'         => '12345',
			'referencia' => 'REF-ABC-001',
			'updated_at' => '2024-01-20 10:30:00',
			'titulo'     => 'Test Property',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( '12345', $result['id'] );
		$this->assertEquals( 'REF-ABC-001', $result['reference'] );
		$this->assertEquals( '2024-01-20 10:30:00', $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Anaconda CRM and missing fields
	 */
	public function test_get_property_info_anaconda_missing_fields() {
		$property = array(
			'id'     => '12345',
			'titulo' => 'Test Property',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( '12345', $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Anaconda CRM and empty property
	 */
	public function test_get_property_info_anaconda_empty_property() {
		$property = array();

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertNull( $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Inmovilla CRM and all fields present
	 */
	public function test_get_property_info_inmovilla_all_fields() {
		$property = array(
			'cod_ofer'   => 'INM-2024-001',
			'referencia' => 'REF-INM-001',
			'fechaact'   => '2024-01-20',
			'titulo'     => 'Inmovilla Property',
		);

		$result = API::get_property_info( $property, 'inmovilla' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'INM-2024-001', $result['id'] );
		$this->assertEquals( 'REF-INM-001', $result['reference'] );
		$this->assertEquals( '2024-01-20', $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Inmovilla CRM and missing reference
	 */
	public function test_get_property_info_inmovilla_missing_reference() {
		$property = array(
			'cod_ofer' => 'INM-2024-001',
			'fechaact' => '2024-01-20',
		);

		$result = API::get_property_info( $property, 'inmovilla' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'INM-2024-001', $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertEquals( '2024-01-20', $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Inmovilla CRM and missing date
	 */
	public function test_get_property_info_inmovilla_missing_date() {
		$property = array(
			'cod_ofer'   => 'INM-2024-001',
			'referencia' => 'REF-INM-001',
		);

		$result = API::get_property_info( $property, 'inmovilla' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'INM-2024-001', $result['id'] );
		$this->assertEquals( 'REF-INM-001', $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Inmovilla Procesos CRM and all fields present
	 */
	public function test_get_property_info_inmovilla_procesos_all_fields() {
		$property = array(
			'cod_ofer' => 'PROC-2024-001',
			'fechaact' => '2024-01-20 15:45:00',
			'titulo'   => 'Procesos Property',
		);

		$result = API::get_property_info( $property, 'inmovilla_procesos' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'PROC-2024-001', $result['id'] );
		$this->assertNull( $result['reference'] ); // Procesos doesn't have reference field.
		$this->assertEquals( '2024-01-20 15:45:00', $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Inmovilla Procesos CRM and missing date
	 */
	public function test_get_property_info_inmovilla_procesos_missing_date() {
		$property = array(
			'cod_ofer' => 'PROC-2024-001',
			'titulo'   => 'Procesos Property',
		);

		$result = API::get_property_info( $property, 'inmovilla_procesos' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'PROC-2024-001', $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with Inmovilla Procesos CRM and empty property
	 */
	public function test_get_property_info_inmovilla_procesos_empty() {
		$property = array();

		$result = API::get_property_info( $property, 'inmovilla_procesos' );

		$this->assertIsArray( $result );
		$this->assertNull( $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with unknown CRM type
	 */
	public function test_get_property_info_unknown_crm() {
		$property = array(
			'id'         => '12345',
			'referencia' => 'REF-001',
			'updated_at' => '2024-01-20',
		);

		$result = API::get_property_info( $property, 'unknown_crm' );

		$this->assertIsArray( $result );
		$this->assertNull( $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with null values in property
	 */
	public function test_get_property_info_with_null_values() {
		$property = array(
			'id'         => null,
			'referencia' => null,
			'updated_at' => null,
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertNull( $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with empty string values
	 */
	public function test_get_property_info_with_empty_strings() {
		$property = array(
			'id'         => '',
			'referencia' => '',
			'updated_at' => '',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( '', $result['id'] );
		$this->assertEquals( '', $result['reference'] );
		$this->assertEquals( '', $result['last_updated'] );
	}

	/**
	 * Test get_property_info with numeric ID for Anaconda
	 */
	public function test_get_property_info_anaconda_numeric_id() {
		$property = array(
			'id'         => 123456,
			'referencia' => 'REF-123',
			'updated_at' => '2024-01-20',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( 123456, $result['id'] );
		$this->assertEquals( 'REF-123', $result['reference'] );
	}

	/**
	 * Test get_property_info with special characters in property data
	 */
	public function test_get_property_info_with_special_characters() {
		$property = array(
			'id'         => 'ID-ÑÁÉ-001',
			'referencia' => 'REF-€$@-002',
			'updated_at' => '2024-01-20 10:30:00',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'ID-ÑÁÉ-001', $result['id'] );
		$this->assertEquals( 'REF-€$@-002', $result['reference'] );
		$this->assertEquals( '2024-01-20 10:30:00', $result['last_updated'] );
	}

	/**
	 * Test get_property_info preserves data types
	 */
	public function test_get_property_info_preserves_data_types() {
		// Test with integer ID.
		$property_int = array(
			'id'         => 999,
			'referencia' => 'REF-999',
			'updated_at' => '2024-01-20',
		);

		$result_int = API::get_property_info( $property_int, 'anaconda' );
		$this->assertIsInt( $result_int['id'] );

		// Test with string ID.
		$property_str = array(
			'id'         => '999',
			'referencia' => 'REF-999',
			'updated_at' => '2024-01-20',
		);

		$result_str = API::get_property_info( $property_str, 'anaconda' );
		$this->assertIsString( $result_str['id'] );
	}

	/**
	 * Test get_property_info with only ID present for Anaconda
	 */
	public function test_get_property_info_anaconda_only_id() {
		$property = array(
			'id' => '12345',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( '12345', $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with only reference present for Anaconda
	 */
	public function test_get_property_info_anaconda_only_reference() {
		$property = array(
			'referencia' => 'REF-001',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertNull( $result['id'] );
		$this->assertEquals( 'REF-001', $result['reference'] );
		$this->assertNull( $result['last_updated'] );
	}

	/**
	 * Test get_property_info with only date present for Anaconda
	 */
	public function test_get_property_info_anaconda_only_date() {
		$property = array(
			'updated_at' => '2024-01-20',
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertNull( $result['id'] );
		$this->assertNull( $result['reference'] );
		$this->assertEquals( '2024-01-20', $result['last_updated'] );
	}

	/**
	 * Test get_property_info with extra fields that should be ignored
	 */
	public function test_get_property_info_ignores_extra_fields() {
		$property = array(
			'id'          => '12345',
			'referencia'  => 'REF-001',
			'updated_at'  => '2024-01-20',
			'titulo'      => 'Should be ignored',
			'descripcion' => 'Should also be ignored',
			'precio'      => 100000,
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertCount( 4, $result ); // Should have id, reference, last_updated, status.
		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'reference', $result );
		$this->assertArrayHasKey( 'last_updated', $result );
		$this->assertArrayHasKey( 'status', $result );
		$this->assertArrayNotHasKey( 'titulo', $result );
		$this->assertArrayNotHasKey( 'descripcion', $result );
		$this->assertArrayNotHasKey( 'precio', $result );
	}

	/**
	 * Test get_property_info with very long strings
	 */
	public function test_get_property_info_with_long_strings() {
		$long_id        = str_repeat( 'A', 1000 );
		$long_reference = str_repeat( 'B', 1000 );
		$long_date      = str_repeat( 'C', 100 );

		$property = array(
			'id'         => $long_id,
			'referencia' => $long_reference,
			'updated_at' => $long_date,
		);

		$result = API::get_property_info( $property, 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEquals( $long_id, $result['id'] );
		$this->assertEquals( $long_reference, $result['reference'] );
		$this->assertEquals( $long_date, $result['last_updated'] );
	}
}
