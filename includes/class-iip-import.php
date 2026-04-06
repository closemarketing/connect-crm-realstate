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
		$this->settings        = get_option( 'ccrmre_settings' );
		$this->settings_fields = get_option( 'ccrmre_merge_fields' );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_manual_import' ) );
		add_action( 'wp_ajax_ccrmre_manual_import', array( $this, 'manual_import' ) );
		add_action( 'wp_ajax_ccrmre_get_import_stats', array( $this, 'get_import_stats' ) );
	}

	/**
	 * Manual import Requests
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function scripts_manual_import( $hook ) {
		if ( 'toplevel_page_ccrmre_options' !== $hook ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'iip-import' !== $active_tab ) {
			return;
		}

		wp_enqueue_script(
			'ccrmre-manual-sync',
			CCRMRE_PLUGIN_URL . 'includes/assets/iip-manual-sync.js',
			array( 'ccrmre-admin-import-stats' ),
			CCRMRE_VERSION,
			true
		);

		wp_localize_script(
			'ccrmre-manual-sync',
			'ccrmre_ajax_action',
			array(
				'url'                 => admin_url( 'admin-ajax.php' ),
				'label_sync'          => __( 'Sync', 'connect-crm-realstate' ),
				'label_syncing'       => __( 'Syncing', 'connect-crm-realstate' ),
				'label_sync_complete' => __( 'Finished', 'connect-crm-realstate' ),
				'label_waiting'       => __( 'Waiting', 'connect-crm-realstate' ),
				/* translators: %s is the number of seconds to wait before retrying. */
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

		$loop_page = $loop % $pagination;
		$page      = floor( $loop / $pagination ) + 1;

		// Date filter for "modified last X" modes (API returns only properties modified since this date).
		$changed_from = '';
		if ( 'modified_3h' === $mode ) {
			$changed_from = gmdate( 'Y-m-d H:i:s', strtotime( '-3 hours' ) );
		} elseif ( 'modified_24h' === $mode ) {
			$changed_from = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
		} elseif ( 'modified_7d' === $mode ) {
			$changed_from = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		}

		// Remove properties not in API before syncing.
		if ( 0 === $loop ) {
			SYNC::clear_property_meta();

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

		// When starting a new page, fetch from cached property IDs.
		if ( ( 0 === $loop_page && 0 < $pagination ) || ( 0 === $loop && -1 === $pagination ) ) {
			$result_api    = API::get_all_property_ids( $crm, true );
			$properties    = self::build_import_list_from_cached_ids( $crm, $result_api, $changed_from, $mode );
			$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'Connecting with API and syncing Properties ...', 'connect-crm-realstate' ) . '<br/>';

			if ( 'updated' === $mode && 'ok' === $result_api['status'] && isset( $result_api['count'] ) && $result_api['count'] > 0 ) {
				$api_properties = $result_api['count'];
				$progress_msg  .= '[' . date_i18n( 'H:i:s' ) . '] ' . sprintf(
					/* translators: %1$d: number of properties to update, %2$d: number of properties from API */
					__( 'Filtering properties to update... Found %1$d / %2$d properties.', 'connect-crm-realstate' ),
					count( $properties ),
					$api_properties
				) . '<br/>';
			}

			$totalprop = count( $properties );

			if ( 'error' === $result_api['status'] ) {
				$error_type = isset( $result_api['error_type'] ) ? $result_api['error_type'] : 'default';

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

			if ( 0 === $totalprop ) {
				if ( 0 === $loop ) {
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] <strong style="color:orange;">' . __( 'No properties found to import.', 'connect-crm-realstate' ) . '</strong><br/>';
				} else {
					$progress_msg .= '[' . date_i18n( 'H:i:s' ) . '] ' . __( 'No more properties from API. Import complete.', 'connect-crm-realstate' ) . '<br/>';
				}
				$property = null;
			} else {
				$i = 0;
				foreach ( $properties as $property_api ) {
					set_transient( 'ccrmre_query_property_loop_' . $i, $property_api, 30 * MINUTE_IN_SECONDS );
					++$i;
				}

				$property = isset( $properties[0] ) ? $properties[0] : null;
			}
		} else {
			$property = get_transient( 'ccrmre_query_property_loop_' . $loop );

			// Transient expired - re-fetch from cached IDs and rebuild loop transients.
			if ( false === $property && $loop < $totalprop ) {
				$result_api = API::get_all_property_ids( $crm, true );
				$properties = self::build_import_list_from_cached_ids( $crm, $result_api, $changed_from, $mode );

				if ( 'ok' === $result_api['status'] && ! empty( $properties ) ) {
					$i = 0;
					foreach ( $properties as $property_api ) {
						set_transient( 'ccrmre_query_property_loop_' . $i, $property_api, 30 * MINUTE_IN_SECONDS );
						++$i;
					}
					$loop_page = $loop % $pagination;
					$property  = isset( $properties[ $loop_page ] ) ? $properties[ $loop_page ] : null;
				}
			}
		}

		$finish = false;
		$is_new = false;

		if ( ! empty( $property ) ) {
			$line_prefix  = '[' . date_i18n( 'H:i:s' ) . '] ' . ( $loop + 1 ) . ' - ' . __( 'Property', 'connect-crm-realstate' ) . ' ';
			$is_available = SYNC::is_property_available( $property, $crm );

			$id_display = '?';
			if ( 'inmovilla' === $crm || 'inmovilla_procesos' === $crm ) {
				$id_display = isset( $property['cod_ofer'] ) ? $property['cod_ofer'] : ( isset( $property['id'] ) ? $property['id'] : '?' );
			} elseif ( isset( $property['id'] ) ) {
				$id_display = $property['id'];
			}
			if ( ! $is_available ) {
				$result_sync   = SYNC::handle_unavailable_property( $property, $this->settings, $this->settings_fields, $crm );
				$ref_display   = isset( $result_sync['reference'] ) ? $result_sync['reference'] : $id_display;
				$title_display = isset( $result_sync['title'] ) ? substr( $result_sync['title'], 0, 50 ) : '';
				$city_display  = isset( $result_sync['city'] ) ? $result_sync['city'] : '';
				$progress_msg .= self::format_sync_progress_line( $loop + 1, esc_html( $ref_display ), __( 'NOT IMP', 'connect-crm-realstate' ), $title_display, $city_display ) . ' ';
				$progress_msg .= $result_sync['message'];
			} else {
				$result_get_property = API::get_property( $property, $crm );
				if ( 'ok' !== $result_get_property['status'] ) {
					$error_type = isset( $result_get_property['error_type'] ) ? $result_get_property['error_type'] : 'default';

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

					$progress_msg .= $line_prefix . esc_html( $id_display ) . ' ERR - ';
					$progress_msg .= '<strong style="color:red;">' . __( 'API ERROR:', 'connect-crm-realstate' ) . '</strong> ' . $result_get_property['message'] . '<br/>';
					wp_send_json_success(
						array(
							'loop'       => $loop + 1,
							'message'    => $progress_msg,
							'pagination' => $pagination,
							'totalprop'  => $totalprop,
							'finish'     => $finish,
							'is_new'     => $is_new,
						)
					);
				}
				$property_complete = $result_get_property['data'];
				$result_sync       = SYNC::sync_property( $property_complete, $this->settings, $this->settings_fields );
				$ref_display       = isset( $result_sync['reference'] ) ? $result_sync['reference'] : $id_display;
				$title_display     = isset( $result_sync['title'] ) ? substr( $result_sync['title'], 0, 50 ) : '';
				$city_display      = isset( $result_sync['city'] ) ? $result_sync['city'] : '';
				$action            = ! empty( $result_sync['is_new'] ) ? __( 'NEW', 'connect-crm-realstate' ) : __( 'UPD', 'connect-crm-realstate' );
				$progress_msg     .= self::format_sync_progress_line( $loop + 1, esc_html( $ref_display ), $action, $title_display, $city_display ) . ' ';
				$is_new            = ! empty( $result_sync['is_new'] ) ? true : false;

				if ( ! empty( $result_sync['post_id'] ) ) {
					$edit_link     = get_edit_post_link( $result_sync['post_id'] );
					$progress_msg .= ' - <a href="' . esc_url( $edit_link ) . '" target="_blank">' . __( 'Edit', 'connect-crm-realstate' ) . '</a>';

					$view_link     = get_permalink( $result_sync['post_id'] );
					$progress_msg .= ' - <a href="' . esc_url( $view_link ) . '" target="_blank">' . __( 'View', 'connect-crm-realstate' ) . '</a>';
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
			$progress_msg .= '<br/>[' . date_i18n( 'H:i:s' ) . '] <strong style="color:green;">' . esc_html__( 'Import completed successfully!', 'connect-crm-realstate' ) . '</strong><br/>';

			$size_clean = -1 === $pagination ? $totalprop : $pagination;
			for ( $i = 0; $i < $size_clean; $i++ ) {
				delete_transient( 'ccrmre_query_property_loop_' . $i );
			}
		}

		wp_send_json_success(
			array(
				'loop'       => $loop + 1,
				'message'    => $progress_msg,
				'pagination' => $pagination,
				'totalprop'  => $totalprop,
				'finish'     => $finish,
				'is_new'     => $is_new,
			)
		);
	}

	/**
	 * Build list of property items for import from cached get_all_property_ids result.
	 * Applies changed_from date filter and "updated" mode filter (new/outdated only).
	 *
	 * @param string $crm         CRM type.
	 * @param array  $result_api  Return from API::get_all_property_ids( $crm, true ).
	 * @param string $changed_from Optional date string; only include properties with last_updated >= this.
	 * @param string $mode        Import mode: 'updated' to filter to new/outdated only, else use all.
	 * @return array List of items (minimal item + status) for the import loop.
	 */
	public static function build_import_list_from_cached_ids( $crm, $result_api, $changed_from = '', $mode = 'updated' ) {
		if ( 'ok' !== ( isset( $result_api['status'] ) ? $result_api['status'] : '' ) || ! isset( $result_api['data'] ) || ! is_array( $result_api['data'] ) ) {
			return array();
		}

		$data = $result_api['data'];

		if ( ! empty( $changed_from ) ) {
			$from_ts = strtotime( $changed_from );
			$data    = array_filter(
				$data,
				function ( $meta ) use ( $from_ts ) {
					$lu = isset( $meta['last_updated'] ) ? $meta['last_updated'] : '';
					return '' !== $lu && strtotime( $lu ) >= $from_ts;
				}
			);
		}

		if ( 'updated' === $mode ) {
			$fake_properties = array();
			foreach ( $data as $id => $meta ) {
				if ( 'anaconda' === $crm ) {
					$fake_properties[] = array(
						'id'         => $id,
						'updated_at' => isset( $meta['last_updated'] ) ? $meta['last_updated'] : '',
						'status'     => isset( $meta['status'] ) ? $meta['status'] : true,
					);
				} else {
					$fake_properties[] = array(
						'cod_ofer'     => $id,
						'ref'          => $id,
						'fechaact'     => isset( $meta['last_updated'] ) ? $meta['last_updated'] : '',
						'nodisponible' => ( isset( $meta['status'] ) && $meta['status'] ) ? 0 : 1,
					);
				}
			}
			$filtered = SYNC::filter_properties_to_update( $fake_properties, $crm );
			$ids      = array();
			foreach ( $filtered as $p ) {
				$pid = isset( $p['id'] ) ? $p['id'] : ( isset( $p['cod_ofer'] ) ? $p['cod_ofer'] : null );
				if ( null !== $pid && isset( $data[ $pid ] ) ) {
					$ids[] = $pid;
				}
			}
		} else {
			$ids = array_keys( $data );
		}

		// Batch mode: limit to first 20 properties for testing purposes.
		if ( 'batch' === $mode ) {
			$ids = array_slice( $ids, 0, 20 );
		}

		$items = array();
		foreach ( $ids as $id ) {
			$minimal = SYNC::build_minimal_item( $id, $crm );
			$status  = isset( $data[ $id ]['status'] ) ? $data[ $id ]['status'] : true;
			$items[] = array_merge( $minimal, array( 'status' => $status ) );
		}

		return $items;
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
			wp_send_json_error( array( 'message' => __( 'CRM type not configured', 'connect-crm-realstate' ) ) );
		}

		$api_result     = API::get_all_property_ids( $crm_type, true );
		$api_properties = isset( $api_result['data'] ) ? $api_result['data'] : array();
		$api_count      = count( $api_properties );
		$api_ids        = array_keys( $api_properties );

		$available_properties = array();
		foreach ( $api_properties as $prop_id => $prop_data ) {
			if ( SYNC::is_property_available( $prop_data, $crm_type ) ) {
				$available_properties[ $prop_id ] = $prop_data;
			}
		}

		/**
		 * Filter available properties for import stats (e.g. by postal code or province in PRO).
		 *
		 * @param array $available_properties Map of property_id => prop_data.
		 * @param array $api_properties      All API properties.
		 */
		$available_properties = apply_filters( 'ccrmre_available_properties_for_stats', $available_properties, $api_properties );

		$wp_properties = SYNC::get_wordpress_property_data( $crm_type );

		/**
		 * Filter WordPress properties for import stats (e.g. limit to province-filtered IDs in PRO).
		 *
		 * @param array $wp_properties        Map of property_id => array( last_updated ).
		 * @param array $available_properties Already-filtered available properties from API.
		 */
		$wp_properties = apply_filters( 'ccrmre_wordpress_properties_for_stats', $wp_properties, $available_properties );

		$wp_count = count( $wp_properties );

		$counts = self::compute_import_stats( $available_properties, $wp_properties, $api_ids );

		$response = array_merge(
			array(
				'api_count'                  => $api_count,
				'available_count'            => count( $available_properties ),
				'wp_count'                   => $wp_count,
				'filtered_by_province_count' => 0,
			),
			$counts
		);

		/**
		 * Filter stats response (e.g. PRO can set api_count to filtered count when postal filter is active).
		 *
		 * @param array $response Keys: api_count, available_count, wp_count, new_count, outdated_count, import_count, delete_count.
		 */
		$response = apply_filters( 'ccrmre_import_stats_response', $response );

		wp_send_json_success( $response );
	}

	/**
	 * Compute import stats (new, outdated, import_count, delete_count) from available and WP data.
	 * Used by get_import_stats() and by unit tests.
	 *
	 * @param array $available_properties Map of property_id => array( last_updated?, ... ) from API (available only).
	 * @param array $wp_properties       Map of property_id => array( last_updated? ) from WordPress.
	 * @param array $api_ids             All property IDs from API (for delete count).
	 * @return array{new_count: int, outdated_count: int, import_count: int, delete_count: int}
	 */
	public static function compute_import_stats( array $available_properties, array $wp_properties, array $api_ids ) {
		$available_ids = array_keys( $available_properties );
		$wp_ids        = array_keys( $wp_properties );

		$new_count = count( array_diff( $available_ids, $wp_ids ) );

		// Outdated: in WP and API but API has a newer last_updated (we no longer store status in WP).
		$outdated_count = 0;
		foreach ( $wp_properties as $wp_id => $wp_data ) {
			if ( isset( $available_properties[ $wp_id ] ) ) {
				$api_data     = $available_properties[ $wp_id ];
				$api_date     = isset( $api_data['last_updated'] ) ? $api_data['last_updated'] : null;
				$wp_date      = isset( $wp_data['last_updated'] ) ? $wp_data['last_updated'] : null;
				$needs_update = false;

				if ( ! empty( $api_date ) && ! empty( $wp_date ) ) {
					$api_timestamp = strtotime( $api_date );
					$wp_timestamp  = strtotime( $wp_date );
					if ( $api_timestamp > $wp_timestamp ) {
						$needs_update = true;
					}
				}

				if ( $needs_update ) {
					++$outdated_count;
				}
			}
		}

		$import_count = $new_count + $outdated_count;
		$delete_count = count( array_diff( $wp_ids, $api_ids ) );

		return array(
			'new_count'      => $new_count,
			'outdated_count' => $outdated_count,
			'import_count'   => $import_count,
			'delete_count'   => $delete_count,
		);
	}

	/**
	 * Returns " — Title - City" for progress message when title or city present.
	 *
	 * @param string $title Property title (e.g. first 50 chars).
	 * @param string $city  Property city.
	 * @return string
	 */
	private function format_title_city_suffix( $title, $city ) {
		return self::format_title_city_suffix_static( $title, $city );
	}

	/**
	 * Returns " — Title - City" for progress message (shared with WP-CLI).
	 *
	 * @param string $title Property title (e.g. first 50 chars).
	 * @param string $city  Property city.
	 * @return string
	 */
	public static function format_title_city_suffix_static( $title, $city ) {
		$title = '' !== $title ? esc_html( $title ) : '';
		$city  = '' !== $city ? esc_html( $city ) : '';
		if ( '' === $title && '' === $city ) {
			return '';
		}
		if ( '' !== $title && '' !== $city ) {
			return ' — ' . $title . ' - ' . $city;
		}
		return ' — ' . ( '' !== $title ? $title : $city );
	}

	/**
	 * Formats a single property progress line (shared with WP-CLI).
	 *
	 * Example: [10:31:57] 12 - Property 27759997 NEW — Amplia finca... - Fuente Vaqueros
	 *
	 * @param int    $index   One-based position (e.g. loop + 1).
	 * @param string $ref     Reference or property ID.
	 * @param string $action  Action label: NEW, UPD, or NOT IMP (already translated).
	 * @param string $title   Property title (e.g. substr 0, 50).
	 * @param string $city    Property city.
	 * @return string
	 */
	public static function format_sync_progress_line( $index, $ref, $action, $title, $city ) {
		$time   = date_i18n( 'H:i:s' );
		$label  = __( 'Property', 'connect-crm-realstate' );
		$suffix = self::format_title_city_suffix_static( $title, $city );
		return '[' . $time . '] ' . (int) $index . ' - ' . $label . ' ' . (string) $ref . ' ' . $action . $suffix;
	}
}
