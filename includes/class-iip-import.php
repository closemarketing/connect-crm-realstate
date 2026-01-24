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
		$mode         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'updated';
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
			$result_api   = API::get_properties( $page );
			$properties   = 'ok' === $result_api['status'] ? $result_api['data'] : array();
			$progress_msg = '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-crm-realstate' ) . '<br/>';
			$totalprop    = count( $properties );

			if ( 'error' === $result_api['status'] ) {
				$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ';
				$progress_msg .= $result_api['data'] ?? __( 'Error connecting with API. Please check your API connection.', 'connect-crm-realstate' ) . '<br/>';
				$finish        = true;

				wp_send_json_success(
					array(
						'loop'       => 0,
						'message'    => $progress_msg,
						'pagination' => 0,
						'totalprop'  => 0,
						'finish'     => true,
					)
				);
			}

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
			// Check if property is available in listing (optimization).
			$is_available = SYNC::is_property_available( $property, $crm );

			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . ( $loop + 1 ) . ' - ';
			if ( ! $is_available ) {
				// Property is not available, handle according to settings.
				$result_sync   = SYNC::handle_unavailable_property( $property, $this->settings, $this->settings_fields, $crm );
				$progress_msg .= $result_sync['message'];
			} else {
				// Property is available, sync full details.
				$property_complete = API::get_property( $property, $crm );
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
			$count         = SYNC::trash_not_synced();
			$progress_msg .= esc_html__( 'Properties not synced and sent to trash: ', 'connect-crm-realstate' ) . $count;

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

		// Get properties from API with dates (with 10-minute cache).
		$transient_key = 'ccrmre_api_properties_' . $crm_type;
		$api_result    = get_transient( $transient_key );

		if ( false === $api_result ) {
			// No cache, fetch from API.
			$api_result = API::get_all_property_ids( $crm_type, true );

			if ( 'error' === $api_result['status'] ) {
				wp_send_json_error( array( 'message' => $api_result['data'] ) );
			}

			// Cache for 10 minutes.
			set_transient( $transient_key, $api_result, 10 * MINUTE_IN_SECONDS );
		}

		$api_properties = isset( $api_result['data'] ) ? $api_result['data'] : array();
		$api_count      = count( $api_properties );
		$api_ids        = array_keys( $api_properties );

		// Get properties from WordPress with dates (with 10-minute cache).
		$wp_transient_key = 'ccrmre_wp_properties_' . $crm_type;
		$wp_properties    = get_transient( $wp_transient_key );

		if ( false === $wp_properties ) {
			// No cache, fetch from database.
			$wp_properties = $this->get_wordpress_property_data( $crm_type );

			// Cache for 10 minutes.
			set_transient( $wp_transient_key, $wp_properties, 10 * MINUTE_IN_SECONDS );
		}

		$wp_count = count( $wp_properties );
		$wp_ids   = array_keys( $wp_properties );

		// Calculate NEW properties (in API but not in WP).
		$new_properties = array_diff( $api_ids, $wp_ids );
		$new_count      = count( $new_properties );

		// Calculate OUTDATED properties (in both, but with changes in date OR status).
		$outdated_count = 0;
		foreach ( $wp_properties as $wp_id => $wp_data ) {
			if ( isset( $api_properties[ $wp_id ] ) ) {
				$api_data     = $api_properties[ $wp_id ];
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
				'api_count'      => $api_count,
				'wp_count'       => $wp_count,
				'import_count'   => $import_count,
				'new_count'      => $new_count,
				'outdated_count' => $outdated_count,
				'delete_count'   => $delete_count,
			)
		);
	}

	/**
	 * Get WordPress property references
	 *
	 * @param string $crm_type CRM type.
	 * @return array Array of property references
	 */
	private function get_wordpress_property_refs( $crm_type ) {
		global $wpdb;

		$meta_key = $this->get_reference_meta_key( $crm_type );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				WHERE p.post_type = 'property'
				AND p.post_status != 'trash'
				AND pm.meta_key = %s",
				$meta_key
			)
		);
		// phpcs:enable

		return array_filter( $results );
	}

	/**
	 * Get WordPress property data with dates and status
	 *
	 * @param string $crm_type CRM type.
	 * @return array Associative array of property_id => array(last_updated, status)
	 */
	private function get_wordpress_property_data( $crm_type ) {
		global $wpdb;

		$meta_key = $this->get_reference_meta_key( $crm_type );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					pm1.meta_value as property_ref, 
					pm2.meta_value as last_updated,
					pm3.meta_value as status
				FROM {$wpdb->postmeta} pm1
				INNER JOIN {$wpdb->posts} p ON pm1.post_id = p.ID
				LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'ccrmre_last_updated'
				LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = 'ccrmre_status'
				WHERE p.post_type = 'property'
				AND p.post_status != 'trash'
				AND pm1.meta_key = %s",
				$meta_key
			),
			ARRAY_A
		);
		// phpcs:enable

		$property_data = array();
		foreach ( $results as $row ) {
			if ( ! empty( $row['property_ref'] ) ) {
				$property_data[ $row['property_ref'] ] = array(
					'last_updated' => $row['last_updated'],
					'status'       => $row['status'],
				);
			}
		}

		return $property_data;
	}

	/**
	 * Get reference meta key based on CRM type
	 *
	 * @param string $crm_type CRM type.
	 * @return string Meta key for property reference
	 */
	private function get_reference_meta_key( $crm_type ) {
		$merge_fields = get_option( 'conncrmreal_merge_fields', array() );

		// Try to find the reference field in merge fields.
		if ( 'anaconda' === $crm_type ) {
			if ( isset( $merge_fields['id'] ) ) {
				return $merge_fields['id'];
			}
		} elseif ( 'inmovilla' === $crm_type ) {
			if ( isset( $merge_fields['referencia'] ) ) {
				return $merge_fields['referencia'];
			}
		} elseif ( 'inmovilla_procesos' === $crm_type ) {
			if ( isset( $merge_fields['cod_ofer'] ) ) {
				return $merge_fields['cod_ofer'];
			}
		}

		// Fallback to default property_id.
		return 'property_id';
	}
}
