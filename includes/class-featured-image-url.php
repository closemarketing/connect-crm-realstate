<?php
/**
 * Featured Image from URL
 *
 * Handles external image URLs as featured images without downloading them.
 *
 * @package WordPress
 * @author Closemarketing
 * @copyright 2025 Closemarketing
 * @version 1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Featured Image URL
 */
class Featured_Image_URL {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Replace post thumbnail with external URL.
		add_filter( 'post_thumbnail_html', array( $this, 'replace_thumbnail_html' ), 10, 5 );
		add_filter( 'get_post_metadata', array( $this, 'get_thumbnail_id' ), 10, 4 );

		// Image size attributes.
		add_filter( 'wp_get_attachment_image_src', array( $this, 'get_image_src' ), 10, 4 );
		add_filter( 'wp_get_attachment_url', array( $this, 'get_attachment_url' ), 10, 2 );
		add_filter( 'wp_get_attachment_metadata', array( $this, 'get_attachment_metadata' ), 10, 2 );
	}

	/**
	 * Replace post thumbnail HTML with external URL
	 *
	 * @param string       $html Post thumbnail HTML.
	 * @param int          $post_id Post ID.
	 * @param int          $post_thumbnail_id Post thumbnail ID.
	 * @param string|array $size Requested image size.
	 * @param string|array $attr Query string or array of attributes.
	 * @return string
	 */
	public function replace_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		// Skip if already has thumbnail.
		if ( ! empty( $html ) && empty( get_post_meta( $post_id, 'ccrmre_featured_image_url', true ) ) ) {
			return $html;
		}

		$url = get_post_meta( $post_id, 'ccrmre_featured_image_url', true );

		if ( empty( $url ) ) {
			return $html;
		}

		// Build img attributes.
		$attr_string = '';
		if ( is_array( $attr ) ) {
			foreach ( $attr as $key => $value ) {
				$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		// Get size attributes.
		$size_class = is_string( $size ) ? $size : 'thumbnail';

		return sprintf(
			'<img src="%s" class="attachment-%s size-%s wp-post-image" alt="%s" loading="lazy"%s />',
			esc_url( $url ),
			esc_attr( $size_class ),
			esc_attr( $size_class ),
			esc_attr( get_the_title( $post_id ) ),
			$attr_string
		);
	}

	/**
	 * Get fake thumbnail ID for posts with external URLs
	 *
	 * @param mixed  $value The value to return.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $single Whether to return a single value.
	 * @return mixed
	 */
	public function get_thumbnail_id( $value, $object_id, $meta_key, $single ) {
		// Only handle thumbnail ID requests.
		if ( '_thumbnail_id' !== $meta_key ) {
			return $value;
		}

		// Check if post has external image URL.
		$url = get_post_meta( $object_id, 'ccrmre_featured_image_url', true );

		if ( empty( $url ) ) {
			return $value;
		}

		// Return a fake ID to indicate image exists.
		return $single ? 999999 : array( 999999 );
	}

	/**
	 * Get image source from URL
	 *
	 * @param array|false  $image Image data.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size Requested size.
	 * @param bool         $icon Whether to get icon.
	 * @return array|false
	 */
	public function get_image_src( $image, $attachment_id, $size = 'thumbnail', $icon = false ) {
		// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
		unset( $size, $icon );
		// phpcs:enable

		// Skip if not our fake ID.
		if ( 999999 !== $attachment_id ) {
			return $image;
		}

		// Get post ID from thumbnail meta.
		global $wpdb;
		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta 
				WHERE meta_key = %s AND meta_value = %d 
				LIMIT 1",
				'_thumbnail_id',
				$attachment_id
			)
		);

		if ( ! $post_id ) {
			return $image;
		}

		$url = get_post_meta( $post_id, 'ccrmre_featured_image_url', true );

		if ( empty( $url ) ) {
			return $image;
		}

		// Return array format: [url, width, height, is_intermediate].
		return array( $url, 800, 600, false );
	}

	/**
	 * Get attachment URL
	 *
	 * @param string $url Attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 */
	public function get_attachment_url( $url, $attachment_id ) {
		// Skip if not our fake ID.
		if ( 999999 !== $attachment_id ) {
			return $url;
		}

		// Get post ID from thumbnail meta.
		global $wpdb;
		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta 
				WHERE meta_key = %s AND meta_value = %d 
				LIMIT 1",
				'_thumbnail_id',
				$attachment_id
			)
		);

		if ( ! $post_id ) {
			return $url;
		}

		$external_url = get_post_meta( $post_id, 'ccrmre_featured_image_url', true );

		return ! empty( $external_url ) ? $external_url : $url;
	}

	/**
	 * Get attachment metadata
	 *
	 * @param array $data Metadata array.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function get_attachment_metadata( $data, $attachment_id ) {
		// Skip if not our fake ID.
		if ( 999999 !== $attachment_id ) {
			return $data;
		}

		// Return basic metadata.
		return array(
			'width'  => 800,
			'height' => 600,
			'file'   => '',
			'sizes'  => array(),
		);
	}
}

new Featured_Image_URL();
