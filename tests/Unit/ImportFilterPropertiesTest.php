<?php
/**
 * Tests for Import::filter_properties_to_update() method
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\Import;
use WP_UnitTestCase;

/**
 * Test case for filter_properties_to_update() method
 */
class ImportFilterPropertiesTest extends WP_UnitTestCase {

	/**
	 * Import instance
	 *
	 * @var Import
	 */
	private $import;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Initialize settings
		update_option(
			'conncrmreal_settings',
			array(
				'type'      => 'anaconda',
				'post_type' => 'property',
			)
		);
		
		update_option(
			'conncrmreal_merge_fields',
			array(
				'id'   => 'property_id',
				'name' => 'property_name',
			)
		);

		// Create Import instance
		$this->import = new Import();
	}

	/**
	 * Test filtering new properties (not in WordPress)
	 */
	public function test_filter_new_properties() {
		// Setup: Create WordPress properties
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
				array(
					'id'           => '2',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
			)
		);

		// API properties with new property #3
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0',
			),
			array(
				'id'         => '2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0',
			),
			array(
				'id'         => '3', // NEW property
				'name'       => 'Property 3',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0',
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 1, $filtered, 'Should return only 1 new property' );
		$this->assertEquals( '3', $filtered[0]['id'], 'Should return property #3' );
	}

	/**
	 * Test filtering properties with updated dates
	 */
	public function test_filter_properties_with_updated_dates() {
		// Setup: Create WordPress properties with old dates
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
				array(
					'id'           => '2',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
			)
		);

		// API properties with property #1 updated
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-15 12:00:00', // NEWER date
				'status'     => '0',
			),
			array(
				'id'         => '2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-01 10:00:00', // Same date
				'status'     => '0',
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 1, $filtered, 'Should return only 1 updated property' );
		$this->assertEquals( '1', $filtered[0]['id'], 'Should return property #1 with newer date' );
	}

	/**
	 * Test filtering properties with changed status
	 */
	public function test_filter_properties_with_changed_status() {
		// Setup: Create WordPress properties
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0', // Available
				),
				array(
					'id'           => '2',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0', // Available
				),
			)
		);

		// API properties with property #1 status changed
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00', // Same date
				'status'     => '1', // CHANGED to unavailable
			),
			array(
				'id'         => '2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0', // Same status
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 1, $filtered, 'Should return only 1 property with status change' );
		$this->assertEquals( '1', $filtered[0]['id'], 'Should return property #1 with changed status' );
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
					'status'       => '0',
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

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 1, $filtered, 'Should return property with both changes' );
		$this->assertEquals( '1', $filtered[0]['id'] );
	}

	/**
	 * Test filtering excludes properties without changes
	 */
	public function test_filter_excludes_unchanged_properties() {
		// Setup: Create WordPress properties
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
				array(
					'id'           => '2',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
			)
		);

		// API properties with NO changes
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00', // Same date
				'status'     => '0', // Same status
			),
			array(
				'id'         => '2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-01 10:00:00', // Same date
				'status'     => '0', // Same status
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 0, $filtered, 'Should return 0 properties when nothing changed' );
	}

	/**
	 * Test filtering with missing last_updated in WordPress
	 */
	public function test_filter_with_missing_wp_last_updated() {
		// Setup: Create WordPress property WITHOUT last_updated
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => null, // Missing date
					'status'       => '0',
				),
			)
		);

		// API property with date
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0',
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		// Should NOT update when WordPress date is missing
		$this->assertCount( 0, $filtered, 'Should not update when WP date is missing' );
	}

	/**
	 * Test filtering with missing last_updated in API
	 */
	public function test_filter_with_missing_api_last_updated() {
		// Setup: Create WordPress property
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
			)
		);

		// API property WITHOUT date
		$api_properties = array(
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => null, // Missing date
				'status'     => '0',
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		// Should NOT update when API date is missing
		$this->assertCount( 0, $filtered, 'Should not update when API date is missing' );
	}

	/**
	 * Test filtering with Inmovilla CRM type
	 */
	public function test_filter_with_inmovilla_crm() {
		// Update settings for Inmovilla
		update_option(
			'conncrmreal_settings',
			array(
				'type'      => 'inmovilla',
				'post_type' => 'property',
			)
		);

		update_option(
			'conncrmreal_merge_fields',
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
					'status'       => '0',
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

		// Recreate import instance with new settings
		$this->import = new Import();

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'inmovilla' ) );

		$this->assertCount( 1, $filtered, 'Should work with Inmovilla CRM' );
		$this->assertEquals( 'INM001', $filtered[0]['cod_ofer'] );
	}

	/**
	 * Test filtering with empty properties array
	 */
	public function test_filter_with_empty_properties() {
		$api_properties = array();

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

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
				'status'     => '0',
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 0, $filtered, 'Should skip properties without ID' );
	}

	/**
	 * Test filtering with mixed scenarios
	 */
	public function test_filter_with_mixed_scenarios() {
		// Setup: Create WordPress properties
		$this->create_wp_properties(
			array(
				array(
					'id'           => '1',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
				array(
					'id'           => '2',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
				array(
					'id'           => '3',
					'last_updated' => '2024-01-01 10:00:00',
					'status'       => '0',
				),
			)
		);

		// API properties with mixed scenarios
		$api_properties = array(
			// Property 1: No changes
			array(
				'id'         => '1',
				'name'       => 'Property 1',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0',
			),
			// Property 2: Date updated
			array(
				'id'         => '2',
				'name'       => 'Property 2',
				'updated_at' => '2024-01-15 12:00:00',
				'status'     => '0',
			),
			// Property 3: Status changed
			array(
				'id'         => '3',
				'name'       => 'Property 3',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '1',
			),
			// Property 4: New property
			array(
				'id'         => '4',
				'name'       => 'Property 4',
				'updated_at' => '2024-01-01 10:00:00',
				'status'     => '0',
			),
		);

		$filtered = $this->call_private_method( $this->import, 'filter_properties_to_update', array( $api_properties, 'anaconda' ) );

		$this->assertCount( 3, $filtered, 'Should return 3 properties (2 updated + 1 new)' );
		
		// Extract IDs
		$filtered_ids = array_map(
			function ( $prop ) {
				return $prop['id'];
			},
			$filtered
		);

		$this->assertContains( '2', $filtered_ids, 'Should include property 2 (date updated)' );
		$this->assertContains( '3', $filtered_ids, 'Should include property 3 (status changed)' );
		$this->assertContains( '4', $filtered_ids, 'Should include property 4 (new)' );
		$this->assertNotContains( '1', $filtered_ids, 'Should NOT include property 1 (no changes)' );
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

			// Save property reference
			if ( 'anaconda' === $crm_type ) {
				update_post_meta( $post_id, 'property_id', $prop['id'] );
			} elseif ( 'inmovilla' === $crm_type || 'inmovilla_procesos' === $crm_type ) {
				update_post_meta( $post_id, 'property_id', $prop['id'] );
			}

			// Save last updated date
			if ( isset( $prop['last_updated'] ) ) {
				update_post_meta( $post_id, 'ccrmre_last_updated', $prop['last_updated'] );
			}

			// Save status
			if ( isset( $prop['status'] ) ) {
				update_post_meta( $post_id, 'ccrmre_status', $prop['status'] );
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
	private function call_private_method( $object, $method, $args = array() ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $args );
	}

	/**
	 * Cleanup after tests
	 */
	public function tearDown(): void {
		// Delete all test properties
		$properties = get_posts(
			array(
				'post_type'      => 'property',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		foreach ( $properties as $property ) {
			wp_delete_post( $property->ID, true );
		}

		// Clear transients
		delete_transient( 'ccrmre_wp_properties_anaconda' );
		delete_transient( 'ccrmre_wp_properties_inmovilla' );
		delete_transient( 'ccrmre_wp_properties_inmovilla_procesos' );

		parent::tearDown();
	}
}
