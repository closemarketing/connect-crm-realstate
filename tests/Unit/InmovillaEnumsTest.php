<?php
/**
 * Tests for get_enums_inmovilla() (Inmovilla APIWEB)
 *
 * Command: composer test -- --filter=InmovillaEnumsTest
 *
 * @package Connect_CRM_RealState
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\API;
use WP_UnitTestCase;

/**
 * Test get_enums / get_enums_inmovilla for Inmovilla APIWEB CRM type.
 */
class InmovillaEnumsTest extends WP_UnitTestCase {

	/**
	 * Set up test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		API::set_skip_retry( true );

		update_option(
			'ccrmre_settings',
			array(
				'type'        => 'inmovilla',
				'numagencia'  => '9999',
				'apipassword' => 'test-password',
				'post_type'   => 'post',
			)
		);

		delete_transient( 'ccrmre_query_enums_inmovilla' );

		add_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10, 3 );
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		delete_transient( 'ccrmre_query_enums_inmovilla' );
		delete_option( 'ccrmre_settings' );
		API::set_skip_retry( false );
		parent::tearDown();
	}

	/**
	 * HTTP mock: routes APIWEB requests to the local JSON fixtures.
	 *
	 * The Inmovilla APIWEB encodes the request type as the 5th semicolon-separated
	 * field in the `param` POST body (e.g. "numagencia;password;1;lostipos;tipos;…").
	 *
	 * @param mixed  $pre  Short-circuit value.
	 * @param array  $args Request arguments.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public function mock_http_request( $pre, $args, $url ) {
		if ( false === strpos( $url, 'apiweb.inmovilla.com' ) ) {
			return array(
				'body'     => '',
				'response' => array(
					'code'    => 500,
					'message' => 'Mock: no external HTTP in tests',
				),
			);
		}

		// Extract the tipo from the encoded param field.
		$tipo = 'paginacion';
		if ( ! empty( $args['body'] ) && preg_match( '/param=([^&]+)/', $args['body'], $m ) ) {
			$param = rawurldecode( $m[1] );
			$parts = explode( ';', $param );
			if ( isset( $parts[4] ) ) {
				$tipo = $parts[4];
			}
		}

		$file_map = array(
			'tipos'    => 'inmovilla-enums-tipos.json',
			'ciudades' => 'inmovilla-enums-ciudades.json',
		);

		$file = isset( $file_map[ $tipo ] ) ? $file_map[ $tipo ] : null;

		if ( null === $file ) {
			return array(
				'body'     => '',
				'response' => array(
					'code'    => 500,
					'message' => 'Mock: unexpected tipo: ' . $tipo,
				),
			);
		}

		$path = UNIT_TESTS_DATA_PLUGIN_DIR . $file;

		if ( ! file_exists( $path ) ) {
			return array(
				'body'     => '',
				'response' => array(
					'code'    => 500,
					'message' => 'Mock: fixture file not found: ' . $file,
				),
			);
		}

		return array(
			'body'     => file_get_contents( $path ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
		);
	}

	// -------------------------------------------------------------------------
	// get_enums_inmovilla() — basic structure
	// -------------------------------------------------------------------------

	/**
	 * Returns an array with the expected enum groups.
	 */
	public function test_returns_all_enum_groups() {
		$enums = API::get_enums_inmovilla();

		$this->assertIsArray( $enums );
		$this->assertArrayHasKey( 'key_tipo', $enums );
		$this->assertArrayHasKey( 'key_loca', $enums );
	}

	/**
	 * Each enum group is a non-empty associative array.
	 */
	public function test_enum_groups_are_non_empty_arrays() {
		$enums = API::get_enums_inmovilla();

		$this->assertNotEmpty( $enums['key_tipo'] );
		$this->assertNotEmpty( $enums['key_loca'] );
	}

	// -------------------------------------------------------------------------
	// key_tipo — tipos de propiedad
	// -------------------------------------------------------------------------

	/**
	 * Key_tipo values are strings (human-readable labels), not numbers.
	 */
	public function test_key_tipo_values_are_strings() {
		$enums = API::get_enums_inmovilla();

		foreach ( $enums['key_tipo'] as $id => $label ) {
			$this->assertIsString( $label, "key_tipo[$id] should be a string label" );
		}
	}

	/**
	 * Key_tipo keys are numeric codes from the API (cod_tipo).
	 */
	public function test_key_tipo_keys_are_numeric() {
		$enums = API::get_enums_inmovilla();

		foreach ( array_keys( $enums['key_tipo'] ) as $id ) {
			$this->assertIsNumeric( $id, "key_tipo key [$id] should be numeric" );
		}
	}

	/**
	 * A known tipo from the fixture is resolved correctly.
	 * inmovilla-enums-tipos.json contains cod_tipo=199 => "Adosado".
	 */
	public function test_key_tipo_known_value_resolved() {
		$enums = API::get_enums_inmovilla();

		$this->assertArrayHasKey( 199, $enums['key_tipo'] );
		$this->assertEquals( 'Adosado', $enums['key_tipo'][199] );
	}

