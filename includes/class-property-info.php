<?php
/**
 * Library for Property Information Box
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Property Information Box Class
 *
 * Handles property information display with icons and shortcode
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */
class PropertyInfo {
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
		add_shortcode( 'ccrmre_property_info', array( $this, 'shortcode_property_info' ) );
		add_shortcode( 'property_info', array( $this, 'shortcode_property_info' ) ); // Backward compatibility.

		// Auto display property info if enabled.
		// Priority 30 ensures it appears after gallery (priority 20).
		if ( isset( $this->settings['show_property_info'] ) && 'yes' === $this->settings['show_property_info'] ) {
			add_filter( 'the_content', array( $this, 'auto_display_property_info' ), 30 );
		}

		// Enqueue property info assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_property_info_assets' ) );
	}

	/**
	 * Enqueue property info assets
	 *
	 * @return void
	 */
	public function enqueue_property_info_assets() {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		if ( ! is_singular( $post_type ) ) {
			return;
		}

		wp_enqueue_style(
			'ccrmre-property-info',
			plugin_dir_url( __FILE__ ) . 'assets/property-info.css',
			array(),
			CCRMRE_VERSION
		);
	}

	/**
	 * Auto display property info after content
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function auto_display_property_info( $content ) {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';
		if ( ! is_singular( $post_type ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$info_html = $this->render_property_info();

		if ( empty( $info_html ) ) {
			return $content;
		}

		return $content . $info_html;
	}

	/**
	 * Shortcode for property info
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_property_info( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'property_info'
		);

		return $this->render_property_info( $atts['post_id'] );
	}

	/**
	 * Render property info HTML
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function render_property_info( $post_id = null ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return '';
		}

		// Get merge fields configuration.
		$merge_fields = get_option( 'ccrmre_merge_fields', array() );

		if ( empty( $merge_fields ) ) {
			return '';
		}

		// Get all property meta data.
		$property_data = array();
		foreach ( $merge_fields as $crm_field => $wp_field ) {
			if ( ! empty( $wp_field ) ) {
				$value = get_post_meta( $post_id, $wp_field, true );
				if ( ! empty( $value ) ) {
					$property_data[ $crm_field ] = $value;
				}
			}
		}

		if ( empty( $property_data ) ) {
			return '';
		}

		// Map common field names to display info.
		$info_fields = $this->map_property_fields( $property_data );

		ob_start();
		?>
		<div class="ccrmre-property-info-box">
			<?php if ( ! empty( $info_fields['price'] ) ) : ?>
				<div class="ccrmre-info-price">
					<span class="ccrmre-price-label"><?php esc_html_e( 'Price', 'connect-crm-realstate' ); ?></span>
					<span class="ccrmre-price-value"><?php echo esc_html( $info_fields['price'] ); ?></span>
				</div>
			<?php endif; ?>

			<div class="ccrmre-info-grid">
				<?php if ( ! empty( $info_fields['bedrooms'] ) ) : ?>
					<div class="ccrmre-info-item">
						<span class="ccrmre-icon ccrmre-icon-bed">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
								<path d="M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z"/>
							</svg>
						</span>
						<div class="ccrmre-info-content">
							<span class="ccrmre-info-value"><?php echo esc_html( $info_fields['bedrooms'] ); ?></span>
							<span class="ccrmre-info-label"><?php esc_html_e( 'Bedrooms', 'connect-crm-realstate' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $info_fields['bathrooms'] ) ) : ?>
					<div class="ccrmre-info-item">
						<span class="ccrmre-icon ccrmre-icon-bath">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
								<path d="M20 2H4c-1.11 0-2 .89-2 2v16c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V4c0-1.11-.89-2-2-2zM7 3c1.1 0 2 .89 2 2 0 1.1-.9 2-2 2s-2-.9-2-2c0-1.11.9-2 2-2zm13 15H4v-2h16v2zm0-5H4V6h16v7z"/>
							</svg>
						</span>
						<div class="ccrmre-info-content">
							<span class="ccrmre-info-value"><?php echo esc_html( $info_fields['bathrooms'] ); ?></span>
							<span class="ccrmre-info-label"><?php esc_html_e( 'Bathrooms', 'connect-crm-realstate' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $info_fields['area'] ) ) : ?>
					<div class="ccrmre-info-item">
						<span class="ccrmre-icon ccrmre-icon-area">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
								<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H5V5h9v12zm5-12v12h-3V5h3z"/>
							</svg>
						</span>
						<div class="ccrmre-info-content">
							<span class="ccrmre-info-value"><?php echo esc_html( $info_fields['area'] ); ?> m²</span>
							<span class="ccrmre-info-label"><?php esc_html_e( 'Area', 'connect-crm-realstate' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $info_fields['type'] ) ) : ?>
					<div class="ccrmre-info-item">
						<span class="ccrmre-icon ccrmre-icon-type">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
								<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
							</svg>
						</span>
						<div class="ccrmre-info-content">
							<span class="ccrmre-info-value"><?php echo esc_html( $info_fields['type'] ); ?></span>
							<span class="ccrmre-info-label"><?php esc_html_e( 'Type', 'connect-crm-realstate' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $info_fields['location'] ) ) : ?>
					<div class="ccrmre-info-item">
						<span class="ccrmre-icon ccrmre-icon-location">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
								<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
							</svg>
						</span>
						<div class="ccrmre-info-content">
							<span class="ccrmre-info-value"><?php echo esc_html( $info_fields['location'] ); ?></span>
							<span class="ccrmre-info-label"><?php esc_html_e( 'Location', 'connect-crm-realstate' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $info_fields['reference'] ) ) : ?>
					<div class="ccrmre-info-item">
						<span class="ccrmre-icon ccrmre-icon-ref">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
								<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
							</svg>
						</span>
						<div class="ccrmre-info-content">
							<span class="ccrmre-info-value"><?php echo esc_html( $info_fields['reference'] ); ?></span>
							<span class="ccrmre-info-label"><?php esc_html_e( 'Reference', 'connect-crm-realstate' ); ?></span>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Map property fields to display format
	 *
	 * @param array $property_data Property meta data.
	 * @return array
	 */
	private function map_property_fields( $property_data ) {
		$mapped = array();

		// Price - multiple possible field names.
		$price_fields = array( 'precio', 'price', 'pvp', 'precio_venta' );
		foreach ( $price_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$price = $property_data[ $field ];
				if ( is_numeric( $price ) ) {
					$mapped['price'] = number_format( $price, 0, ',', '.' ) . ' €';
				} else {
					$mapped['price'] = $price;
				}
				break;
			}
		}

		// Bedrooms.
		$bedroom_fields = array( 'dormitorios', 'bedrooms', 'habitaciones', 'dorm' );
		foreach ( $bedroom_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$mapped['bedrooms'] = $property_data[ $field ];
				break;
			}
		}

		// Bathrooms.
		$bathroom_fields = array( 'banos', 'bathrooms', 'aseos', 'wc' );
		foreach ( $bathroom_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$mapped['bathrooms'] = $property_data[ $field ];
				break;
			}
		}

		// Area.
		$area_fields = array( 'superficie', 'area', 'm2', 'metros', 'superficie_construida' );
		foreach ( $area_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$mapped['area'] = $property_data[ $field ];
				break;
			}
		}

		// Type.
		$type_fields = array( 'tipo', 'type', 'tipologia', 'tipo_inmueble' );
		foreach ( $type_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$mapped['type'] = $property_data[ $field ];
				break;
			}
		}

		// Location.
		$location_fields = array( 'ciudad', 'location', 'localidad', 'poblacion', 'municipio' );
		foreach ( $location_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$mapped['location'] = $property_data[ $field ];
				break;
			}
		}

		// Reference.
		$reference_fields = array( 'referencia', 'reference', 'ref', 'codigo' );
		foreach ( $reference_fields as $field ) {
			if ( isset( $property_data[ $field ] ) ) {
				$mapped['reference'] = $property_data[ $field ];
				break;
			}
		}

		return $mapped;
	}
}

new PropertyInfo();
