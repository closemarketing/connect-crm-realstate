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
		$time_start = microtime( true );

		// Gets data from last sync.
		$last_sync = get_option( 'ccrmre_cron_sync_last_time' );
		if ( empty( $last_sync ) ) {
			$date_since = strtotime( 'now - ' . CCRMRE_SYNC_PERIOD . ' seconds' );
			$last_sync  = gmdate( 'Y/m/d H:i:s', $date_since );
		}
		$result_api = API::get_properties( 0, $last_sync );

		if ( 'error' === $result_api['status'] ) {
			// Log error if needed.
			$error_message = isset( $result_api['message'] ) ? $result_api['message'] : __( 'Unknown API error', 'connect-crm-realstate' );
			error_log( 'Connect CRM Real State - Cron sync error: ' . $error_message );
			return;
		}

		$properties = isset( $result_api['data'] ) ? $result_api['data'] : array();

		if ( empty( $properties ) ) {
			return;
		}

		$settings     = get_option( 'conncrmreal_settings' );
		$merge_fields = get_option( 'conncrmreal_merge_fields' );
		$result_log   = array();

		foreach ( $properties as $property ) {
			$property_result = API::get_property( $property, $settings['type'] );

			if ( 'error' === $property_result['status'] ) {
				$error_message = isset( $property_result['message'] ) ? $property_result['message'] : __( 'Unknown property error', 'connect-crm-realstate' );
				error_log( 'Connect CRM Real State - Error getting property: ' . $error_message );
				continue;
			}

			$property_complete = isset( $property_result['data'] ) ? $property_result['data'] : array();

			if ( empty( $property_complete ) ) {
				continue;
			}

			$result_sync  = SYNC::sync_property( $property_complete, $settings, $merge_fields );
			$result_log[] = $result_sync['message'];
		}

		$this->save_log( $time_start, $result_log );

		update_option( 'ccrmre_cron_sync_last_time', gmdate( 'Y/m/d H:i:s' ) );
		return array(
			'synced' => count( $properties ),
		);
	}

	/**
	 * Save log
	 *
	 * @param float $time_start Time start.
	 * @param array $result_log Result.
	 * @return void
	 */
	private function save_log( $time_start, $result_log ) {
		// Create folder for logs.
		$uploads_dir = wp_upload_dir();
		$log_dir     = $uploads_dir['basedir'] . '/ccrmre_logs/';
		if ( ! file_exists( $log_dir ) ) {
			mkdir( $log_dir, 0777, true );
		}
		$nonce     = wp_create_nonce( 'ccrmre_cron_sync_properties' );
		$log_file  = $log_dir . 'cron-' . $nonce . '-' . gmdate( 'Y-m-d-H-i-s' ) . '.log';
		$log_count = ! empty( $result_log ) ? count( $result_log ) : 0;
		$log_item  = sprintf(
			/* translators: %1$s: number of properties, %2$s: time */
			esc_html__( '## %1$s - Synced %2$s properties in %3$s', 'connect-crm-realstate' ),
			gmdate( 'Y-m-d H:i:s' ),
			esc_html( $log_count ),
			esc_html( $this->get_time( $time_start ) )
		);
		$log_item = $log_item . PHP_EOL;
		foreach ( $result_log as $log_res ) {
			$log_item .= $log_res['message'] . PHP_EOL;
		}

		file_put_contents( $log_file, $log_item );
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