	/**
	 * Total number of tipos matches the fixture (69 entries, metadata row stripped).
	 */
	public function test_key_tipo_count_matches_fixture() {
		$enums = API::get_enums_inmovilla();

		$this->assertCount( 69, $enums['key_tipo'] );
	}

	// -------------------------------------------------------------------------
	// key_loca — ciudades
	// -------------------------------------------------------------------------

	/**
	 * Key_loca values are arrays with 'city' and 'state' keys.
	 */
	public function test_key_loca_values_are_arrays_with_city_and_state() {
		$enums = API::get_enums_inmovilla();

		foreach ( $enums['key_loca'] as $id => $value ) {
			$this->assertIsArray( $value, "key_loca[$id] should be an array" );
			$this->assertArrayHasKey( 'city', $value, "key_loca[$id] should have 'city' key" );
			$this->assertArrayHasKey( 'state', $value, "key_loca[$id] should have 'state' key" );
		}
	}

	/**
	 * A known city from the fixture resolves correctly.
	 * inmovilla-enums-ciudades.json contains cod_ciu=41999 => city="Pinoso", provincia="ALICANTE".
	 */
	public function test_key_loca_known_city_resolved() {
		$enums = API::get_enums_inmovilla();

		$this->assertArrayHasKey( 41999, $enums['key_loca'] );
		$this->assertEquals( 'Pinoso', $enums['key_loca'][41999]['city'] );
	}

	/**
	 * City entry includes the province in the 'state' key.
	 */
	public function test_key_loca_includes_province() {
		$enums = API::get_enums_inmovilla();

		$this->assertEquals( 'ALICANTE', $enums['key_loca'][41999]['state'] );
	}

	/**
	 * Total number of cities matches the fixture (226 entries, metadata stripped).
	 */
	public function test_key_loca_count_matches_fixture() {
		$enums = API::get_enums_inmovilla();

		$this->assertCount( 226, $enums['key_loca'] );
	}

	// -------------------------------------------------------------------------
	// get_enums() public facade
	// -------------------------------------------------------------------------

	/**
	 * Get_enums() with crm_type='inmovilla' delegates to get_enums_inmovilla().
	 */
	public function test_get_enums_facade_returns_same_as_get_enums_inmovilla() {
		$via_facade   = API::get_enums( 'inmovilla' );
		$via_internal = API::get_enums_inmovilla();

		$this->assertEquals( $via_internal, $via_facade );
	}

	/**
	 * Get_enums() with crm_type='anaconda' returns an empty array (not supported).
	 */
	public function test_get_enums_unsupported_crm_type_returns_empty() {
		$result = API::get_enums( 'anaconda' );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	// -------------------------------------------------------------------------
	// Caching — transient behaviour
	// -------------------------------------------------------------------------

	/**
	 * Result is stored in the transient after the first call.
	 */
	public function test_result_is_cached_in_transient() {
		API::get_enums_inmovilla();

		$cached = get_transient( 'ccrmre_query_enums_inmovilla' );
		$this->assertIsArray( $cached );
		$this->assertNotEmpty( $cached );
	}

	/**
	 * Second call returns the cached value without hitting the API again.
	 */
	public function test_second_call_uses_cache() {
		API::get_enums_inmovilla();

		$extra_request_made = false;
		add_filter(
			'pre_http_request',
			function ( $pre, $args, $url ) use ( &$extra_request_made ) {
				if ( false !== strpos( $url, 'apiweb.inmovilla.com' ) ) {
					$extra_request_made = true;
				}
				return $pre;
			},
			5,
			3
		);

		API::get_enums_inmovilla();

		$this->assertFalse( $extra_request_made, 'Second call should not make an HTTP request' );
	}

	/**
	 * Force_refresh=true bypasses the cache and re-fetches from the API.
	 */
	public function test_force_refresh_bypasses_cache() {
		set_transient( 'ccrmre_query_enums_inmovilla', array( 'key_tipo' => array( 1 => 'Stale' ) ), DAY_IN_SECONDS );

		$enums = API::get_enums_inmovilla( true );

		$this->assertArrayHasKey( 199, $enums['key_tipo'] );
		$this->assertEquals( 'Adosado', $enums['key_tipo'][199] );
	}

	// -------------------------------------------------------------------------
	// API error handling
	// -------------------------------------------------------------------------

	/**
	 * When the API returns errors, key_tipo and key_loca are absent from the result.
	 */
	public function test_api_error_returns_empty_enums() {
		remove_filter( 'pre_http_request', array( $this, 'mock_http_request' ), 10 );
		add_filter(
			'pre_http_request',
			function ( $_pre, $_args, $_url ) {
				return array(
					'body'     => '',
					'response' => array(
						'code'    => 500,
						'message' => 'Internal Server Error',
					),
				);
			},
			10,
			3
		);

		$enums = API::get_enums_inmovilla();

		$this->assertIsArray( $enums );
		$this->assertArrayNotHasKey( 'key_tipo', $enums );
		$this->assertArrayNotHasKey( 'key_loca', $enums );
	}
}
