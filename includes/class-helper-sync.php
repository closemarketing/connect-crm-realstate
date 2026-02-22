<?php
/**
 * Library for Sync connection
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Api Connection.
 *
 * @since 1.0.0
 */
class SYNC {
	/**
	 * Syncs property from item API.
	 *
	 * @param array $item Item from API.
	 * @param array $settings Settings.
	 * @param array $settings_fields Settings fields.
	 * @return array
	 */
	public static function sync_property( $item, $settings = array(), $settings_fields = array() ) {
		$message             = '';
		$settings            = empty( $settings ) ? get_option( 'conncrmreal_settings' ) : $settings;
		$crm                 = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		$settings_fields     = empty( $settings_fields ) ? get_option( 'conncrmreal_merge_fields' ) : $settings_fields;
		$post_type           = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';
		$filter_postal_code  = isset( $settings['postal_code'] ) ? $settings['postal_code'] : '';
		$property_info_early = API::get_property_info( $item, $crm );
		$property_id         = $property_info_early['id'];
		$property_post_id    = self::find_property( $property_id, $post_type );

		if ( 'inmovilla_procesos' === $crm ) {
			// Inmovilla Procesos: descripciones and tituloes are direct strings.
			$property_title       = isset( $item['tituloes'] ) ? $item['tituloes'] : __( 'Property', 'connect-crm-real-state' );
			$property_description = isset( $item['descripciones'] ) ? $item['descripciones'] : '';
			$property_city        = isset( $item['ciudad'] ) ? $item['ciudad'] : '';
		} elseif ( 'inmovilla' === $crm ) {
			// Inmovilla APIWEB: descripciones is an array with titulo and descrip.
			$descripciones        = isset( $item['descripciones'] ) ? $item['descripciones'] : array();
			$property_title       = isset( $descripciones['titulo'] ) ? $descripciones['titulo'] : __( 'Property', 'connect-crm-real-state' );
			$property_description = isset( $descripciones['descrip'] ) ? $descripciones['descrip'] : '';
			$property_city        = isset( $item['ciudad'] ) ? $item['ciudad'] : '';
		} else {
			// Anaconda.
			$property_title       = isset( $item['name'] ) ? $item['name'] : __( 'Property', 'connect-crm-real-state' );
			$property_description = isset( $item['description'] ) ? $item['description'] : '';
			$property_city        = isset( $item['city'] ) ? $item['city'] : '';
		}

		if ( self::cannot_import( $item, $filter_postal_code ) ) {
			$reference = self::get_reference_from_item( $item, $crm );
			$message   = __( 'NOT Imported', 'connect-crm-real-state' );
			$message  .= self::add_end_message( $property_id, $property_title, $property_city, $reference );

			return array(
				'property_id' => $property_id,
				'message'     => $message,
			);
		}

		$property_description = self::process_description( $property_description );

		// Property info.
		$property_info = array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
		);

		// Meta Info.
		$property_info['meta_input'] = array();

		// Base fields.
		$property_info_meta          = API::get_property_info( $item, $crm, true );
		$property_info['meta_input'] = $property_info_meta;

		// Merge fields.
		if ( empty( $settings_fields ) ) {
			// Method without merge fields.
			foreach ( $item as $key => $item_meta ) {
				if ( 'description' === $key || 'name' === $key || 'descripciones' === $key ) {
					continue;
				}
				$item_meta = self::format_item_meta( $crm, $item_meta, $key );

				$property_info['meta_input'][ 'property_' . $key ] = $item_meta;
			}
		} else {
			// Method with merge fields.
			foreach ( $settings_fields as $key => $field ) {
				if ( 'descripciones' === $key ) {
					continue;
				}
				$item_meta = isset( $item[ $key ] ) ? $item[ $key ] : '';
				$item_meta = self::format_item_meta( $crm, $item_meta, $key );

				$property_info['meta_input'][ $field ] = $item_meta;
			}
		}

