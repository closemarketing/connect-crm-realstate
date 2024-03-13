<?php
/**
 * Cron automation for Connect CRM Real State
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

use Close\ConnectCRM\RealState\SYNC;

/**
 * Cron.
 *
 * @since 1.0.0
 */
class Cron {

	/**
	 * Construct of Class
	 */
	public function __construct() {
		$settings = get_option( 'conncrmreal_settings' );

		if ( isset( $settings['cron'] ) && 'yes' === $settings['cron'] ) {
			// Add action Schedule.
			add_action( 'init', array( $this, 'action_scheduler' ) );
			add_action( 'ccrmre_cron_sync_properties', array( $this, 'cron_sync_properties' ) );
		}
	}

	/**
	 * Cron advanced with Action Scheduler
	 *
	 * @return void
	 */
	public function action_scheduler() {
		if ( false === as_has_scheduled_action( 'ccrmre_cron_sync_properties' ) ) {
			as_schedule_recurring_action( time(), CCRMRE_SYNC_PERIOD, 'ccrmre_cron_sync_properties' );
		}
	}

	/**
	 * Cron sync properties
	 *
	 * @return void
	 */
	public function cron_sync_properties() {
		$date_since = strtotime( 'now - ' . CCRMRE_SYNC_PERIOD . ' seconds' );
		$date_since = gmdate( 'Y/m/d H:i:s', $date_since );
		$result_api = API::get_properties( 0, $date_since );

		if ( 'error' === $result_api['status'] || empty( $result_api['data'] ) ) {
			return;
		}
		foreach ( $result_api['data'] as $property ) {
			SYNC::sync_property( $property );
		}
	}
}
