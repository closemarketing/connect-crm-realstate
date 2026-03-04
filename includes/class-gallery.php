<?php
/**
 * Library for Property Gallery
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Property Gallery Class
 *
 * Handles property photo gallery display and shortcode
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */
class Gallery {
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'ccrmre_settings' );

		// Register shortcode (prefixed for Plugin Directory guidelines).
		add_shortcode( 'ccrmre_property_gallery', array( $this, 'shortcode_gallery' ) );
		add_shortcode( 'property_gallery', array( $this, 'shortcode_gallery' ) ); // Backward compatibility.

		// Auto display gallery if enabled.
		if ( isset( $this->settings['show_gallery'] ) && 'yes' === $this->settings['show_gallery'] ) {
			add_filter( 'the_content', array( $this, 'auto_display_gallery' ), 20 );
		}

		// Enqueue gallery assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_gallery_assets' ) );
	}

	/**
	 * Enqueue gallery assets
	 *
	 * @return void
	 */
	public function enqueue_gallery_assets() {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		if ( ! is_singular( $post_type ) ) {
			return;
		}

		wp_enqueue_style(
			'ccrmre-gallery',
			plugin_dir_url( __FILE__ ) . 'assets/gallery.css',
			array(),
			CCRMRE_VERSION
		);

		wp_enqueue_script(
			'ccrmre-gallery',
			plugin_dir_url( __FILE__ ) . 'assets/gallery.js',
			array(),
			CCRMRE_VERSION,
			true
		);
	}

	/**
	 * Auto display gallery after title
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function auto_display_gallery( $content ) {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		if ( ! is_singular( $post_type ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$gallery_html = $this->render_gallery();

		if ( empty( $gallery_html ) ) {
			return $content;
		}

		return $gallery_html . $content;
	}

	/**
	 * Shortcode for gallery
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_gallery( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'property_gallery'
		);

		return $this->render_gallery( $atts['post_id'] );
	}

	/**
	 * Render gallery HTML
	 *
	 * Uses local attachment IDs when available, falls back to external URLs.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function render_gallery( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return '';
		}

		$photo_urls = $this->get_gallery_image_urls( $post_id );

		if ( empty( $photo_urls ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="ccrmre-property-gallery">
			<div class="ccrmre-gallery-main">
				<button class="ccrmre-gallery-prev" aria-label="<?php esc_attr_e( 'Previous photo', 'connect-crm-real-state' ); ?>">
					<span>&lsaquo;</span>
				</button>
				<div class="ccrmre-gallery-slider">
					<?php foreach ( $photo_urls as $index => $photo_url ) : ?>
						<div class="ccrmre-gallery-slide <?php echo 0 === $index ? 'active' : ''; ?>">
							<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) . ' - ' . ( $index + 1 ) ); ?>" loading="lazy" />
						</div>
					<?php endforeach; ?>
				</div>
				<button class="ccrmre-gallery-next" aria-label="<?php esc_attr_e( 'Next photo', 'connect-crm-real-state' ); ?>">
					<span>&rsaquo;</span>
				</button>
				<div class="ccrmre-gallery-counter">
					<span class="ccrmre-gallery-current">1</span> / <span class="ccrmre-gallery-total"><?php echo count( $photo_urls ); ?></span>
				</div>
			</div>
			<div class="ccrmre-gallery-thumbnails">
				<?php foreach ( $photo_urls as $index => $photo_url ) : ?>
					<div class="ccrmre-gallery-thumb <?php echo 0 === $index ? 'active' : ''; ?>" data-index="<?php echo esc_attr( $index ); ?>">
						<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) . ' - ' . ( $index + 1 ) ); ?>" loading="lazy" />
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gets the gallery image URLs, preferring local attachments over external URLs.
	 *
	 * @param int $post_id Post ID.
	 * @return array Array of image URLs.
	 */
	private function get_gallery_image_urls( $post_id ) {
		$gallery_urls   = get_post_meta( $post_id, 'ccrmre_gallery_urls', true );
		$attachment_ids = get_post_meta( $post_id, 'ccrmre_gallery_attachment_ids', true );
		$gallery_urls   = is_array( $gallery_urls ) ? $gallery_urls : array();
		$attachment_ids = is_array( $attachment_ids ) ? $attachment_ids : array();

		if ( empty( $gallery_urls ) && empty( $attachment_ids ) ) {
			return array();
		}

		$photo_urls = array();
		$count      = max( count( $gallery_urls ), count( $attachment_ids ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$local_url = '';

			// Try to get local URL from attachment ID.
			if ( isset( $attachment_ids[ $i ] ) && ! empty( $attachment_ids[ $i ] ) ) {
				$local_url = wp_get_attachment_url( (int) $attachment_ids[ $i ] );
			}

			if ( ! empty( $local_url ) ) {
				$photo_urls[] = $local_url;
			} elseif ( isset( $gallery_urls[ $i ] ) && ! empty( $gallery_urls[ $i ] ) ) {
				// Fallback to external URL.
				$photo_urls[] = $gallery_urls[ $i ];
			}
		}

		return $photo_urls;
	}
}

new Gallery();
