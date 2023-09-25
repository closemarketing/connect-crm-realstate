<?php
/**
 * Library for Post type settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * Library for Page Settings
 *
 * Settings in order to sync products
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2023 Closemarketing
 * @version    0.1
 */
class PostType {
	/**
	 * Settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		$this->settings     = get_option( 'conncrmreal_settings' );
		$settings_post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';

		if ( 'property' === $settings_post_type ) {
			add_action( 'init', array( $this, 'cpt_property' ) );
			// Register Meta box for post type property.
			add_action( 'add_meta_boxes', array( $this, 'metabox_property' ) );
		}
	}

	/**
	 * Register Post Type POST Property
	 *
	 * @return void
	 **/
	public function cpt_property() {
		$settings_post_type_slug = isset( $this->settings['post_type_slug'] ) ? $this->settings['post_type_slug'] : __( 'properties', 'connect-crm-realstate' );
		$labels = array(
			'name'               => __( 'Property', 'connect-crm-realstate' ),
			'singular_name'      => __( 'Properties', 'connect-crm-realstate' ),
			'add_new'            => __( 'Add New Properties', 'connect-crm-realstate' ),
			'add_new_item'       => __( 'Add New Properties', 'connect-crm-realstate' ),
			'edit_item'          => __( 'Edit Properties', 'connect-crm-realstate' ),
			'new_item'           => __( 'New Properties', 'connect-crm-realstate' ),
			'view_item'          => __( 'View Properties', 'connect-crm-realstate' ),
			'search_items'       => __( 'Search Property', 'connect-crm-realstate' ),
			'not_found'          => __( 'Not found Property', 'connect-crm-realstate' ),
			'not_found_in_trash' => __( 'Not found Property in trash', 'connect-crm-realstate' ),
		);
		$args   = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => true, // Adds gutenberg support.
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $settings_post_type_slug,
				'with_front' => false,
			),
			'has_archive'        => false,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-admin-users', // https://developer.wordpress.org/resource/dashicons/.
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		);
		register_post_type( 'property', $args );
	}
	/**
	 * Adds metabox
	 *
	 * @return void
	 */
	public function metabox_property() {
		add_meta_box(
			'property',
			__( 'Property Meta', 'connect-crm-realstate' ),
			array( $this, 'metabox_show_property' ),
			'property',
			'normal'
		);
	}
	/**
	 * Metabox inputs for post type.
	 *
	 * @param object $post Post object.
	 * @return void
	 */
	public function metabox_show_property( $post ) {
		$meta = get_post_meta( $post->ID );
		?>
		<table>
			<?php
			foreach ( $meta as $key => $value ) {
				if ( false === strpos( $key, 'property_' ) ) {
					continue;
				}
				echo '<tr>';
				echo '<td><strong>' . esc_attr( $key ) . '</strong></td>';
				echo '<td>' . esc_attr( $value[0] ) . '</td>';
				echo '</tr>';
			}
			?>
		</table>
		<?php
	}
}