		if ( empty( $property_post_id ) ) {
			$property_info['post_title']   = $property_title;
			$property_info['post_name']    = sanitize_title( $property_title );
			$property_info['post_content'] = $property_description;
			$property_post_id              = wp_insert_post( $property_info );
			$message                      .= __( 'Created', 'connect-crm-real-state' );
		} else {
			$message            .= __( 'Updated', 'connect-crm-real-state' );
			$property_info['ID'] = $property_post_id;
			wp_update_post( $property_info );
		}
		$reference = self::get_reference_from_item( $item, $crm );
		$message  .= self::add_end_message( $property_id, $property_title, $property_city, $reference );

		if ( ! empty( $property_post_id ) ) {
			update_post_meta( $property_post_id, 'property_synced', true );
			delete_post_meta( $property_post_id, 'property_description' );
			delete_post_meta( $property_post_id, 'property_name' );

			// Save fixed meta keys for property identification and sync tracking.
			$property_info_meta = API::get_property_info( $item, $crm );
			update_post_meta( $property_post_id, 'ccrmre_property_id', $property_id );
			if ( ! empty( $property_info_meta['last_updated'] ) ) {
				update_post_meta( $property_post_id, 'ccrmre_last_updated', $property_info_meta['last_updated'] );
			}
			if ( isset( $property_info_meta['status'] ) ) {
				update_post_meta( $property_post_id, 'ccrmre_status', $property_info_meta['status'] );
			}

			// Save photo URLs and optionally download images.
			if ( isset( $item['fotos'] ) && is_array( $item['fotos'] ) && ! empty( $item['fotos'] ) ) {
				$download_mode = isset( $settings['download_images'] ) ? $settings['download_images'] : 'no';

				// Always save external URLs as reference.
				update_post_meta( $property_post_id, 'ccrmre_gallery_urls', $item['fotos'] );
				update_post_meta( $property_post_id, 'ccrmre_featured_image_url', $item['fotos'][0] );

				if ( 'featured' === $download_mode || 'all' === $download_mode ) {
					// Download first photo and set as real featured image.
					self::download_and_set_featured_image( $property_post_id, $item['fotos'][0] );
				}

				if ( 'all' === $download_mode ) {
					// Download all gallery images locally.
					self::download_gallery_images( $property_post_id, $item['fotos'] );
				}
			}

			// Clear statistics cache after syncing.
			delete_transient( 'ccrmre_wp_properties_' . $crm );
		}

