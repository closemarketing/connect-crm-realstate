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
	 * @return array
	 */
	public static function sync_property( $item ) {
		$message        = '';
		$settings       = get_option( 'conncrmreal_settings' );
		$post_type      = isset( $settings['post_type'] ) ? $settings['post_type'] : 'property';
		$property_id    = self::find_property( $item['id'], $post_type );
		$property_title = isset( $item['name'] ) ? $item['name'] : __( 'Property', 'connect-crm-realstate' );

		// Property info.
		$property_info = array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
		);

		// Meta Info.
		$property_info['meta_input'] = array();

		foreach ( $item as $key => $item_meta ) {
			$property_info['meta_input'][ 'property_' . $key ] = $item_meta;
		}

		if ( empty( $property_id ) ) {
			$property_info['post_title']   = $property_title;
			$property_info['post_name']    = sanitize_title( $property_title );
			$property_info['post_content'] = isset( $item['description'] ) ? $item['description'] : '';
			$property_id                   = wp_insert_post( $property_info );
			$message                      .= 'CREA ' . $property_id;
		} else {
			$message            .= 'ACT ' . $property_id;
			$property_info['ID'] = $property_id;
			wp_update_post( $property_info );
		}

		return array(
			'property_id' => $property_id,
			'message'     => $message,
		);

	}

	/**
	 * Finds property by property_id.
	 *
	 * @param string $property_id Property ID.
	 * @param string $post_type Post type.
	 * @return int
	 */
	public static function find_property( $property_id, $post_type ) {
		$property = get_posts(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => 'property_id',
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
}
