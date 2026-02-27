<?php
/**
 * Tests for API Helper class
 *
 * Command: composer test-debug -- --filter=HelperApiTest
 *
 * @package Connect_CRM_RealState
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\API;
use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Test API Helper methods
 */
class HelperApiTest extends WP_UnitTestCase {

	/**
	 * Property list returned by the mocked API for get_unsynced tests.
	 * Null means: fall through to the file-based mock.
	 *
	 * @var array|null
	 */
	private $mock_api_properties = null;

	/**
	 * When true the HTTP mock returns a 500 error for every Inmovilla request.
	 *
	 * @var bool
	 */
	private $mock_api_error = false;

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mock_api_properties = null;
		$this->mock_api_error      = false;
		$this->cleanup_properties();

		update_option(
			'conncrmreal_settings',
			array(
				'type'        => 'inmovilla_procesos',
				'apipassword' => 'test',
				'post_type'   => 'property',
			)
		);
		update_option( 'conncrmreal_merge_fields', array() );
		delete_transient( 'ccrmre_query_inmovilla_procesos_ciudades' );

		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		$this->cleanup_properties();
		parent::tearDown();
	}

	/**
	 * HTTP mock: intercepts every Inmovilla Procesos API request.
	 *
	 * Priority (checked in order):
	 *  1. If mock_api_error is true → always return 500.
	 *  2. If mock_api_properties is set → return that JSON for any propiedades endpoint.
	 *  3. Fall through to file-based mock (used by the original get_property_info / get_enums tests).
	 *
	 * @param mixed  $pre  Short-circuit value.
	 * @param array  $args Request arguments.
	 * @param string $url  Request URL.
	 * @return mixed
	 */
	public function mock_http_request( $pre, $args, $url ) {
		if ( 0 !== strpos( $url, 'https://procesos.inmovilla.com/api/v1/' ) ) {
			return $pre;
		}

		if ( $this->mock_api_error ) {
			return array(
				'body'     => '',
				'response' => array( 'code' => 500, 'message' => 'Internal Server Error' ),
			);
		}

		if ( null !== $this->mock_api_properties && false !== strpos( $url, 'propiedades/' ) ) {
			return array(
				'body'     => wp_json_encode( $this->mock_api_properties ),
				'response' => array( 'code' => 200, 'message' => 'OK' ),
			);
		}

		// File-based mock (existing tests).
		$endpoint      = str_replace( 'https://procesos.inmovilla.com/api/v1/', '', $url );
		$endpoint      = str_replace( array( '?', '=', '/' ), '-', $endpoint );
		$response_file = UNIT_TESTS_DATA_PLUGIN_DIR . 'inmovilla-procesos-' . $endpoint . '.json';

		if ( file_exists( $response_file ) ) {
			return array(
				'body'     => file_get_contents( $response_file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				'response' => array( 'code' => 200, 'message' => 'OK' ),
			);
		}

		return array(
			'body'     => '',
			'response' => array( 'code' => 500, 'message' => 'Error API' ),
		);
	}
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

	public function test_get_enums_inmovilla_procesos() {
		$result = API::get_enums( 'inmovilla_procesos', 'key_loca' );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'key_loca', $result );
		$this->assertArrayHasKey( 368799, $result['key_loca'] );
		$this->assertEquals( 'A Baña', $result['key_loca'][368799]['city'] );
		$this->assertEquals( 'A CORUÑA', $result['key_loca'][368799]['state'] );
	}

	/**
	 * When WordPress has no properties, every API property is unsynced.
	 */
	public function test_returns_all_when_wordpress_is_empty() {
		$this->mock_api_properties = $this->make_api_properties( array( 1001, 1002, 1003 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertCount( 3, $result['data'] );
		$this->assertContains( '1001', array_map( 'strval', $result['data'] ) );
		$this->assertContains( '1002', array_map( 'strval', $result['data'] ) );
		$this->assertContains( '1003', array_map( 'strval', $result['data'] ) );
	}

	/**
	 * Only IDs missing from WordPress are returned.
	 */
	public function test_returns_only_missing_ids() {
		$this->mock_api_properties = $this->make_api_properties( array( 1001, 1002, 1003 ) );

		// Import 1001 and 1002 – 1003 stays unsynced.
		$this->create_wp_properties( array( 1001, 1002 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertCount( 1, $result['data'] );
		$this->assertContains( '1003', array_map( 'strval', $result['data'] ) );
	}

	/**
	 * When every API property already exists in WordPress, the result is empty.
	 */
	public function test_returns_empty_when_all_synced() {
		$this->mock_api_properties = $this->make_api_properties( array( 1001, 1002, 1003 ) );
		$this->create_wp_properties( array( 1001, 1002, 1003 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertIsArray( $result['data'] );
		$this->assertEmpty( $result['data'] );
	}

	/**
	 * When the API returns no properties, the result is an empty list.
	 */
	public function test_returns_empty_when_api_has_no_properties() {
		$this->mock_api_properties = array();

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertIsArray( $result['data'] );
		$this->assertEmpty( $result['data'] );
	}

	/**
	 * Extra WordPress properties (not in API) do not appear as unsynced.
	 */
	public function test_ignores_wp_properties_not_in_api() {
		$this->mock_api_properties = $this->make_api_properties( array( 1001 ) );

		// WP has 1001 (synced) + 9999 (orphan, not in API).
		$this->create_wp_properties( array( 1001, 9999 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertEmpty( $result['data'], '9999 is in WP but not in API; should not be listed as unsynced' );
	}

	/**
	 * The exact set of unsynced IDs matches the diff between API and WP.
	 */
	public function test_returns_correct_ids_from_diff() {
		$this->mock_api_properties = $this->make_api_properties( array( 10, 20, 30, 40, 50 ) );
		$this->create_wp_properties( array( 10, 30, 50 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$ids = array_map( 'strval', $result['data'] );
		sort( $ids );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertCount( 2, $ids );
		$this->assertEquals( array( '20', '40' ), $ids );
	}

	/**
	 * A single unsynced property is returned correctly.
	 */
	public function test_handles_single_unsynced_property() {
		$this->mock_api_properties = $this->make_api_properties( array( 7777 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertCount( 1, $result['data'] );
		$this->assertContains( '7777', array_map( 'strval', $result['data'] ) );
	}

	/**
	 * A large number of unsynced properties is handled without issues.
	 */
	public function test_handles_large_number_of_unsynced_properties() {
		$ids = range( 1, 200 );

		$this->mock_api_properties = $this->make_api_properties( $ids );

		// Sync only the first 50; the remaining 150 should be unsynced.
		$this->create_wp_properties( array_slice( $ids, 0, 50 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'ok', $result['status'] );
		$this->assertCount( 150, $result['data'] );
	}

	// -------------------------------------------------------------------------
	// Tests: response structure
	// -------------------------------------------------------------------------

	/**
	 * The return value always has 'status' and 'data' keys.
	 */
	public function test_response_has_required_keys() {
		$this->mock_api_properties = $this->make_api_properties( array( 1001 ) );

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'status', $result );
		$this->assertArrayHasKey( 'data', $result );
	}

	/**
	 * 'data' is always an array (never null).
	 */
	public function test_data_is_always_array() {
		$this->mock_api_properties = array();

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertIsArray( $result['data'] );
	}

	// -------------------------------------------------------------------------
	// Tests: error handling
	// -------------------------------------------------------------------------

	/**
	 * When the API returns an HTTP error, the method propagates the error.
	 */
	public function test_propagates_api_http_error() {
		$this->mock_api_error = true;

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'error', $result['status'] );
	}

	/**
	 * When the API key is not configured, an error is returned immediately.
	 */
	public function test_returns_error_when_api_key_missing() {
		update_option(
			'conncrmreal_settings',
			array(
				'type'      => 'inmovilla_procesos',
				'post_type' => 'property',
				// No apipassword.
			)
		);

		$result = SYNC::get_unsynced_property_ids( 'inmovilla_procesos' );

		$this->assertEquals( 'error', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Builds an array of raw Inmovilla Procesos property items from a list of cod_ofer values.
	 *
	 * @param int[] $cod_ofer_list List of cod_ofer identifiers.
	 * @return array
	 */
	private function make_api_properties( array $cod_ofer_list ) {
		return array_map(
			function ( $cod_ofer ) {
				return array(
					'cod_ofer'     => $cod_ofer,
					'ref'          => 'REF' . $cod_ofer,
					'nodisponible' => false,
					'fechaact'     => '2024-01-01 10:00:00',
				);
			},
			$cod_ofer_list
		);
	}

	/**
	 * Creates WordPress property posts with the given cod_ofer values.
	 *
	 * Uses 'ccrmre_property_id' as the fixed meta key for property identification.
	 *
	 * @param int[] $cod_ofer_list List of cod_ofer values to store in WP.
	 */
	private function create_wp_properties( array $cod_ofer_list ) {
		foreach ( $cod_ofer_list as $cod_ofer ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'property',
					'post_title'  => 'Property ' . $cod_ofer,
					'post_status' => 'publish',
				)
			);

			update_post_meta( $post_id, 'ccrmre_property_id', (string) $cod_ofer );
			update_post_meta( $post_id, 'ccrmre_last_updated', '2024-01-01 10:00:00' );
		}

		delete_transient( 'ccrmre_wp_properties_inmovilla_procesos' );
	}

	/**
	 * Removes all property posts and clears related caches.
	 */
	private function cleanup_properties() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'property'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		delete_transient( 'ccrmre_wp_properties_anaconda' );
		delete_transient( 'ccrmre_wp_properties_inmovilla' );
		delete_transient( 'ccrmre_wp_properties_inmovilla_procesos' );

		wp_cache_flush();
	}

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

	// -------------------------------------------------------------------------
	// Tests: SYNC::get_property_content
	// -------------------------------------------------------------------------

	/**
	 * Inmovilla Procesos: all fields present.
	 */
	public function test_get_property_content_inmovilla_procesos_all_fields() {
		$item = array(
			'tituloes'     => 'Casa en la playa',
			'descripciones' => 'Amplia casa con vistas al mar.',
			'ciudad'       => 'Barcelona',
		);

		$result = SYNC::get_property_content( $item, 'inmovilla_procesos' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'Casa en la playa', $result['title'] );
		$this->assertEquals( 'Amplia casa con vistas al mar.', $result['description'] );
		$this->assertEquals( 'Barcelona', $result['city'] );
	}

	/**
	 * Inmovilla Procesos: missing all optional fields uses defaults.
	 */
	public function test_get_property_content_inmovilla_procesos_missing_fields() {
		$result = SYNC::get_property_content( array(), 'inmovilla_procesos' );

		$this->assertEquals( 'Property', $result['title'] );
		$this->assertEquals( '', $result['description'] );
		$this->assertEquals( '', $result['city'] );
	}

	/**
	 * Inmovilla APIWEB: all fields present inside the descripciones array.
	 */
	public function test_get_property_content_inmovilla_all_fields() {
		$item = array(
			'descripciones' => array(
				'titulo' => 'Piso moderno',
				'descrip' => 'Piso reformado en el centro.',
			),
			'ciudad' => 'Madrid',
		);

		$result = SYNC::get_property_content( $item, 'inmovilla' );

		$this->assertEquals( 'Piso moderno', $result['title'] );
		$this->assertEquals( 'Piso reformado en el centro.', $result['description'] );
		$this->assertEquals( 'Madrid', $result['city'] );
	}

	/**
	 * Inmovilla APIWEB: descripciones is empty array, title falls back to default.
	 */
	public function test_get_property_content_inmovilla_empty_descripciones() {
		$item = array(
			'descripciones' => array(),
			'ciudad'        => 'Sevilla',
		);

		$result = SYNC::get_property_content( $item, 'inmovilla' );

		$this->assertEquals( 'Property', $result['title'] );
		$this->assertEquals( '', $result['description'] );
		$this->assertEquals( 'Sevilla', $result['city'] );
	}

	/**
	 * Inmovilla APIWEB: descripciones key is absent.
	 */
	public function test_get_property_content_inmovilla_missing_descripciones() {
		$result = SYNC::get_property_content( array(), 'inmovilla' );

		$this->assertEquals( 'Property', $result['title'] );
		$this->assertEquals( '', $result['description'] );
		$this->assertEquals( '', $result['city'] );
	}

	/**
	 * Anaconda: all fields present.
	 */
	public function test_get_property_content_anaconda_all_fields() {
		$item = array(
			'name'        => 'Villa con piscina',
			'description' => 'Amplia villa con piscina privada.',
			'city'        => 'Marbella',
		);

		$result = SYNC::get_property_content( $item, 'anaconda' );

		$this->assertEquals( 'Villa con piscina', $result['title'] );
		$this->assertEquals( 'Amplia villa con piscina privada.', $result['description'] );
		$this->assertEquals( 'Marbella', $result['city'] );
	}

	/**
	 * Anaconda: missing fields use defaults.
	 */
	public function test_get_property_content_anaconda_missing_fields() {
		$result = SYNC::get_property_content( array(), 'anaconda' );

		$this->assertEquals( 'Property', $result['title'] );
		$this->assertEquals( '', $result['description'] );
		$this->assertEquals( '', $result['city'] );
	}

	/**
	 * Unknown CRM type falls through to Anaconda defaults.
	 */
	public function test_get_property_content_unknown_crm_uses_anaconda_defaults() {
		$item = array(
			'name' => 'Any property',
			'city' => 'Valencia',
		);

		$result = SYNC::get_property_content( $item, 'unknown_crm' );

		$this->assertEquals( 'Any property', $result['title'] );
		$this->assertEquals( '', $result['description'] );
		$this->assertEquals( 'Valencia', $result['city'] );
	}

	/**
	 * Return value always contains the three expected keys.
	 */
	public function test_get_property_content_always_has_required_keys() {
		foreach ( array( 'inmovilla_procesos', 'inmovilla', 'anaconda' ) as $crm ) {
			$result = SYNC::get_property_content( array(), $crm );

			$this->assertArrayHasKey( 'title', $result, "Missing 'title' for CRM: $crm" );
			$this->assertArrayHasKey( 'description', $result, "Missing 'description' for CRM: $crm" );
			$this->assertArrayHasKey( 'city', $result, "Missing 'city' for CRM: $crm" );
		}
	}
}

