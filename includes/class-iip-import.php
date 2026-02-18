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
		add_action( 'wp_ajax_get_import_stats', array( $this, 'get_import_stats' ) );
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
				'label_waiting'       => __( 'Waiting', 'connect-crm-realstate' ),
				// translators: %s is the number of seconds to wait before retrying.
			'label_rate_limit'    => __( 'API rate limit reached. Waiting %s seconds before retrying...', 'connect-crm-realstate' ),
				'label_resuming'      => __( 'Resuming import...', 'connect-crm-realstate' ),
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

		// Disable server-side retry sleep so rate limit errors reach us immediately.
		API::set_skip_retry( true );

		$loop         = isset( $_POST['loop'] ) ? (int) $_POST['loop'] : 0;
		$mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'updated';
		$crm          = isset( $this->settings['type'] ) ? $this->settings['type'] : '';
		$pagination   = isset( $_POST['pagination'] ) ? (int) $_POST['pagination'] : API::get_pagination_size( $crm );
		$totalprop    = isset( $_POST['totalprop'] ) ? (int) $_POST['totalprop'] : 0;
		$progress_msg = '';

		// Validate API credentials on first loop only.
		if ( 0 === $loop ) {
			$credentials_valid = self::validate_api_credentials_static( $crm );
			if ( ! $credentials_valid['valid'] ) {
				wp_send_json_error(
					array(
						'message' => $credentials_valid['message'],
					)
				);
				return;
			}
		}

		$loop_page = $loop % $pagination;
		$page      = floor( $loop / $pagination ) + 1;

		// First step: remove properties not in API before syncing.
		if ( 0 === $loop ) {
			SYNC::clear_property_meta();

			// Remove properties that are no longer in API.
			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Checking properties to remove...', 'connect-crm-realstate' ) . '<br/>';
			$remove_result = SYNC::remove_properties_not_in_api( $crm );

			if ( 'error' === $remove_result['status'] ) {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:red;">' . __( 'Error checking API:', 'connect-crm-realstate' ) . '</strong> ' . esc_html( $remove_result['message'] ) . '<br/>';
			} elseif ( $remove_result['count'] > 0 ) {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'Properties removed (not in API):', 'connect-crm-realstate' ) . '</strong> ' . $remove_result['count'] . '<br/>';

				foreach ( $remove_result['details'] as $removed ) {
					$progress_msg .= '&nbsp;&nbsp;&nbsp;- ' . esc_html__( 'ID:', 'connect-crm-realstate' ) . ' ' . esc_html( $removed['property_id'] ) . ' - ' . esc_html( $removed['title'] ) . '<br/>';
				}
			} else {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'No properties to remove.', 'connect-crm-realstate' ) . '<br/>';
			}
		}

		// When starting a new page (loop_page = 0), always fetch from API.
		if ( ( 0 === $loop_page && 0 < $pagination ) || ( 0 === $loop && -1 === $pagination ) ) {
			$result_api   = API::get_properties( $crm, $page );
			$properties   = 'ok' === $result_api['status'] ? $result_api['data'] : array();
			$progress_msg = '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-crm-realstate' ) . '<br/>';

			// Filter properties if mode is 'updated'.
			if ( 'updated' === $mode && ! empty( $properties ) ) {
				$properties    = SYNC::filter_properties_to_update( $properties, $crm );
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . sprintf(
					/* translators: %d: number of properties to update */
					__( 'Filtering properties to update... Found %d properties.', 'connect-crm-realstate' ),
					count( $properties )
				) . '<br/>';
			}

			$totalprop = count( $properties );

			if ( 'error' === $result_api['status'] ) {
				$error_type = isset( $result_api['error_type'] ) ? $result_api['error_type'] : 'default';

				// Rate limit: inform user and tell JS to wait 60 seconds.
				if ( 'rate_limit' === $error_type ) {
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'API rate limit reached. The API has requested a wait.', 'connect-crm-realstate' ) . '</strong><br/>';

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

				// Other errors: original behavior.
				$error_message  = $result_api['data'] ?? __( 'Error connecting with API. Please check your API connection.', 'connect-crm-realstate' );
				$error_message .= '. ' . __( 'If your credentials are correct, wait a few minutes and try again.', 'connect-crm-realstate' );

				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:red;">' . __( 'API ERROR:', 'connect-crm-realstate' ) . '</strong> ' . $error_message . '<br/>';

				wp_send_json_error(
					array(
						'message' => $progress_msg,
						'loop'    => $loop,
					)
				);
			}

			// Check if we got properties from API.
			if ( 0 === $totalprop ) {
				if ( 0 === $loop ) {
					// First loop and no properties found.
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'No properties found to import.', 'connect-crm-realstate' ) . '</strong><br/>';
				} else {
					// No more properties from API - we're done.
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'No more properties from API. Import complete.', 'connect-crm-realstate' ) . '<br/>';
				}
				$property = null;
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
			// Check if property is available in listing (optimization).
			$is_available = SYNC::is_property_available( $property, $crm );

			$key_id      = ( 'inmovilla' === $crm || 'inmovilla_procesos' === $crm ) ? 'cod_ofer' : 'id';
			$prop_id_val = isset( $property[ $key_id ] ) ? $property[ $key_id ] : '?';

			/* translators: %s: property ID from the CRM. */
			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . ( $loop + 1 ) . ' - ' . sprintf( __( 'Property ID: %s', 'connect-crm-realstate' ), esc_html( $prop_id_val ) ) . ' — ';
			if ( ! $is_available ) {
				// Property is not available, handle according to settings.
				$result_sync   = SYNC::handle_unavailable_property( $property, $this->settings, $this->settings_fields, $crm );
				$progress_msg .= $result_sync['message'];
			} else {
				// Property is available, sync full details.
				$result_get_property = API::get_property( $property, $crm );
				if ( 'ok' !== $result_get_property['status'] ) {
					$error_type = isset( $result_get_property['error_type'] ) ? $result_get_property['error_type'] : 'default';

					// Rate limit: inform user and tell JS to wait 60 seconds.
					if ( 'rate_limit' === $error_type ) {
						$progress_msg .= '<strong style="color:orange;">' . __( 'API rate limit reached. The API has requested a wait.', 'connect-crm-realstate' ) . '</strong><br/>';

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

					// Other errors: original behavior.
					$progress_msg .= ' ' . __( 'Property ID:', 'connect-crm-realstate' ) . ' ';
					$progress_msg .= $property['id'];
					$progress_msg .= ' ' . __( 'Error:', 'connect-crm-realstate' ) . ' ';

					$progress_msg .= '<strong style="color:red;">' . __( 'API ERROR:', 'connect-crm-realstate' ) . '</strong> ' . $result_get_property['message'] . '<br/>';
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

				// Add link to view/edit the post.
				if ( ! empty( $result_sync['post_id'] ) ) {
					$edit_link     = get_edit_post_link( $result_sync['post_id'] );
					$progress_msg .= ' - <a href="' . esc_url( $edit_link ) . '" target="_blank">' . __( 'View Post', 'connect-crm-realstate' ) . '</a>';
				}
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
			// Check for any properties that failed to sync during the process.
			$trash_result = SYNC::trash_not_synced();

			if ( $trash_result['count'] > 0 ) {
				$progress_msg .= '<br/>[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . esc_html__( 'Properties that failed sync (sent to trash):', 'connect-crm-realstate' ) . '</strong> ' . $trash_result['count'] . '<br/>';

				foreach ( $trash_result['details'] as $trashed ) {
					$progress_msg .= '&nbsp;&nbsp;&nbsp;- ' . esc_html__( 'ID:', 'connect-crm-realstate' ) . ' ' . esc_html( $trashed['property_id'] ) . ' - ' . esc_html( $trashed['title'] ) . '<br/>';
				}
			}

			$progress_msg .= '<br/>[' . date_i18n( 'H:i:s' ) . '] <strong style="color:green;">' . esc_html__( 'Import completed successfully!', 'connect-crm-realstate' ) . '</strong><br/>';

			// Clear transients.
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
	 * Returns property counts from API, Web, to import (new + outdated), and to delete
	 *
	 * @return void
	 */
	public function get_import_stats() {
		check_ajax_referer( 'ccrmre_import_nonce', 'security' );

		$crm_type = isset( $this->settings['type'] ) ? $this->settings['type'] : '';

		if ( empty( $crm_type ) ) {
			wp_send_json_error( array( 'message' => __( 'CRM type not configured', 'connect-crm-realstate' ) ) );
		}

		// Validate API credentials before making request.
		$credentials_valid = self::validate_api_credentials_static( $crm_type );
		if ( ! $credentials_valid['valid'] ) {
			wp_send_json_error( array( 'message' => $credentials_valid['message'] ) );
		}

		// Get properties from API with dates (with 10-minute cache).
		$transient_key = 'ccrmre_api_properties_' . $crm_type;
		$api_result    = get_transient( $transient_key );

		if ( false === $api_result ) {
			// No cache, fetch from API.
			$api_result = API::get_all_property_ids( $crm_type, true );

			if ( 'error' === $api_result['status'] ) {
				$error_message = isset( $api_result['message'] ) && ! empty( $api_result['message'] )
					? $api_result['message']
					: __( 'Error fetching property IDs from API', 'connect-crm-realstate' );
				wp_send_json_error( array( 'message' => $error_message ) );
			}

			// Cache for 10 minutes.
			set_transient( $transient_key, $api_result, 10 * MINUTE_IN_SECONDS );
		}

		$api_properties = isset( $api_result['data'] ) ? $api_result['data'] : array();
		$api_count      = count( $api_properties );
		$api_ids        = array_keys( $api_properties );

		// Filter out unavailable properties (those that won't be imported).
		$available_properties = array();
		foreach ( $api_properties as $prop_id => $prop_data ) {
			if ( SYNC::is_property_available( $prop_data, $crm_type ) ) {
				$available_properties[ $prop_id ] = $prop_data;
			}
		}
		$available_ids = array_keys( $available_properties );

		// Get properties from WordPress with dates (with 10-minute cache).
		$wp_properties = SYNC::get_wordpress_property_data( $crm_type );
		$wp_count      = count( $wp_properties );
		$wp_ids        = array_keys( $wp_properties );

		// Calculate NEW properties (in API, available, but not in WP).
		$new_properties = array_diff( $available_ids, $wp_ids );
		$new_count      = count( $new_properties );

		// Calculate OUTDATED properties (in both, available in API, but with changes in date OR status).
		$outdated_count = 0;
		foreach ( $wp_properties as $wp_id => $wp_data ) {
			// Only check properties that are available in API.
			if ( isset( $available_properties[ $wp_id ] ) ) {
				$api_data     = $available_properties[ $wp_id ];
				$needs_update = false;

				// Get dates and status.
				$api_date   = isset( $api_data['last_updated'] ) ? $api_data['last_updated'] : null;
				$wp_date    = isset( $wp_data['last_updated'] ) ? $wp_data['last_updated'] : null;
				$api_status = isset( $api_data['status'] ) ? $api_data['status'] : null;
				$wp_status  = isset( $wp_data['status'] ) ? $wp_data['status'] : null;

				// Check if date is newer in API.
				if ( ! empty( $api_date ) && ! empty( $wp_date ) ) {
					$api_timestamp = strtotime( $api_date );
					$wp_timestamp  = strtotime( $wp_date );

					if ( $api_timestamp > $wp_timestamp ) {
						$needs_update = true;
					}
				}

				// Check if status has changed.
				if ( $api_status !== $wp_status ) {
					$needs_update = true;
				}

				if ( $needs_update ) {
					++$outdated_count;
				}
			}
		}

		// Total to import = new + outdated.
		$import_count = $new_count + $outdated_count;

		// Calculate to delete (in WP but not in API).
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

	/**
	 * Validate API credentials are configured and working (static version)
	 *
	 * Uses a transient cache to avoid checking on every request.
	 * Cache duration: 1 day for valid, 5 minutes for invalid.
	 *
	 * @param string $crm_type CRM type (anaconda, inmovilla, inmovilla_procesos).
	 * @param bool   $force_check Force validation even if cache exists.
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	public static function validate_api_credentials_static( $crm_type, $force_check = false ) {
		// Check transient cache first (unless forced).
		if ( ! $force_check ) {
			$transient_key = 'ccrmre_api_valid_' . $crm_type;
			$cached_result = get_transient( $transient_key );

			if ( false !== $cached_result ) {
				return $cached_result;
			}
		}

		$settings = get_option( 'conncrmreal_settings', array() );
		$result   = array(
			'valid'   => false,
			'message' => '',
		);

		// Validate credentials are configured.
		if ( 'anaconda' === $crm_type ) {
			$apipassword = isset( $settings['apipassword'] ) ? trim( $settings['apipassword'] ) : '';

			if ( empty( $apipassword ) ) {
				$result['message'] = __( 'API password is not configured. Please configure your Anaconda API credentials in the Connection tab.', 'connect-crm-realstate' );
				self::cache_api_validation_static( $crm_type, $result );
				return $result;
			}
		} elseif ( 'inmovilla' === $crm_type || 'inmovilla_procesos' === $crm_type ) {
			$apiuser     = isset( $settings['apiuser'] ) ? trim( $settings['apiuser'] ) : '';
			$apipassword = isset( $settings['apipassword'] ) ? trim( $settings['apipassword'] ) : '';

			if ( empty( $apiuser ) || empty( $apipassword ) ) {
				$result['message'] = __( 'API credentials are not configured. Please configure your Inmovilla API user and password in the Connection tab.', 'connect-crm-realstate' );
				self::cache_api_validation_static( $crm_type, $result );
				return $result;
			}
		}

		// Test credentials with a simple API call.
		$test_result = self::test_api_connection_static( $crm_type );

		if ( $test_result['valid'] ) {
			$result['valid']   = true;
			$result['message'] = '';
		} else {
			$result['message'] = $test_result['message'];
		}

		// Cache result.
		self::cache_api_validation_static( $crm_type, $result );

		return $result;
	}

	/**
	 * Test API connection with a lightweight request (static version)
	 *
	 * @param string $crm_type CRM type (anaconda, inmovilla, inmovilla_procesos).
	 * @return array Array with 'valid' boolean and 'message' string.
	 */
	private static function test_api_connection_static( $crm_type ) {
		// Make a lightweight API call to test credentials.
		if ( 'anaconda' === $crm_type ) {
			$result = API::request_anaconda( 'properties?page=1&per_page=1' );
		} elseif ( 'inmovilla' === $crm_type ) {
			$result = API::request_inmovilla( 'lista', 1, 1 );
		} elseif ( 'inmovilla_procesos' === $crm_type ) {
			$result = API::request_inmovilla_procesos( 'Procesos', array( 'pag' => 1 ) );
		} else {
			return array(
				'valid'   => false,
				'message' => __( 'Unknown CRM type', 'connect-crm-realstate' ),
			);
		}

		if ( 'ok' === $result['status'] ) {
			return array(
				'valid'   => true,
				'message' => '',
			);
		}

		// Extract error message.
		$error_message = isset( $result['message'] ) ? $result['message'] : __( 'API connection test failed', 'connect-crm-realstate' );

		return array(
			'valid'   => false,
			'message' => $error_message,
		);
	}

	/**
	 * Cache API validation result (static version)
	 *
	 * @param string $crm_type CRM type.
	 * @param array  $result Validation result.
	 * @return void
	 */
	private static function cache_api_validation_static( $crm_type, $result ) {
		$transient_key = 'ccrmre_api_valid_' . $crm_type;
		$cache_time    = $result['valid'] ? DAY_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
		set_transient( $transient_key, $result, $cache_time );
	}
}
