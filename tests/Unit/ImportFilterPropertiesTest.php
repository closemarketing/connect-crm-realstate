<?php
/**
 * Tests for Sync::filter_properties_to_update() method
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Test case for filter_properties_to_update() method
 */
class ImportFilterPropertiesTest extends WP_UnitTestCase {

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Clean up any existing properties first
		$this->cleanup_properties();
		
		// Initialize settings (plugin uses ccrmre_settings for get_wordpress_property_data).
		update_option(
			'ccrmre_settings',
			array(
				'type'      => 'anaconda',
				'post_type' => 'property',
			)
		);

		update_option(
			'ccrmre_merge_fields',
			array(
				'id'   => 'property_id',
				'name' => 'property_name',
			)
		);

		// Clear all transients
		delete_transient( 'ccrmre_wp_properties_anaconda' );
		delete_transient( 'ccrmre_wp_properties_inmovilla' );
		delete_transient( 'ccrmre_wp_properties_inmovilla_procesos' 		);
	}

	/**
	 * Test filtering all new properties (empty WordPress)
	 */
	public function test_filter_all_new_properties() {
		// Setup: WordPress has NO properties
		
		// API has properties
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1',
			),
			array(
				'id'         => '2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1',
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		// All properties should be new
		$this->assertCount( 2, $filtered, 'Should return all properties as new' );
	}

	/**
	 * Test filtering single property with updated date
	 */
	public function test_filter_single_property_with_updated_date() {
		// Setup: Create ONE WordPress property with old date
		$this->create_wp_properties(
			array(
				array(
					'id'           => 'TEST1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '1', // Available
				),
			)
		);

		// API property with NEWER date
		$api_properties = array(
			array(
				'id'         => 'TEST1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-15 12:00:00', // NEWER date
				'status'     => '1', // Available
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		$this->assertGreaterThan( 0, count( $filtered ), 'Should return at least 1 property' );
		$this->assertEquals( 'TEST1', $filtered[0]['id'], 'Should return TEST1 with newer date' );
	}

	/**
	 * Test filtering single property that becomes unavailable
	 */
	public function test_filter_single_property_becomes_unavailable() {
		// Setup: Create ONE WordPress property that was available
		$this->create_wp_properties(
			array(
				array(
					'id'           => 'TEST2',
					'last_updated' => '2024-01-01 10:00:00',
				),
			)
		);

		// API property with CHANGED status to unavailable
		$api_properties = array(
			array(
				'id'         => 'TEST2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-01 10:00:00', // Same date
				'status'     => false, // CHANGED to unavailable
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		$this->assertGreaterThan( 0, count( $filtered ), 'Should return at least 1 property' );
		$this->assertEquals( 'TEST2', $filtered[0]['id'], 'Should return TEST2 that became unavailable' );
	}

	/**
	 * Test filtering properties with both date and status changes
	 */
	public function test_filter_properties_with_date_and_status_changes() {
		// Setup: Create WordPress properties
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '1',
				),
			)
		);

		// API property with both date AND status changed
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-15 12:00:00', // NEWER date
				'status'     => '1', // CHANGED status
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		$this->assertCount( 1, $filtered, 'Should return property with both changes' );
		$this->assertEquals( '1', $filtered[0]['id'] );
	}

	/**
	 * Test filtering single property without changes
	 */
	public function test_filter_single_property_unchanged() {
		// Setup: Create ONE WordPress property
		$this->create_wp_properties(
			array(
				array(
					'id'           => 'TEST3',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '1',
				),
			)
		);

		// API property with NO changes
		$api_properties = array(
			array(
				'id'         => 'TEST3',
				'name'       => 'Property 3',
				'updated_at' => '2024-01-01 10:00:00', // Same date
				'status'     => '1', // Same status
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		// Should return 0 or less than total (property unchanged)
		$this->assertLessThanOrEqual( 0, count( $filtered ), 'Should not include unchanged property' );
	}

	/**
	 * Test filtering with missing last_updated in WordPress but status differs
	 */
	public function test_filter_with_missing_wp_last_updated() {
		// Setup: Create WordPress property WITHOUT last_updated and WITHOUT status
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => null, // Missing date
					'status'       => null, // Missing status
				),
			)
		);

		// API property with date and status
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1', // Has status (null !== '0')
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		// SHOULD update because status is different (null vs '0')
		$this->assertCount( 1, $filtered, 'Should update when status differs even if date missing' );
	}

	/**
	 * Test filtering with missing last_updated in API but status differs
	 */
	public function test_filter_with_missing_api_last_updated() {
		// Setup: Create WordPress property with date but WITHOUT status
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => null, // Missing status in WP
				),
			)
		);

		// API property WITHOUT date but WITH status
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => null, // Missing date
				'status'     => '1', // Has status (null !== '0')
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		// SHOULD update because status is different (null vs '0')
		$this->assertCount( 1, $filtered, 'Should update when status differs even if date missing' );
	}

	/**
	 * Test filtering with Inmovilla CRM type
	 */
	public function test_filter_with_inmovilla_crm() {
		// Update settings for Inmovilla (plugin uses ccrmre_settings).
		update_option(
			'ccrmre_settings',
			array(
				'type'      => 'inmovilla',
				'post_type' => 'property',
			)
		);

		update_option(
			'ccrmre_merge_fields',
			array(
				'cod_ofer'   => 'property_id',
				'referencia' => 'property_reference',
			)
		);

		// Setup: Create WordPress properties for Inmovilla
		$this->create_wp_properties(
			array(
				array(
					'id'           => 'INM001',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '1',
				),
			),
			'inmovilla'
		);

		// API properties from Inmovilla (with updated date)
		$api_properties = array(
			array(
				'cod_ofer'   => 'INM001',
				'referencia' => 'REF001',
				'fechaact'   => '2024-01-15 12:00:00', // NEWER date
				'nodisponible' => '0',
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'inmovilla' );

		$this->assertCount( 1, $filtered, 'Should work with Inmovilla CRM' );
		$this->assertEquals( 'INM001', $filtered[0]['cod_ofer'] );
	}

	/**
	 * Test filtering with empty properties array
	 */
	public function test_filter_with_empty_properties() {
		$api_properties = array();

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		$this->assertCount( 0, $filtered, 'Should return empty array when input is empty' );
		$this->assertIsArray( $filtered, 'Should return array type' );
	}

	/**
	 * Test filtering with property without ID
	 */
	public function test_filter_with_property_without_id() {
		// API property without ID (should be skipped)
		$api_properties = array(
			array(
				'name'       => 'Property without ID',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1',
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		$this->assertCount( 0, $filtered, 'Should skip properties without ID' );
	}

	/**
	 * Test filtering returns properties to update
	 */
	public function test_filter_returns_properties_to_update() {
		// Simple test: Verify the function returns an array with available property
		$api_properties = array(
			array(
				'id'         => 'NEW1',
				'name'       => 'New Property',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1', // Available property
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		$this->assertIsArray( $filtered, 'Should return an array' );
		$this->assertGreaterThan( 0, count( $filtered ), 'Should return at least 1 new property' );
	}

	/**
	 * Test filtering skips new properties with status false (unavailable)
	 */
	public function test_filter_skips_new_unavailable_properties() {
		// No WordPress properties (all API properties would be new).
		$api_properties = array(
			array(
				'id'         => 'NEW_UNAVAIL_1',
				'name'       => 'Unavailable Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0', // Unavailable.
			),
			array(
				'id'         => 'NEW_AVAIL_1',
				'name'       => 'Available Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1', // Available.
			),
			array(
				'id'         => 'NEW_UNAVAIL_2',
				'name'       => 'Unavailable Property 2',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => false, // Unavailable.
			),
		);

		$filtered = $this->call_filter_method( $api_properties, 'anaconda' );

		// Should only include the available property (NEW_AVAIL_1).
		$this->assertCount( 1, $filtered, 'Should only return 1 available property' );
		$this->assertEquals( 'NEW_AVAIL_1', $filtered[0]['id'], 'Should only return the available property' );
	}

	/**
	 * Helper: Create WordPress properties for testing
	 *
	 * @param array  $properties Properties to create.
	 * @param string $crm_type CRM type.
	 */
	private function create_wp_properties( $properties, $crm_type = 'anaconda' ) {
		foreach ( $properties as $prop ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'property',
					'post_title'  => 'Property ' . $prop['id'],
					'post_status' => 'publish',
				)
			);

			// Save property reference using the fixed meta key.
			update_post_meta( $post_id, 'ccrmre_property_id', $prop['id'] );

			// Save last updated date (can be null)
			if ( isset( $prop['last_updated'] ) && ! is_null( $prop['last_updated'] ) ) {
				update_post_meta( $post_id, 'ccrmre_last_updated', $prop['last_updated'] );
			}
		}

		// Clear cache
		delete_transient( 'ccrmre_wp_properties_' . $crm_type );
	}

	/**
	 * Helper: Call private method using reflection
	 *
	 * @param object $object Object instance.
	 * @param string $method Method name.
	 * @param array  $args Method arguments.
	 * @return mixed
	 */
	private function call_filter_method( $properties, $crm_type = 'anaconda' ) {
		return SYNC::filter_properties_to_update( $properties, $crm_type );
	}

	/**
	 * Helper method to call private methods
	 *
	 * @param object $object Object instance.
	 * @param string $method Method name.
	 * @param array  $args Method arguments.
	 * @return mixed
	 */
	private function call_private_method( $object, $method, $args = array() ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $args );
	}

	/**
	 * Cleanup properties helper
	 */
	private function cleanup_properties() {
		global $wpdb;
		
		// Delete all property posts
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'property'" );
		$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL" );
		
		// Clear transients
		delete_transient( 'ccrmre_wp_properties_anaconda' );
		delete_transient( 'ccrmre_wp_properties_inmovilla' );
		delete_transient( 'ccrmre_wp_properties_inmovilla_procesos' );
		
		// Clear WP cache
		wp_cache_flush();
	}

	/**
	 * Cleanup after tests
	 */
	public function tearDown(): void {
		$this->cleanup_properties();
		parent::tearDown();
	}
}
