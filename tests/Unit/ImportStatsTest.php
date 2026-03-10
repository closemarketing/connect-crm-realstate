<?php
/**
 * Tests for Import::compute_import_stats() — To Import/Update and related counts.
 *
 * @package Connect_CRM_RealState
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\Import;
use WP_UnitTestCase;

/**
 * Test case for compute_import_stats() (import_count = new_count + outdated_count).
 */
class ImportStatsTest extends WP_UnitTestCase {

	/**
	 * Setup test environment.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option(
			'ccrmre_settings',
			array(
				'type'      => 'anaconda',
				'post_type' => 'property',
			)
		);
	}

	/**
	 * Import count is new + outdated; no WP properties.
	 */
	public function test_import_count_all_new_when_no_wp_properties() {
		$available = array(
			'id1' => array( 'last_updated' => '2024-01-01 10:00:00' ),
			'id2' => array( 'last_updated' => '2024-01-02 10:00:00' ),
		);
		$wp        = array();
		$api_ids   = array( 'id1', 'id2' );

		$counts = Import::compute_import_stats( $available, $wp, $api_ids );

		$this->assertSame( 2, $counts['new_count'], 'Both are new' );
		$this->assertSame( 0, $counts['outdated_count'], 'None outdated' );
		$this->assertSame( 2, $counts['import_count'], 'To Import/Update = new + outdated' );
		$this->assertSame( 0, $counts['delete_count'], 'Nothing to delete' );
	}

	/**
	 * New count: in available but not in WP. Outdated: in both but API date newer.
	 */
	public function test_import_count_new_plus_outdated() {
		$available = array(
			'new1'   => array( 'last_updated' => '2024-01-10 10:00:00' ),
			'in_wp'  => array( 'last_updated' => '2024-01-15 12:00:00' ),
		);
		$wp = array(
			'in_wp' => array( 'last_updated' => '2024-01-01 10:00:00' ),
		);
		$api_ids = array( 'new1', 'in_wp' );

		$counts = Import::compute_import_stats( $available, $wp, $api_ids );

		$this->assertSame( 1, $counts['new_count'], 'One new (new1)' );
		$this->assertSame( 1, $counts['outdated_count'], 'One outdated (in_wp has newer date in API)' );
		$this->assertSame( 2, $counts['import_count'], 'To Import/Update = 1 + 1' );
		$this->assertSame( 0, $counts['delete_count'] );
	}

	/**
	 * Outdated only when API last_updated is strictly greater than WP.
	 */
	public function test_outdated_only_when_api_date_newer() {
		$available = array(
			'same' => array( 'last_updated' => '2024-01-01 10:00:00' ),
			'newer' => array( 'last_updated' => '2024-01-02 10:00:00' ),
		);
		$wp = array(
			'same'  => array( 'last_updated' => '2024-01-01 10:00:00' ),
			'newer' => array( 'last_updated' => '2024-01-01 09:00:00' ),
		);
		$api_ids = array( 'same', 'newer' );

		$counts = Import::compute_import_stats( $available, $wp, $api_ids );

		$this->assertSame( 0, $counts['new_count'] );
		$this->assertSame( 1, $counts['outdated_count'], 'Only newer is outdated' );
		$this->assertSame( 1, $counts['import_count'] );
	}

	/**
	 * When API date is same or older than WP, not outdated.
	 */
	public function test_not_outdated_when_api_date_same_or_older() {
		$available = array(
			'same'   => array( 'last_updated' => '2024-01-01 10:00:00' ),
			'older'  => array( 'last_updated' => '2024-01-01 08:00:00' ),
		);
		$wp = array(
			'same'  => array( 'last_updated' => '2024-01-01 10:00:00' ),
			'older' => array( 'last_updated' => '2024-01-01 09:00:00' ),
		);
		$api_ids = array( 'same', 'older' );

		$counts = Import::compute_import_stats( $available, $wp, $api_ids );

		$this->assertSame( 0, $counts['new_count'] );
		$this->assertSame( 0, $counts['outdated_count'] );
		$this->assertSame( 0, $counts['import_count'] );
	}

	/**
	 * Delete count: in WP but not in API.
	 */
	public function test_delete_count_wp_not_in_api() {
		$available = array( 'only_in_api' => array( 'last_updated' => '2024-01-01 10:00:00' ) );
		$wp        = array(
			'only_in_api' => array( 'last_updated' => '2024-01-01 10:00:00' ),
			'only_in_wp'  => array( 'last_updated' => '2024-01-01 10:00:00' ),
		);
		$api_ids   = array( 'only_in_api' );

		$counts = Import::compute_import_stats( $available, $wp, $api_ids );

		$this->assertSame( 0, $counts['new_count'] );
		$this->assertSame( 0, $counts['outdated_count'] );
		$this->assertSame( 0, $counts['import_count'] );
		$this->assertSame( 1, $counts['delete_count'], 'One WP property not in API' );
	}

	/**
	 * WP property with missing last_updated is not counted as outdated when API has date.
	 */
	public function test_wp_missing_last_updated_not_outdated() {
		$available = array( 'id1' => array( 'last_updated' => '2024-01-01 10:00:00' ) );
		$wp        = array( 'id1' => array( 'last_updated' => null ) );
		$api_ids   = array( 'id1' );

		$counts = Import::compute_import_stats( $available, $wp, $api_ids );

		$this->assertSame( 0, $counts['new_count'] );
		$this->assertSame( 0, $counts['outdated_count'], 'Empty wp_date: no date comparison, not outdated' );
		$this->assertSame( 0, $counts['import_count'] );
	}
}
