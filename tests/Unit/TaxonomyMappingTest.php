<?php
/**
 * Tests for SYNC::assign_taxonomy_terms() and taxonomy mapping sanitization.
 *
 * @package ConnectCRM\RealState\Tests
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use Close\ConnectCRM\RealState\Admin;
use WP_UnitTestCase;

/**
 * Test case for taxonomy mapping feature.
 */
class TaxonomyMappingTest extends WP_UnitTestCase {

	/**
	 * Custom taxonomy name for testing.
	 *
	 * @var string
	 */
	private $test_taxonomy = 'test_property_type';

	/**
	 * Second custom taxonomy for testing.
	 *
	 * @var string
	 */
	private $test_taxonomy_2 = 'test_property_city';

	/**
	 * Setup test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		register_taxonomy( $this->test_taxonomy, 'post', array( 'public' => true ) );
		register_taxonomy( $this->test_taxonomy_2, 'post', array( 'public' => true ) );

		update_option(
			'ccrmre_settings',
			array(
				'type'      => 'anaconda',
				'post_type' => 'post',
			)
		);
	}

	/**
	 * Test single taxonomy term is assigned during sync.
	 */
	public function test_assign_single_taxonomy_term() {
		update_option(
			'ccrmre_taxonomy_mappings',
			array(
				array(
					'crm_field' => 'property_type',
					'taxonomy'  => $this->test_taxonomy,
				),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Test Property',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'            => 'P001',
			'name'          => 'Test Property',
			'property_type' => 'Apartment',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$terms = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$this->assertContains( 'Apartment', $terms );
	}

	/**
	 * Test multiple taxonomy mappings are assigned during sync.
	 */
	public function test_assign_multiple_taxonomy_mappings() {
		update_option(
			'ccrmre_taxonomy_mappings',
			array(
				array(
					'crm_field' => 'property_type',
					'taxonomy'  => $this->test_taxonomy,
				),
				array(
					'crm_field' => 'city',
					'taxonomy'  => $this->test_taxonomy_2,
				),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Multi Mapping',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'            => 'P002',
			'name'          => 'Multi Mapping',
			'property_type' => 'Villa',
			'city'          => 'Madrid',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$type_terms = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$city_terms = wp_get_object_terms( $post_id, $this->test_taxonomy_2, array( 'fields' => 'names' ) );

		$this->assertContains( 'Villa', $type_terms );
		$this->assertContains( 'Madrid', $city_terms );
	}

	/**
	 * Test comma-separated CRM values create multiple terms.
	 */
	public function test_assign_comma_separated_values() {
		update_option(
			'ccrmre_taxonomy_mappings',
			array(
				array(
					'crm_field' => 'features',
					'taxonomy'  => $this->test_taxonomy,
				),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Multi Term',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'       => 'P003',
			'name'     => 'Multi Term',
			'features' => 'Pool, Garden, Parking',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$terms = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$this->assertContains( 'Pool', $terms );
		$this->assertContains( 'Garden', $terms );
		$this->assertContains( 'Parking', $terms );
		$this->assertCount( 3, $terms );
	}

	/**
	 * Test empty CRM field value skips taxonomy assignment.
	 */
	public function test_empty_crm_value_skips_assignment() {
		update_option(
			'ccrmre_taxonomy_mappings',
			array(
				array(
					'crm_field' => 'property_type',
					'taxonomy'  => $this->test_taxonomy,
				),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Empty Value',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'            => 'P004',
			'name'          => 'Empty Value',
			'property_type' => '',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$terms = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$this->assertEmpty( $terms );
	}

	/**
	 * Test missing CRM field skips taxonomy assignment.
	 */
	public function test_missing_crm_field_skips_assignment() {
		update_option(
			'ccrmre_taxonomy_mappings',
			array(
				array(
					'crm_field' => 'nonexistent_field',
					'taxonomy'  => $this->test_taxonomy,
				),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Missing Field',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'   => 'P005',
			'name' => 'Missing Field',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$terms = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$this->assertEmpty( $terms );
	}

	/**
	 * Test no mappings configured does nothing.
	 */
	public function test_no_mappings_does_nothing() {
		delete_option( 'ccrmre_taxonomy_mappings' );

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'No Mappings',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'            => 'P006',
			'name'          => 'No Mappings',
			'property_type' => 'House',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$terms = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$this->assertEmpty( $terms );
	}

	/**
	 * Test existing term is reused instead of duplicated.
	 */
	public function test_existing_term_is_reused() {
		wp_insert_term( 'Apartment', $this->test_taxonomy );

		update_option(
			'ccrmre_taxonomy_mappings',
			array(
				array(
					'crm_field' => 'property_type',
					'taxonomy'  => $this->test_taxonomy,
				),
			)
		);

		$post_id = wp_insert_post(
			array(
				'post_title'  => 'Reuse Term',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		$item = array(
			'id'            => 'P007',
			'name'          => 'Reuse Term',
			'property_type' => 'Apartment',
		);

		SYNC::assign_taxonomy_terms( $post_id, $item, 'anaconda' );

		$terms      = wp_get_object_terms( $post_id, $this->test_taxonomy, array( 'fields' => 'names' ) );
		$all_terms  = get_terms(
			array(
				'taxonomy'   => $this->test_taxonomy,
				'hide_empty' => false,
				'name'       => 'Apartment',
			)
		);

		$this->assertContains( 'Apartment', $terms );
		$this->assertCount( 1, $all_terms, 'Should not duplicate the existing term' );
	}

	/**
	 * Test sanitize_taxonomy_mappings removes incomplete rows.
	 */
	public function test_sanitize_removes_incomplete_rows() {
		$admin = new Admin();

		$input = array(
			array(
				'crm_field' => 'property_type',
				'taxonomy'  => $this->test_taxonomy,
			),
			array(
				'crm_field' => 'city',
				'taxonomy'  => '',
			),
			array(
				'crm_field' => '',
				'taxonomy'  => $this->test_taxonomy_2,
			),
		);

		$result = $admin->sanitize_taxonomy_mappings( $input );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'property_type', $result[0]['crm_field'] );
		$this->assertEquals( $this->test_taxonomy, $result[0]['taxonomy'] );
	}

	/**
	 * Test sanitize_taxonomy_mappings rejects invalid taxonomies.
	 */
	public function test_sanitize_rejects_invalid_taxonomy() {
		$admin = new Admin();

		$input = array(
			array(
				'crm_field' => 'property_type',
				'taxonomy'  => 'nonexistent_taxonomy_xyz',
			),
		);

		$result = $admin->sanitize_taxonomy_mappings( $input );

		$this->assertEmpty( $result );
	}

	/**
	 * Test sanitize_taxonomy_mappings handles non-array input.
	 */
	public function test_sanitize_handles_non_array_input() {
		$admin  = new Admin();
		$result = $admin->sanitize_taxonomy_mappings( null );

		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	/**
	 * Cleanup after tests.
	 */
	public function tearDown(): void {
		delete_option( 'ccrmre_taxonomy_mappings' );
		delete_option( 'ccrmre_settings' );

		$terms = get_terms(
			array(
				'taxonomy'   => $this->test_taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_array( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $this->test_taxonomy );
			}
		}

		$terms_2 = get_terms(
			array(
				'taxonomy'   => $this->test_taxonomy_2,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		if ( is_array( $terms_2 ) ) {
			foreach ( $terms_2 as $term_id ) {
				wp_delete_term( $term_id, $this->test_taxonomy_2 );
			}
		}

		parent::tearDown();
	}
}
