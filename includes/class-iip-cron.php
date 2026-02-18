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

use Close\ConnectCRM\RealState\API;
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
		// Check license before initializing.
		if ( ! function_exists( 'ccrmre_is_license_active' ) || ! ccrmre_is_license_active() ) {
			return;
		}

		$settings = get_option( 'conncrmreal_settings' );

		if ( isset( $settings['cron'] ) && 'yes' === $settings['cron'] ) {
			// Add action Schedule after Action Scheduler is ready.
			add_action( 'action_scheduler_init', array( $this, 'action_scheduler' ) );
			add_action( 'ccrmre_cron_sync_properties', array( $this, 'cron_sync_properties' ) );
		}
	}

	/**
	 * Cron advanced with Action Scheduler
	 *
	 * @return void
	 */
	public function action_scheduler() {
		// Check if Action Scheduler is available.
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

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
		$time_start   = microtime( true );
		$settings     = get_option( 'conncrmreal_settings' );
		$merge_fields = get_option( 'conncrmreal_merge_fields' );
		$crm_type     = isset( $settings['type'] ) ? $settings['type'] : '';
		$result_log   = array();

		// Get properties to sync.
		$last_sync = get_option( 'ccrmre_cron_sync_last_time' );
		if ( empty( $last_sync ) ) {
			$date_since = strtotime( 'now - ' . CCRMRE_SYNC_PERIOD . ' seconds' );
			$last_sync  = gmdate( 'Y/m/d H:i:s', $date_since );
		}
		$result_api = API::get_properties( $crm_type, $last_sync );

		if ( 'error' === $result_api['status'] ) {
			$error_message = isset( $result_api['message'] ) ? $result_api['message'] : __( 'Unknown API error', 'connect-crm-realstate' );
			$result_log[]  = array( 'message' => 'API Error: ' . $error_message );
			$this->save_log( $time_start, $result_log, 0 );
			return;
		}

		$properties = isset( $result_api['data'] ) ? $result_api['data'] : array();

		// Filter to only properties that need update.
		if ( ! empty( $properties ) ) {
			$properties = SYNC::filter_properties_to_update( $properties, $crm_type );
		}

		if ( empty( $properties ) ) {
			$this->save_log( $time_start, $result_log, 0 );
			update_option( 'ccrmre_cron_sync_last_time', gmdate( 'Y/m/d H:i:s' ) );
			return array(
				'synced'  => 0,
				'removed' => 0,
			);
		}

		// Step 3: Sync properties.
		$synced_count = 0;
		foreach ( $properties as $property ) {
			// Check if property is available.
			if ( ! SYNC::is_property_available( $property, $crm_type ) ) {
				$result_sync  = SYNC::handle_unavailable_property( $property, $settings, $merge_fields, $crm_type );
				$result_log[] = $result_sync;
				continue;
			}

			$property_result = API::get_property( $property, $crm_type );

			if ( 'error' === $property_result['status'] ) {
				$error_message = isset( $property_result['message'] ) ? $property_result['message'] : __( 'Unknown property error', 'connect-crm-realstate' );
				$result_log[]  = array( 'message' => 'Error: ' . $error_message );
				continue;
			}

			$property_complete = isset( $property_result['data'] ) ? $property_result['data'] : array();

			if ( empty( $property_complete ) ) {
				continue;
			}

			$result_sync  = SYNC::sync_property( $property_complete, $settings, $merge_fields );
			$result_log[] = $result_sync;
			++$synced_count;
		}

		$this->save_log( $time_start, $result_log, $synced_count );

		update_option( 'ccrmre_cron_sync_last_time', gmdate( 'Y/m/d H:i:s' ) );
		return array(
			'synced'  => $synced_count,
			'removed' => 0,
		);
	}

	/**
	 * Save log
	 *
	 * @param float $time_start   Time start.
	 * @param array $result_log   Result.
	 * @param int   $synced_count Number of synced properties.
	 * @return void
	 */
	private function save_log( $time_start, $result_log, $synced_count = 0 ) {
		// Create folder for logs.
		$uploads_dir = wp_upload_dir();
		$log_dir     = $uploads_dir['basedir'] . '/ccrmre_logs/';
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}

		$log_file = $log_dir . 'cron-' . gmdate( 'Y-m-d-H-i-s' ) . '.log';
		$log_item = sprintf(
			/* translators: %1$s: date, %2$d: synced count, %3$s: time */
			'## %1$s - Synced: %2$d (%3$s)',
			gmdate( 'Y-m-d H:i:s' ),
			$synced_count,
			$this->get_time( $time_start )
		);
		$log_item .= PHP_EOL;

		foreach ( $result_log as $log_res ) {
			$message   = isset( $log_res['message'] ) ? $log_res['message'] : '';
			$log_item .= $message . PHP_EOL;
		}

		file_put_contents( $log_file, $log_item ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	/**
	 * Get time
	 *
	 * @param int $time_start time start.
	 * @return string
	 */
	private function get_time( $time_start ) {
		$time_end = microtime( true );

		$execution_time = round( $time_end - $time_start, 2 );
		$end            = 'seg';
		if ( $execution_time > 3600 ) {
			$execution_time = round( $execution_time / 3600, 2 );
			$end            = 'horas';
		} elseif ( $execution_time > 60 ) {
			$execution_time = round( $execution_time / 60, 2 );
			$end            = 'min';
		}
		return $execution_time . ' ' . $end;
	}
}
