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
		$key_id             = 'inmovilla' === $crm ? 'cod_ofer' : 'id';
		$property_id        = isset( $item[ $key_id ] ) ? $item[ $key_id ] : '';
		$meta_name          = isset( $settings_fields[ $key_id ] ) ? $settings_fields[ $key_id ] : $key_id;
		$property_post_id   = self::find_property( $property_id, $post_type, $meta_name );

		if ( 'inmovilla' === $crm ) {
			$descripciones        = isset( $item['descripciones'] ) ? $item['descripciones'] : array();
			$property_title       = isset( $descripciones['titulo'] ) ? $descripciones['titulo'] : __( 'Property', 'connect-crm-realstate' );
			$property_description = isset( $descripciones['descrip'] ) ? $descripciones['descrip'] : '';
			$property_city        = isset( $item['ciudad'] ) ? $item['ciudad'] : '';
		} else {
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
			$property_id                   = wp_insert_post( $property_info );
			$message                      .= __( 'Created', 'connect-crm-realstate' );
		} else {
			$message            .= __( 'Updated', 'connect-crm-realstate' );
			$property_info['ID'] = $property_post_id;
			wp_update_post( $property_info );
		}
		$message .= self::add_end_message( $property_id, $property_title, $property_city );

		if ( ! empty( $property_id ) ) {
			update_post_meta( $property_id, 'property_synced', true );
			delete_post_meta( $property_id, 'property_description' );
			delete_post_meta( $property_id, 'property_name' );
		}

		return array(
			'property_id' => $property_id,
			'message'     => $message,
		);
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
				WHERE meta_key = '%s'",
				'property_synced'
			)
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

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => $post_type,
			'fields'         => 'ids',
			'meta_query' => array(
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