		return array(
			'property_id' => $property_id,
			'post_id'     => $property_post_id,
			'message'     => $message,
		);
	}

	/**
	 * Formats the item meta.
	 *
	 * @param string $crm CRM type.
	 * @param string $item_meta Item meta.
	 * @param string $key Key.
	 *
	 * @return string
	 */
	public static function format_item_meta( $crm, $item_meta, $key ) {
		if ( 'inmovilla_procesos' === $crm ) {
			$enums = API::get_enums( $crm, $key );
			switch ( $key ) {
				case 'precioinmo':
					return number_format( $item_meta, 0, ',', '.' ) . ' €';
				case 'key_loca':
					$key_loca    = (int) $item_meta;
					$ciudad_data = isset( $enums['key_loca'][ $key_loca ] ) ? $enums['key_loca'][ $key_loca ] : null;
					if ( ! empty( $ciudad_data ) ) {
						if ( is_array( $ciudad_data ) && isset( $ciudad_data['city'] ) ) {
							$item_meta = $ciudad_data['city'];
						} elseif ( is_string( $ciudad_data ) ) {
							$item_meta = $ciudad_data;
						}
					}
					return $item_meta;
				default:
					if ( ! empty( $enums[ $key ] ) ) {
						$item_meta = $enums[ $key ];
					}
					return $item_meta;
			}
		}
		return $item_meta;
	}

	/**
	 * Processes the description.
	 *
	 * @param string $description Description.
	 * @return string
	 */
	public static function process_description( $description ) {
		// Split by ~ to get lines.
		$lines = explode( '~', $description );

		// Filter empty lines and trim whitespace.
		$lines = array_filter(
			array_map(
				function ( $line ) {
					return trim( $line );
				},
				$lines
			),
			function ( $line ) {
				return ! empty( $line );
			}
		);

		// Convert to Gutenberg blocks.
		$blocks = array();

		foreach ( $lines as $line ) {
			// Convert **text** to <strong>text</strong>.
			$line = preg_replace( '/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $line );

			// Create paragraph block.
			$blocks[] = '<!-- wp:paragraph -->';
			$blocks[] = '<p>' . $line . '</p>';
			$blocks[] = '<!-- /wp:paragraph -->';
			$blocks[] = '';
		}

		return implode( "\n", $blocks );
	}

	/**
	 * Gets reference field from API item by CRM type.
	 *
	 * @param array  $item API item.
	 * @param string $crm  CRM type.
	 * @return string|null Reference value or null.
	 */
	private static function get_reference_from_item( $item, $crm ) {
		if ( 'inmovilla_procesos' === $crm && isset( $item['ref'] ) ) {
			return $item['ref'];
		}
		if ( 'inmovilla' === $crm && isset( $item['referencia'] ) ) {
			return $item['referencia'];
		}
		if ( 'anaconda' === $crm && isset( $item['referencia'] ) ) {
			return $item['referencia'];
		}
		return null;
	}

	/**
	 * Adds end message (reference when provided, otherwise property ID).
	 *
	 * @param string      $property_id    Property ID (internal).
	 * @param string      $property_title  Property title.
	 * @param string      $property_city   Property city.
	 * @param string|null $reference       Reference (ref) when available.
	 * @return string
	 */
	private static function add_end_message( $property_id, $property_title, $property_city = '', $reference = null ) {
		if ( null !== $reference && '' !== $reference ) {
			$message  = ' ' . __( 'Reference:', 'connect-crm-real-state' ) . ' ';
			$message .= $reference;
		} else {
			$message  = ' ' . __( 'Property ID:', 'connect-crm-real-state' ) . ' ';
			$message .= $property_id;
		}
		$message .= ' ' . substr( $property_title, 0, 50 ) . ' - ' . $property_city;
		return $message;
	}

	/**
	 * Filters the property depending of settings.
	 *
	 * @param array  $item Item from API.
	 * @param string $filter_postal_code Postal code filter.
	 * @return boolean
	 */
	private static function cannot_import( $item, $filter_postal_code ) {
		$property_postal_code = isset( $item['postal_code'] ) ? trim( $item['postal_code'] ) : '';

		if ( empty( $property_postal_code ) ) {
			return false;
		}

		$filters = explode( ',', $filter_postal_code );
		foreach ( $filters as $filter ) {
			$filter = trim( $filter );
			if ( empty( $filter ) ) {
				continue;
			}
			if ( $filter === $property_postal_code ) {
				return false;
			} elseif ( fnmatch( $filter, $property_postal_code ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Finds property by property_id.
	 *
	 * @param string $property_id Property ID.
	 * @param string $post_type Post type.
	 *
	 * @return int
	 */
	public static function find_property( $property_id, $post_type ) {
		$property = get_posts(
			array(
				'post_type'  => $post_type,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => 'ccrmre_property_id',
						'value'   => $property_id,
						'compare' => '=',
					),
				),
			)
		);
		if ( ! empty( $property[0] ) ) {
			return (int) $property[0];
		} else {
			return 0;
		}
	}

	/**
	 * Clears property meta.
	 *
	 * @return void
	 */
	public static function clear_property_meta() {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta
				WHERE meta_key = %s",
				'property_synced'
			)
		);
	}

	/**
	 * Checks if property is available in listing.
	 *
	 * @param array  $property Property data from listing.
	 * @param string $crm CRM type.
	 * @return bool
	 */
	public static function is_property_available( $property, $crm ) {
		if ( isset( $property['status'] ) ) {
			return (bool) $property['status'];
		}

		if ( 'inmovilla_procesos' === $crm ) {
			// Check nodisponible field (1 = not available, 0 = available).
			return ! isset( $property['nodisponible'] ) || 1 !== (int) $property['nodisponible'];
		} elseif ( 'inmovilla' === $crm ) {
			// Check estado field in Inmovilla APIWEB.
			return ! isset( $property['estado'] ) || 'V' !== $property['estado'];
		} elseif ( 'anaconda' === $crm ) {
			// Check operation_status field in Anaconda.
			return ! isset( $property['operation_status'] ) || 'Vendido' !== $property['operation_status'];
		}

		// Default: assume available.
		return true;
	}

	/**
	 * Handles unavailable property according to settings.
	 *
	 * @param array  $property Property data from listing.
	 * @param array  $settings Settings.
	 * @param array  $settings_fields Settings fields.
	 * @param string $crm CRM type.
	 * @return array
	 */
	public static function handle_unavailable_property( $property, $settings = array(), $settings_fields = array(), $crm = 'anaconda' ) {
		$settings         = empty( $settings ) ? get_option( 'conncrmreal_settings' ) : $settings;
		$settings_fields  = empty( $settings_fields ) ? get_option( 'conncrmreal_merge_fields' ) : $settings_fields;
		$post_type        = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';
		$sold_action      = isset( $settings['sold_action'] ) ? $settings['sold_action'] : 'draft';
		$property_info_h  = API::get_property_info( $property, $crm );
		$property_id      = $property_info_h['id'];
		$property_post_id = self::find_property( $property_id, $post_type );

		if ( empty( $property_post_id ) ) {
			// Property doesn't exist in WordPress, skip it.
			$reason = self::get_unavailable_reason( $property, $crm );

			return array(
				'property_id' => $property_id,
				'message'     => __( 'Skipped (Not Available in CRM)', 'connect-crm-real-state' ) . $reason,
			);
		}

		// Property exists, apply action according to settings.
		$message = '';

		$reason = self::get_unavailable_reason( $property, $crm );

		switch ( $sold_action ) {
			case 'draft':
				wp_update_post(
					array(
						'ID'          => $property_post_id,
						'post_status' => 'draft',
					)
				);
				$message = __( 'Unpublished (Set to Draft)', 'connect-crm-real-state' ) . $reason;
				break;

			case 'trash':
				wp_trash_post( $property_post_id );
				$message = __( 'Moved to Trash', 'connect-crm-real-state' ) . $reason;
				break;

			case 'keep':
			default:
				$message = __( 'Kept Published (Not Available)', 'connect-crm-real-state' ) . $reason;
				break;
		}

		return array(
			'property_id' => $property_id,
			'post_id'     => $property_post_id,
			'message'     => $message,
		);
	}

	/**
	 * Returns a human-readable reason why the property is not available.
	 *
	 * @param array  $property Property data from API.
	 * @param string $crm CRM type.
	 * @return string Reason string with leading separator, or empty if unknown.
	 */
	private static function get_unavailable_reason( $property, $crm ) {
		if ( isset( $property['status'] ) && ! (bool) $property['status'] ) {
			/* translators: %s: status field value from the API. */
			return ' — ' . sprintf( __( 'Reason: status = %s', 'connect-crm-real-state' ), esc_html( $property['status'] ) );
		}

		if ( 'inmovilla_procesos' === $crm && isset( $property['nodisponible'] ) && 1 === (int) $property['nodisponible'] ) {
			return ' — ' . __( 'Reason: nodisponible = 1', 'connect-crm-real-state' );
		}

		if ( 'inmovilla' === $crm && isset( $property['estado'] ) && 'V' === $property['estado'] ) {
			return ' — ' . __( 'Reason: estado = V (Sold)', 'connect-crm-real-state' );
		}

		if ( 'anaconda' === $crm && isset( $property['operation_status'] ) && 'Vendido' === $property['operation_status'] ) {
			return ' — ' . __( 'Reason: operation_status = Sold', 'connect-crm-real-state' );
		}

		return '';
	}

	/**
	 * Removes properties that are not in API before sync starts.
	 *
	 * @param string $crm_type CRM type.
	 * @return array Array with count and detailed info of removed properties.
	 */
	public static function remove_properties_not_in_api( $crm_type ) {
		$settings  = get_option( 'conncrmreal_settings' );
		$post_type = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';
		$meta_name = 'ccrmre_property_id';

		// Get all property IDs from API.
		$api_result = API::get_all_property_ids( $crm_type, false );
		if ( 'error' === $api_result['status'] ) {
			return array(
				'status'  => 'error',
				'message' => $api_result['message'],
				'count'   => 0,
				'details' => array(),
			);
		}

		$api_properties = isset( $api_result['data'] ) ? $api_result['data'] : array();
		$api_ids        = array_keys( $api_properties );

		// Get all property IDs from WordPress.
		$wp_properties = self::get_wordpress_property_data();
		$wp_ids        = array_keys( $wp_properties );

		// Find properties in WordPress that are NOT in API.
		$to_remove       = array_diff( $wp_ids, $api_ids );
		$removed_details = array();

		foreach ( $to_remove as $property_ref ) {
			// Find the WordPress post by property reference.
			$post_id = self::find_property( $property_ref, $post_type );

			if ( ! empty( $post_id ) ) {
				$post_title        = get_the_title( $post_id );
				$removed_details[] = array(
					'post_id'     => $post_id,
					'title'       => $post_title,
					'property_id' => $property_ref,
				);

				wp_trash_post( $post_id );
			}
		}

		// Clear statistics cache.
		delete_transient( 'ccrmre_wp_properties_' . $crm_type );
		delete_transient( 'ccrmre_api_properties_' . $crm_type );

		return array(
			'status'  => 'ok',
			'count'   => count( $removed_details ),
			'details' => $removed_details,
		);
	}

	/**
	 * Sends to trash not synced products.
	 *
	 * @return array Array with count and detailed info of trashed properties.
	 */
	public static function trash_not_synced() {
		$settings  = get_option( 'conncrmreal_settings' );
		$post_type = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';
		$meta_name = 'ccrmre_property_id';

		$args            = array(
			'posts_per_page' => -1,
			'post_type'      => $post_type,
			'meta_query'     => array(
				array(
					'key'     => 'property_synced',
					'compare' => 'NOT EXISTS',
				),
			),
		);
		$posts_to_delete = get_posts( $args );
		$trashed_details = array();

		foreach ( $posts_to_delete as $post ) {
			$post_id     = is_object( $post ) ? $post->ID : $post;
			$post_title  = get_the_title( $post_id );
			$property_id = get_post_meta( $post_id, $meta_name, true );

			$trashed_details[] = array(
				'post_id'     => $post_id,
				'title'       => $post_title,
				'property_id' => $property_id,
			);

			wp_trash_post( $post_id );
		}

		return array(
			'count'   => count( $posts_to_delete ),
			'details' => $trashed_details,
		);
	}

	/**
	 * Get WordPress property data with dates and status
	 *
	 * @return array Associative array of property_id => array(last_updated, status)
	 */
	public static function get_wordpress_property_data() {
		global $wpdb;

		$settings  = get_option( 'conncrmreal_settings' );
		$post_type = isset( $settings['post_type'] ) ? $settings['post_type'] : 'ccrmre_property';

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
				WHERE p.post_type = %s
				AND p.post_status != 'trash'
				AND pm1.meta_key = 'ccrmre_property_id'",
				$post_type
			),
			ARRAY_A
		);
		// phpcs:enable

		$property_data = array();
		foreach ( $results as $row ) {
			if ( ! empty( $row['property_ref'] ) ) {
				$property_data[ $row['property_ref'] ] = array(
					'last_updated' => $row['last_updated'],
					'status'       => (bool) $row['status'],
				);
			}
		}

		return $property_data;
	}

	/**
	 * Get reference meta key - always returns the fixed meta key for property identification.
	 *
	 * @return string Fixed meta key for property reference.
	 */
	public static function get_reference_meta_key() {
		return 'ccrmre_property_id';
	}

	/**
	 * Downloads an image from a URL and sets it as the post featured image.
	 *
	 * Avoids re-downloading if the URL has not changed since the last sync.
	 * Falls back gracefully: if the download fails the property is kept without
	 * a featured image rather than aborting the whole import.
	 *
	 * @param int    $post_id   WordPress post ID.
	 * @param string $image_url External image URL.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	public static function download_and_set_featured_image( $post_id, $image_url ) {
		if ( empty( $image_url ) || empty( $post_id ) ) {
			return false;
		}

		// Check if the URL is the same as the one already downloaded.
		$saved_url     = get_post_meta( $post_id, 'ccrmre_featured_image_url', true );
		$current_thumb = get_post_thumbnail_id( $post_id );

		if ( $saved_url === $image_url && ! empty( $current_thumb ) && false !== get_post( $current_thumb ) ) {
			// URL unchanged and attachment still exists — skip download.
			return (int) $current_thumb;
		}

		// Require WordPress media helpers.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download the file to a temp location.
		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return false;
		}

		// Build a clean file name from the URL.
		$url_path  = wp_parse_url( $image_url, PHP_URL_PATH );
		$file_name = ! empty( $url_path ) ? sanitize_file_name( basename( $url_path ) ) : 'property-image.jpg';

		$file_array = array(
			'name'     => $file_name,
			'tmp_name' => $tmp,
		);

		// Sideload the file into the media library, attached to the post.
		$attachment_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			// Clean up the temp file if sideload failed.
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return false;
		}

		// Delete the previous attachment if it differs from the new one.
		if ( ! empty( $current_thumb ) && (int) $current_thumb !== $attachment_id ) {
			wp_delete_attachment( (int) $current_thumb, true );
		}

		// Set the attachment as the post featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Downloads all gallery images and saves their attachment IDs.
	 *
	 * Compares the current URLs with previously saved ones to avoid
	 * re-downloading unchanged images. Stores IDs in the meta key
	 * ccrmre_gallery_attachment_ids.
	 *
	 * @param int   $post_id    WordPress post ID.
	 * @param array $image_urls Array of external image URLs.
	 * @return array Array of attachment IDs (may contain gaps where downloads failed).
	 */
	public static function download_gallery_images( $post_id, $image_urls ) {
		if ( empty( $image_urls ) || ! is_array( $image_urls ) || empty( $post_id ) ) {
			return array();
		}

		// Get previously saved data for comparison.
		$saved_urls = get_post_meta( $post_id, 'ccrmre_gallery_urls', true );
		$saved_ids  = get_post_meta( $post_id, 'ccrmre_gallery_attachment_ids', true );
		$saved_urls = is_array( $saved_urls ) ? $saved_urls : array();
		$saved_ids  = is_array( $saved_ids ) ? $saved_ids : array();

		// Require WordPress media helpers.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_ids = array();

		foreach ( $image_urls as $index => $image_url ) {
			// Check if this URL already has a valid local attachment.
			if (
				isset( $saved_urls[ $index ] ) &&
				$saved_urls[ $index ] === $image_url &&
				isset( $saved_ids[ $index ] ) &&
				! empty( $saved_ids[ $index ] ) &&
				false !== get_post( $saved_ids[ $index ] )
			) {
				// URL unchanged and attachment exists — reuse it.
				$attachment_ids[] = (int) $saved_ids[ $index ];
				continue;
			}

			// Download the file to a temp location.
			$tmp = download_url( $image_url );

			if ( is_wp_error( $tmp ) ) {
				$attachment_ids[] = 0;
				continue;
			}

			// Build a clean file name.
			$url_path  = wp_parse_url( $image_url, PHP_URL_PATH );
			$file_name = ! empty( $url_path ) ? sanitize_file_name( basename( $url_path ) ) : 'property-gallery-' . $index . '.jpg';

			$file_array = array(
				'name'     => $file_name,
				'tmp_name' => $tmp,
			);

			$attach_id = media_handle_sideload( $file_array, $post_id );

			if ( is_wp_error( $attach_id ) ) {
				if ( file_exists( $tmp ) ) {
					wp_delete_file( $tmp );
				}
				$attachment_ids[] = 0;
				continue;
			}

			// Delete the old attachment at this position if it changed.
			if ( isset( $saved_ids[ $index ] ) && ! empty( $saved_ids[ $index ] ) && (int) $saved_ids[ $index ] !== $attach_id ) {
				wp_delete_attachment( (int) $saved_ids[ $index ], true );
			}

			$attachment_ids[] = $attach_id;
		}

		// Delete any leftover attachments from previous syncs with more images.
		if ( count( $saved_ids ) > count( $image_urls ) ) {
			$saved_ids_count  = count( $saved_ids );
			$image_urls_count = count( $image_urls );
			for ( $i = $image_urls_count; $i < $saved_ids_count; $i++ ) {
				if ( ! empty( $saved_ids[ $i ] ) ) {
					wp_delete_attachment( (int) $saved_ids[ $i ], true );
				}
			}
		}

		update_post_meta( $post_id, 'ccrmre_gallery_attachment_ids', $attachment_ids );

		return $attachment_ids;
	}

	/**
	 * Returns property IDs present in the API but not yet in WordPress.
	 *
	 * @param string $crm_type CRM type.
	 * @return array Array with 'status' and 'data' (list of unsynced property IDs).
	 */
	public static function get_unsynced_property_ids( $crm_type ) {
		$api_result = API::get_all_property_ids( $crm_type, true );

		if ( 'error' === $api_result['status'] ) {
			return $api_result;
		}

		$api_properties = isset( $api_result['data'] ) ? $api_result['data'] : array();
		$wp_properties  = self::get_wordpress_property_data();

		// Properties in API but missing from WordPress.
		$unsynced = array_diff_key( $api_properties, $wp_properties );

		return array(
			'status' => 'ok',
			'data'   => array_keys( $unsynced ),
		);
	}

	/**
	 * Builds a minimal property item array sufficient for API::get_property().
	 *
	 * @param string $property_id Property ID or reference.
	 * @param string $crm_type    CRM type.
	 * @return array
	 */
	public static function build_minimal_item( $property_id, $crm_type ) {
		if ( 'inmovilla_procesos' === $crm_type ) {
			return array( 'cod_ofer' => $property_id );
		}
		if ( 'inmovilla' === $crm_type ) {
			return array(
				'cod_ofer'   => $property_id,
				'referencia' => $property_id,
			);
		}
		// Anaconda: get_property() returns the item as-is; include the ID field.
		return array( 'id' => $property_id );
	}

	/**
	 * Filter properties to only include those that need updating
	 *
	 * @param array  $properties List of properties from API.
	 * @param string $crm_type CRM type.
	 * @return array Filtered list of properties
	 */
	public static function filter_properties_to_update( $properties, $crm_type ) {
		// Get WordPress properties data with cache.
		$wp_properties = self::get_wordpress_property_data();
		$wp_refs       = array_keys( $wp_properties );

		if ( empty( $wp_refs ) ) {
			return $properties;
		}

		// Filter properties.
		$filtered = array();
		foreach ( $properties as $property ) {
			$property_info = API::get_property_info( $property, $crm_type );
			$property_ref  = $property_info['id'];

			if ( empty( $property_ref ) ) {
				continue;
			}

			// Check if property is new.
			if ( ! in_array( $property_ref, $wp_refs, true ) ) {
				// Only import new properties if they are available (status = true).
				$api_status = (bool) $property_info['status'];
				if ( $api_status ) {
					$filtered[] = $property;
				}
				continue;
			}

			// Check if property is outdated (date or status changed).
			if ( isset( $wp_properties[ $property_ref ] ) ) {
				$wp_data      = $wp_properties[ $property_ref ];
				$needs_update = false;

				// Get dates and status.
				$api_date   = $property_info['last_updated'];
				$wp_date    = isset( $wp_data['last_updated'] ) ? $wp_data['last_updated'] : null;
				$api_status = (bool) $property_info['status']; // Convert to bool for comparison.
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
					$filtered[] = $property;
				}
			}
		}

		return $filtered;
	}
}
