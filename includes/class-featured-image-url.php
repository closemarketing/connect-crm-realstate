<?php
/**
 * Featured Image from URL (Fallback)
 *
 * Provides frontend-only fallback for properties that still have an external
 * image URL in ccrmre_featured_image_url but have not yet been re-synced to
 * download the image locally. Once all properties are re-synced this class
 * can be removed entirely.
 *
 * @package WordPress
 * @author  Closemarketing
 * @copyright 2025 Closemarketing
 * @version 2.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Class for Featured Image URL fallback.
 */
class Featured_Image_URL {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Frontend-only fallback: render external URL when no real attachment exists.
		add_filter( 'post_thumbnail_html', array( $this, 'fallback_thumbnail_html' ), 10, 5 );
	}

	/**
	 * Fallback: render external image URL when the post has no real featured image.
	 *
	 * Only applies to the post type configured for properties. Replaces empty
	 * thumbnail output with the image from ccrmre_featured_image_url meta.
	 *
	 * @param string       $html              Post thumbnail HTML.
	 * @param int          $post_id           Post ID.
	 * @param int          $post_thumbnail_id Post thumbnail ID.
	 * @param string|array $size              Requested image size.
	 * @param string|array $attr              Query string or array of attributes.
	 * @return string
	 */
	public function fallback_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		$settings  = get_option( 'ccrmre_settings', array() );
		$post_type = isset( $settings['post_type'] ) ? $settings['post_type'] : CCRMRE_POST_TYPE;

		if ( get_post_type( $post_id ) !== $post_type ) {
			return $html;
		}

		// If WordPress already rendered a real thumbnail, leave it alone.
		if ( ! empty( $html ) ) {
			return $html;
		}

		$url = get_post_meta( $post_id, 'ccrmre_featured_image_url', true );

		if ( empty( $url ) || ! is_string( $url ) ) {
			return $html;
		}

		// Build img attributes.
		$attr_string = '';
		if ( is_array( $attr ) ) {
			foreach ( $attr as $key => $value ) {
				$attr_string .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

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
}

new Featured_Image_URL();
