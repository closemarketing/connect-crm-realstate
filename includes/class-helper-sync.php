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

			// Save photo URLs for properties (without downloading).
			if ( isset( $item['fotos'] ) && is_array( $item['fotos'] ) && ! empty( $item['fotos'] ) ) {
				// Save first photo as featured image URL.
				update_post_meta( $property_post_id, 'ccrmre_featured_image_url', $item['fotos'][0] );

				// Save all photos for gallery.
				update_post_meta( $property_post_id, 'ccrmre_gallery_urls', $item['fotos'] );
			}
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
}
