<?php
/**
 * Library for importing products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

use Close\ConnectCRM\RealState\API;

/**
 * Library for WooCommerce Settings
 *
 * Settings in order to importing products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class Import {
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Settings Fields
	 *
	 * @var array
	 */
	private $settings_fields;

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		// Check license before initializing.
		if ( ! function_exists( 'ccrmre_is_license_active' ) || ! ccrmre_is_license_active() ) {
			return;
		}

		$this->settings        = get_option( 'conncrmreal_settings' );
		$this->settings_fields = get_option( 'conncrmreal_merge_fields' );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_manual_import' ) );
		add_action( 'wp_ajax_manual_import', array( $this, 'manual_import' ) );
		add_action( 'wp_ajax_nopriv_manual_import', array( $this, 'manual_import' ) );
	}

	/**
	 * Manual import Requests
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function scripts_manual_import( $hook ) {
		// Only load on plugin settings page.
		if ( 'toplevel_page_iip-options' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'ccrmre-manual-sync',
			CCRMRE_PLUGIN_URL . 'includes/assets/iip-manual-sync.js',
			array(),
			CCRMRE_VERSION,
			true
		);

		wp_localize_script(
			'ccrmre-manual-sync',
			'ajaxAction',
			array(
				'url'                 => admin_url( 'admin-ajax.php' ),
				'label_sync'          => __( 'Sync', 'connect-crm-realstate' ),
				'label_syncing'       => __( 'Syncing', 'connect-crm-realstate' ),
				'label_sync_complete' => __( 'Finished', 'connect-crm-realstate' ),
				'nonce'               => wp_create_nonce( 'ccrmre_manual_import_nonce' ),
			)
		);
	}

	/**
	 * Ajax function to load info
	 *
	 * @return void
	 */
	public function manual_import() {
		// Verify nonce manually for better error handling.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ccrmre_manual_import_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'connect-crm-realstate' ),
				)
			);
			return;
		}

		$loop         = isset( $_POST['loop'] ) ? (int) $_POST['loop'] : 0;
		$crm          = isset( $this->settings['type'] ) ? $this->settings['type'] : '';
		$pagination   = isset( $_POST['pagination'] ) ? (int) $_POST['pagination'] : API::get_pagination_size( $crm );
		$totalprop    = isset( $_POST['totalprop'] ) ? (int) $_POST['totalprop'] : 0;
		$progress_msg = '';

		$loop_page = $loop % $pagination;
		$page      = floor( $loop / $pagination ) + 1;

		if ( 0 === $loop ) {
			SYNC::clear_property_meta();
		}

		// When starting a new page (loop_page = 0), always fetch from API.
		if ( ( 0 === $loop_page && 0 < $pagination ) || ( 0 === $loop && -1 === $pagination ) ) {
			// Clear old transients from previous page.
			for ( $i = 0; $i < $pagination; $i++ ) {
				delete_transient( 'connreal_query_property_loop_' . $i );
			}

			$result_api   = API::get_properties( $page );
			$properties   = 'ok' === $result_api['status'] ? $result_api['data'] : array();
			$progress_msg = '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-crm-realstate' ) . '<br/>';
			$totalprop    = count( $properties );

			// Check if we got properties from API.
			if ( 0 === $totalprop && $loop > 0 ) {
				// No more properties from API - we're done.
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'No more properties from API. Import complete.', 'connect-crm-realstate' ) . '<br/>';
				$property      = null;
			} else {
				// Save properties in transients.
				$i = 0;
				foreach ( $properties as $property_api ) {
					set_transient( 'connreal_query_property_loop_' . $i, $property_api, MINUTE_IN_SECONDS * 3 );
					++$i;
				}

				$property = isset( $properties[0] ) ? $properties[0] : null;
			}
		} else {
			// Get property from transient.
			$property = get_transient( 'connreal_query_property_loop_' . $loop );
		}

		$finish = false;

		if ( ! empty( $property ) ) {
			$property_complete = API::get_property( $property, $crm );
			$result_sync       = SYNC::sync_property( $property_complete, $this->settings, $this->settings_fields );
			$progress_msg     .= '[' . date_i18n( 'H:i:s' ) . '] ' . ( $loop + 1 );
			$progress_msg     .= ' - ' . $result_sync['message'];

			// Add link to view/edit the post.
			if ( ! empty( $result_sync['post_id'] ) ) {
				$edit_link     = get_edit_post_link( $result_sync['post_id'] );
				$progress_msg .= ' - <a href="' . esc_url( $edit_link ) . '" target="_blank">' . __( 'View Post', 'connect-crm-realstate' ) . '</a>';
			}

			// Determine if we should finish.
			if ( -1 === $pagination ) {
				// No pagination: finish when we've processed all properties.
				$finish = ( ( $loop + 1 ) >= $totalprop );
			} else {
				// With pagination: finish when we're at the last property of a partial batch.
				$loop_page        = $loop % $pagination;
				$is_last_in_batch = ( ( $loop_page + 1 ) === $totalprop );
				$batch_not_full   = ( $totalprop < $pagination );
				$finish           = $is_last_in_batch && $batch_not_full;
			}
		} else {
			$finish = true;
		}

		if ( $finish ) {
			$count         = SYNC::trash_not_synced();
			$progress_msg .= esc_html__( 'Properties not synced and sent to trash: ', 'connect-crm-realstate' ) . $count;

			// Clear all transients.
			if ( -1 === $pagination ) {
				// Clear all transients for no-pagination mode.
				for ( $i = 0; $i < $totalprop; $i++ ) {
					delete_transient( 'connreal_query_property_loop_' . $i );
				}
			} else {
				// Clear transients for pagination mode.
				for ( $i = 0; $i < $pagination; $i++ ) {
					delete_transient( 'connreal_query_property_loop_' . $i );
				}
			}
		}

		wp_send_json_success(
			array(
				'loop'       => $loop + 1,
				'message'    => $progress_msg,
				'pagination' => $pagination,
				'totalprop'  => $totalprop,
				'finish'     => $finish,
			)
		);
	}
}
