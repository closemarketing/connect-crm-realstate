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
		$message            = '';
		$settings           = empty( $settings ) ? get_option( 'conncrmreal_settings' ) : $settings;
		$crm                = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';
		$settings_fields    = empty( $settings_fields ) ? get_option( 'conncrmreal_merge_fields' ) : $settings_fields;
		$post_type          = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';
		$filter_postal_code = isset( $settings['postal_code'] ) ? $settings['postal_code'] : '';
		$key_id             = ( 'inmovilla' === $crm || 'inmovilla_procesos' === $crm ) ? 'cod_ofer' : 'id';
		$property_id        = isset( $item[ $key_id ] ) ? $item[ $key_id ] : '';
		$meta_name          = isset( $settings_fields[ $key_id ] ) ? $settings_fields[ $key_id ] : $key_id;
		$property_post_id   = self::find_property( $property_id, $post_type, $meta_name );

		if ( 'inmovilla_procesos' === $crm ) {
			// Inmovilla Procesos: descripciones and tituloes are direct strings.
			$property_title       = isset( $item['tituloes'] ) ? $item['tituloes'] : __( 'Property', 'connect-crm-realstate' );
			$property_description = isset( $item['descripciones'] ) ? $item['descripciones'] : '';
			$property_city        = isset( $item['ciudad'] ) ? $item['ciudad'] : '';
		} elseif ( 'inmovilla' === $crm ) {
			// Inmovilla APIWEB: descripciones is an array with titulo and descrip.
			$descripciones        = isset( $item['descripciones'] ) ? $item['descripciones'] : array();
			$property_title       = isset( $descripciones['titulo'] ) ? $descripciones['titulo'] : __( 'Property', 'connect-crm-realstate' );
			$property_description = isset( $descripciones['descrip'] ) ? $descripciones['descrip'] : '';
			$property_city        = isset( $item['ciudad'] ) ? $item['ciudad'] : '';
		} else {
			// Anaconda.
			$property_title       = isset( $item['name'] ) ? $item['name'] : __( 'Property', 'connect-crm-realstate' );
			$property_description = isset( $item['description'] ) ? $item['description'] : '';
			$property_city        = isset( $item['city'] ) ? $item['city'] : '';
		}

		if ( self::cannot_import( $item, $filter_postal_code ) ) {
			$message  = __( 'NOT Imported', 'connect-crm-realstate' );
			$message .= self::add_end_message( $property_id, $property_title, $property_city );

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
				$property_info['meta_input'][ 'property_' . $key ] = $item_meta;
			}
		} else {
			// Method with merge fields.
			foreach ( $settings_fields as $key => $field ) {
				if ( 'descripciones' === $key ) {
					continue;
				}
				$property_info['meta_input'][ $field ] = isset( $item[ $key ] ) ? $item[ $key ] : '';
			}
		}

		if ( empty( $property_post_id ) ) {
			$property_info['post_title']   = $property_title;
			$property_info['post_name']    = sanitize_title( $property_title );
			$property_info['post_content'] = $property_description;
			$property_post_id              = wp_insert_post( $property_info );
			$message                      .= __( 'Created', 'connect-crm-realstate' );
		} else {
			$message            .= __( 'Updated', 'connect-crm-realstate' );
			$property_info['ID'] = $property_post_id;
			wp_update_post( $property_info );
		}
		$message .= self::add_end_message( $property_id, $property_title, $property_city );

		if ( ! empty( $property_post_id ) ) {
			update_post_meta( $property_post_id, 'property_synced', true );
			delete_post_meta( $property_post_id, 'property_description' );
			delete_post_meta( $property_post_id, 'property_name' );

			// Save last updated date and status from CRM.
			$property_info_meta = API::get_property_info( $item, $crm );
			if ( ! empty( $property_info_meta['last_updated'] ) ) {
				update_post_meta( $property_post_id, 'ccrmre_last_updated', $property_info_meta['last_updated'] );
			}
			if ( isset( $property_info_meta['status'] ) ) {
				update_post_meta( $property_post_id, 'ccrmre_status', $property_info_meta['status'] );
			}

			// Save photo URLs for properties (without downloading).
			if ( isset( $item['fotos'] ) && is_array( $item['fotos'] ) && ! empty( $item['fotos'] ) ) {
				// Save first photo as featured image URL.
				update_post_meta( $property_post_id, 'ccrmre_featured_image_url', $item['fotos'][0] );

				// Save all photos for gallery.
				update_post_meta( $property_post_id, 'ccrmre_gallery_urls', $item['fotos'] );
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
	 * Adds end message.
	 *
	 * @param string $property_id Property ID.
	 * @param string $property_title Property title.
	 * @param string $property_city Property city.
	 * @return string
	 */
	private static function add_end_message( $property_id, $property_title, $property_city = '' ) {
		$message  = ' ' . __( 'Property ID:', 'connect-crm-realstate' ) . ' ';
		$message .= $property_id;
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
	 * @param string $key Meta key.
	 *
	 * @return int
	 */
	public static function find_property( $property_id, $post_type, $key = 'property_id' ) {
		$property = get_posts(
			array(
				'post_type'  => $post_type,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => $key,
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
		$key_id           = ( 'inmovilla' === $crm || 'inmovilla_procesos' === $crm ) ? 'cod_ofer' : 'id';
		$property_id      = isset( $property[ $key_id ] ) ? $property[ $key_id ] : '';
		$meta_name        = isset( $settings_fields[ $key_id ] ) ? $settings_fields[ $key_id ] : $key_id;
		$property_post_id = self::find_property( $property_id, $post_type, $meta_name );

		if ( empty( $property_post_id ) ) {
			// Property doesn't exist in WordPress, skip it.
			return array(
				'property_id' => $property_id,
				'message'     => __( 'Skipped (Not Available in CRM)', 'connect-crm-realstate' ),
			);
		}

		// Property exists, apply action according to settings.
		$message = '';

		switch ( $sold_action ) {
			case 'draft':
				wp_update_post(
					array(
						'ID'          => $property_post_id,
						'post_status' => 'draft',
					)
				);
				$message = __( 'Unpublished (Set to Draft)', 'connect-crm-realstate' );
				break;

			case 'trash':
				wp_trash_post( $property_post_id );
				$message = __( 'Moved to Trash', 'connect-crm-realstate' );
				break;

			case 'keep':
			default:
				$message = __( 'Kept Published (Not Available)', 'connect-crm-realstate' );
				break;
		}

		return array(
			'property_id' => $property_id,
			'post_id'     => $property_post_id,
			'message'     => $message,
		);
	}

	/**
	 * Sends to trash not synced products.
	 *
	 * @return int
	 */
	public static function trash_not_synced() {
		$settings  = get_option( 'conncrmreal_settings' );
		$post_type = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';

		$args            = array(
			'posts_per_page' => -1,
			'post_type'      => $post_type,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'property_synced',
					'compare' => 'NOT EXISTS',
				),
			),
		);
		$posts_to_delete = get_posts( $args );

		foreach ( $posts_to_delete as $post_id ) {
			wp_trash_post( $post_id );
		}

		return count( $posts_to_delete );
	}

	/**
	 * Get WordPress property data with dates and status
	 *
	 * @param string $crm_type CRM type.
	 * @return array Associative array of property_id => array(last_updated, status)
	 */
	public static function get_wordpress_property_data( $crm_type ) {
		global $wpdb;

		$settings  = get_option( 'conncrmreal_settings' );
		$meta_key  = self::get_reference_meta_key( $crm_type );
		$post_type = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';

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
				AND pm1.meta_key = %s",
				$post_type,
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
					'status'       => (bool) $row['status'],
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
	public static function get_reference_meta_key( $crm_type ) {
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

	/**
	 * Filter properties to only include those that need updating
	 *
	 * @param array  $properties List of properties from API.
	 * @param string $crm_type CRM type.
	 * @return array Filtered list of properties
	 */
	public static function filter_properties_to_update( $properties, $crm_type ) {
		// Get WordPress properties data with cache.
		$wp_properties = self::get_wordpress_property_data( $crm_type );
		$wp_ids        = array_keys( $wp_properties );

		// Filter properties.
		$filtered = array();
		foreach ( $properties as $property ) {
			$property_info = API::get_property_info( $property, $crm_type );
			$property_id   = ! empty( $property_info['id'] ) ? $property_info['id'] : $property_info['reference'];

			if ( empty( $property_id ) ) {
				continue;
			}

			// Check if property is new.
			if ( ! in_array( $property_id, $wp_ids, true ) ) {
				// Only import new properties if they are available (status = true).
				$api_status = (bool) $property_info['status'];
				if ( $api_status ) {
					$filtered[] = $property;
				}
				continue;
			}

			// Check if property is outdated (date or status changed).
			if ( isset( $wp_properties[ $property_id ] ) ) {
				$wp_data      = $wp_properties[ $property_id ];
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
