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
 * Library for Import Settings
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
		$this->settings        = get_option( 'conncrmreal_settings' );
		$this->settings_fields = get_option( 'conncrmreal_merge_fields' );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_manual_import' ) );
		add_action( 'wp_ajax_manual_import', array( $this, 'manual_import' ) );
		add_action( 'wp_ajax_get_import_stats', array( $this, 'get_import_stats' ) );
	}

	/**
	 * Manual import Requests
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function scripts_manual_import( $hook ) {
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
				'label_sync'          => __( 'Sync', 'connect-crm-real-state' ),
				'label_syncing'       => __( 'Syncing', 'connect-crm-real-state' ),
				'label_sync_complete' => __( 'Finished', 'connect-crm-real-state' ),
				'label_waiting'       => __( 'Waiting', 'connect-crm-real-state' ),
				/* translators: %s is the number of seconds to wait before retrying. */
				'label_rate_limit'    => __( 'API rate limit reached. Waiting %s seconds before retrying...', 'connect-crm-real-state' ),
				'label_resuming'      => __( 'Resuming import...', 'connect-crm-real-state' ),
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
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ccrmre_manual_import_nonce' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security verification failed. Please refresh the page and try again.', 'connect-crm-real-state' ),
				)
			);
			return;
		}

		// Disable server-side retry sleep so rate limit errors reach us immediately.
		API::set_skip_retry( true );

		$loop         = isset( $_POST['loop'] ) ? (int) $_POST['loop'] : 0;
		$mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'updated';
		$crm          = isset( $this->settings['type'] ) ? $this->settings['type'] : '';
		$pagination   = isset( $_POST['pagination'] ) ? (int) $_POST['pagination'] : API::get_pagination_size( $crm );
		$totalprop    = isset( $_POST['totalprop'] ) ? (int) $_POST['totalprop'] : 0;
		$progress_msg = '';

		$loop_page = $loop % $pagination;
		$page      = floor( $loop / $pagination ) + 1;

		// First step: remove properties not in API before syncing.
		if ( 0 === $loop ) {
			SYNC::clear_property_meta();

			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Checking properties to remove...', 'connect-crm-real-state' ) . '<br/>';
			$remove_result = SYNC::remove_properties_not_in_api( $crm );

			if ( 'error' === $remove_result['status'] ) {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:red;">' . __( 'Error checking API:', 'connect-crm-real-state' ) . '</strong> ' . esc_html( $remove_result['message'] ) . '<br/>';
			} elseif ( $remove_result['count'] > 0 ) {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'Properties removed (not in API):', 'connect-crm-real-state' ) . '</strong> ' . $remove_result['count'] . '<br/>';

				foreach ( $remove_result['details'] as $removed ) {
					$progress_msg .= '&nbsp;&nbsp;&nbsp;- ' . esc_html__( 'ID:', 'connect-crm-real-state' ) . ' ' . esc_html( $removed['property_id'] ) . ' - ' . esc_html( $removed['title'] ) . '<br/>';
				}
			} else {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'No properties to remove.', 'connect-crm-real-state' ) . '<br/>';
			}
		}

		// When starting a new page, fetch from API.
		if ( ( 0 === $loop_page && 0 < $pagination ) || ( 0 === $loop && -1 === $pagination ) ) {
			$result_api   = API::get_properties( $crm, $page );
			$properties   = 'ok' === $result_api['status'] ? $result_api['data'] : array();
			$progress_msg = '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-crm-real-state' ) . '<br/>';

			if ( 'updated' === $mode && ! empty( $properties ) ) {
				$api_properties = count( $properties );
				$properties     = SYNC::filter_properties_to_update( $properties, $crm );
				$progress_msg  .= '[' . date_i18n( 'H:i:s' ) . '] ' . sprintf(
					/* translators: %1$d: number of properties to update, %2$d: number of properties from API */
					__( 'Filtering properties to update... Found %1$d / %2$d properties.', 'connect-crm-real-state' ),
					count( $properties ),
					$api_properties
				) . '<br/>';
			}

			$totalprop = count( $properties );

			if ( 'error' === $result_api['status'] ) {
				$error_type = isset( $result_api['error_type'] ) ? $result_api['error_type'] : 'default';

				if ( 'rate_limit' === $error_type ) {
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'API rate limit reached. The API has requested a wait.', 'connect-crm-real-state' ) . '</strong><br/>';

					wp_send_json_success(
						array(
							'loop'         => $loop,
							'message'      => $progress_msg,
							'pagination'   => $pagination,
							'totalprop'    => $totalprop,
							'finish'       => false,
							'rate_limit'   => true,
							'wait_seconds' => 60,
						)
					);
				}

				$error_message  = $result_api['data'] ?? __( 'Error connecting with API. Please check your API connection.', 'connect-crm-real-state' );
				$error_message .= '. ' . __( 'If your credentials are correct, wait a few minutes and try again.', 'connect-crm-real-state' );

				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:red;">' . __( 'API ERROR:', 'connect-crm-real-state' ) . '</strong> ' . $error_message . '<br/>';

				wp_send_json_error(
					array(
						'message' => $progress_msg,
						'loop'    => $loop,
					)
				);
			}

			if ( 0 === $totalprop ) {
				if ( 0 === $loop ) {
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'No properties found to import.', 'connect-crm-real-state' ) . '</strong><br/>';
				} else {
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'No more properties from API. Import complete.', 'connect-crm-real-state' ) . '<br/>';
				}
				$property = null;
			} else {
				$i = 0;
				foreach ( $properties as $property_api ) {
					set_transient( 'connreal_query_property_loop_' . $i, $property_api, 30 * MINUTE_IN_SECONDS );
					++$i;
				}

				$property = isset( $properties[0] ) ? $properties[0] : null;
			}
		} else {
			$property = get_transient( 'connreal_query_property_loop_' . $loop );

			// Transient expired - re-fetch the page.
			if ( false === $property && $loop < $totalprop ) {
				$page       = floor( $loop / $pagination ) + 1;
				$result_api = API::get_properties( $crm, $page );

				if ( 'ok' === $result_api['status'] && ! empty( $result_api['data'] ) ) {
					$properties = $result_api['data'];
					$i          = 0;
					foreach ( $properties as $property_api ) {
						set_transient( 'connreal_query_property_loop_' . $i, $property_api, 30 * MINUTE_IN_SECONDS );
						++$i;
					}
					$loop_page = $loop % $pagination;
					$property  = isset( $properties[ $loop_page ] ) ? $properties[ $loop_page ] : null;
				}
			}
		}

		$finish = false;
		if ( ! empty( $property ) ) {
			$is_available = SYNC::is_property_available( $property, $crm );

			$id_display = '?';
			if ( 'inmovilla' === $crm || 'inmovilla_procesos' === $crm ) {
				$id_display = isset( $property['cod_ofer'] ) ? $property['cod_ofer'] : ( isset( $property['id'] ) ? $property['id'] : '?' );
			} elseif ( isset( $property['id'] ) ) {
				$id_display = $property['id'];
			}

			/* translators: %s: property ID (internal) from the CRM. */
			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . ( $loop + 1 ) . ' - ' . sprintf( __( 'Property ID: %s', 'connect-crm-real-state' ), esc_html( $id_display ) ) . ' — ';
			if ( ! $is_available ) {
				$result_sync   = SYNC::handle_unavailable_property( $property, $this->settings, $this->settings_fields, $crm );
				$progress_msg .= $result_sync['message'];
			} else {
				$result_get_property = API::get_property( $property, $crm );
				if ( 'ok' !== $result_get_property['status'] ) {
					$error_type = isset( $result_get_property['error_type'] ) ? $result_get_property['error_type'] : 'default';

					if ( 'rate_limit' === $error_type ) {
						$progress_msg .= '<strong style="color:orange;">' . __( 'API rate limit reached. The API has requested a wait.', 'connect-crm-real-state' ) . '</strong><br/>';

						wp_send_json_success(
							array(
								'loop'         => $loop,
								'message'      => $progress_msg,
								'pagination'   => $pagination,
								'totalprop'    => $totalprop,
								'finish'       => false,
								'rate_limit'   => true,
								'wait_seconds' => 60,
							)
						);
					}

					$progress_msg .= ' ' . __( 'Property ID:', 'connect-crm-real-state' ) . ' ';
					$progress_msg .= $property['id'];
					$progress_msg .= ' ' . __( 'Error:', 'connect-crm-real-state' ) . ' ';
					$progress_msg .= '<strong style="color:red;">' . __( 'API ERROR:', 'connect-crm-real-state' ) . '</strong> ' . $result_get_property['message'] . '<br/>';
					wp_send_json_error(
						array(
							'message' => $progress_msg,
							'loop'    => $loop,
						)
					);
				}
				$property_complete = $result_get_property['data'];
				$result_sync       = SYNC::sync_property( $property_complete, $this->settings, $this->settings_fields );
				$progress_msg     .= $result_sync['message'];

				if ( ! empty( $result_sync['post_id'] ) ) {
					$edit_link     = get_edit_post_link( $result_sync['post_id'] );
					$progress_msg .= ' - <a href="' . esc_url( $edit_link ) . '" target="_blank">' . __( 'View Post', 'connect-crm-real-state' ) . '</a>';
				}
			}

			// Determine if we should finish.
			if ( -1 === $pagination ) {
				$finish = ( ( $loop + 1 ) >= $totalprop );
			} else {
				$loop_page        = $loop % $pagination;
				$is_last_in_batch = ( ( $loop_page + 1 ) === $totalprop );
				$batch_not_full   = ( $totalprop < $pagination );
				$finish           = $is_last_in_batch && $batch_not_full;
			}
		} else {
			$finish = true;
		}

		if ( $finish ) {
			$trash_result = SYNC::trash_not_synced();

			if ( $trash_result['count'] > 0 ) {
				$progress_msg .= '<br/>[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . esc_html__( 'Properties that failed sync (sent to trash):', 'connect-crm-real-state' ) . '</strong> ' . $trash_result['count'] . '<br/>';

				foreach ( $trash_result['details'] as $trashed ) {
					$progress_msg .= '&nbsp;&nbsp;&nbsp;- ' . esc_html__( 'ID:', 'connect-crm-real-state' ) . ' ' . esc_html( $trashed['property_id'] ) . ' - ' . esc_html( $trashed['title'] ) . '<br/>';
				}
			}

			$progress_msg .= '<br/>[' . date_i18n( 'H:i:s' ) . '] <strong style="color:green;">' . esc_html__( 'Import completed successfully!', 'connect-crm-real-state' ) . '</strong><br/>';

			$size_clean = -1 === $pagination ? $totalprop : $pagination;
			for ( $i = 0; $i < $size_clean; $i++ ) {
				delete_transient( 'connreal_query_property_loop_' . $i );
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

	/**
	 * Get import statistics
	 *
	 * @return void
	 */
	public function get_import_stats() {
		check_ajax_referer( 'ccrmre_import_nonce', 'security' );

		$crm_type = isset( $this->settings['type'] ) ? $this->settings['type'] : '';

		if ( empty( $crm_type ) ) {
			wp_send_json_error( array( 'message' => __( 'CRM type not configured', 'connect-crm-real-state' ) ) );
		}

		$transient_key = 'ccrmre_api_properties_' . $crm_type;
		$api_result    = get_transient( $transient_key );

		if ( false === $api_result ) {
			$api_result = API::get_all_property_ids( $crm_type, true );

			if ( 'error' === $api_result['status'] ) {
				$error_message = isset( $api_result['message'] ) && ! empty( $api_result['message'] )
					? $api_result['message']
					: __( 'Error fetching property IDs from API', 'connect-crm-real-state' );
				wp_send_json_error( array( 'message' => $error_message ) );
			}

			set_transient( $transient_key, $api_result, 10 * MINUTE_IN_SECONDS );
		}

		$api_properties = isset( $api_result['data'] ) ? $api_result['data'] : array();
		$api_count      = count( $api_properties );
		$api_ids        = array_keys( $api_properties );

		$available_properties = array();
		foreach ( $api_properties as $prop_id => $prop_data ) {
			if ( SYNC::is_property_available( $prop_data, $crm_type ) ) {
				$available_properties[ $prop_id ] = $prop_data;
			}
		}
		$available_ids = array_keys( $available_properties );

		$wp_properties = SYNC::get_wordpress_property_data( $crm_type );
		$wp_count      = count( $wp_properties );
		$wp_ids        = array_keys( $wp_properties );

		$new_properties = array_diff( $available_ids, $wp_ids );
		$new_count      = count( $new_properties );

		$outdated_count = 0;
		foreach ( $wp_properties as $wp_id => $wp_data ) {
			if ( isset( $available_properties[ $wp_id ] ) ) {
				$api_data     = $available_properties[ $wp_id ];
				$needs_update = false;

				$api_date   = isset( $api_data['last_updated'] ) ? $api_data['last_updated'] : null;
				$wp_date    = isset( $wp_data['last_updated'] ) ? $wp_data['last_updated'] : null;
				$api_status = isset( $api_data['status'] ) ? $api_data['status'] : null;
				$wp_status  = isset( $wp_data['status'] ) ? $wp_data['status'] : null;

				if ( ! empty( $api_date ) && ! empty( $wp_date ) ) {
					$api_timestamp = strtotime( $api_date );
					$wp_timestamp  = strtotime( $wp_date );

					if ( $api_timestamp > $wp_timestamp ) {
						$needs_update = true;
					}
				}

				if ( $api_status !== $wp_status ) {
					$needs_update = true;
				}

				if ( $needs_update ) {
					++$outdated_count;
				}
			}
		}

		$import_count = $new_count + $outdated_count;
		$to_delete    = array_diff( $wp_ids, $api_ids );
		$delete_count = count( $to_delete );

		wp_send_json_success(
			array(
				'api_count'       => $api_count,
				'available_count' => count( $available_properties ),
				'wp_count'        => $wp_count,
				'import_count'    => $import_count,
				'new_count'       => $new_count,
				'outdated_count'  => $outdated_count,
				'delete_count'    => $delete_count,
			)
		);
	}
}
