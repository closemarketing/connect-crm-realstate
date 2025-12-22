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

// Prevents fatal error is_plugin_active.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

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
		// Check license before initializing.
		if ( ! function_exists( 'cccrmre_is_license_active' ) || ! cccrmre_is_license_active() ) {
			return;
		}

		$this->settings     = get_option( 'conncrmreal_settings' );
		$settings_post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';

		if ( 'property' === $settings_post_type ) {
			add_action( 'init', array( $this, 'cpt_property' ) );
			// Register Meta box for post type property.
			add_action( 'add_meta_boxes', array( $this, 'metabox_property' ) );

			add_filter( 'manage_edit-' . $settings_post_type . '_columns', array( $this, 'add_property_columns' ) );
			add_action( 'manage_' . $settings_post_type . '_posts_custom_column', array( $this, 'manage_post_type_columns' ), 10, 2 );

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
	/**
	 * Adds columns to post type post_type
	 *
	 * @param array $post_type_columns  Header of admin post type list.
	 * @return array $post_type_columns New elements for header.
	 */
	public function add_property_columns( $post_type_columns ) {
		$new_columns['cb']            = '<input type="checkbox" />';
		$new_columns['title']         = __( 'Title', 'connect-crm-realstate' );
		$new_columns['property_data'] = __( 'Property', 'connect-crm-realstate' );

		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
			// Optional for Yoast SEO.
			$new_columns['wpseo-score']             = __( 'SEO', 'wordpress-seo' );
			$new_columns['wpseo-score-readability'] = __( 'Readability', 'wordpress-seo' );
			$new_columns['wpseo-title']             = __( 'SEO Title', 'wordpress-seo' );
			$new_columns['wpseo-metadesc']          = __( 'Meta Desc.', 'wordpress-seo' );
			$new_columns['wpseo-focuskw']           = __( 'Focus KW', 'wordpress-seo' );
		}

		if ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
			// Optional for RankMath SEO.
			$new_columns['rank_math_seo_details']           = __( 'SEO Details', 'rankmath' );
		}

		return $new_columns;
	}

	/**
	 * Add columns content
	 *
	 * @param array $column_name Column name of actual.
	 * @param array $id Post ID.
	 * @return void
	 */
	public function manage_post_type_columns( $column_name, $id ) {
		switch ( $column_name ) {
			case 'property_data':
				// Code.
				$property_id = get_post_meta( $id, 'property_id', true );
				echo '<p><strong>' . esc_html__( 'Property ID', 'connect-crm-realstate' ) . '</strong>: ';
				echo esc_html( $property_id );
				echo '</p>';
				// Code.
				$property_internal_id = get_post_meta( $id, 'property_internal_property_id', true );
				echo '<p><strong>' . esc_html__( 'Property Internal ID', 'connect-crm-realstate' ) . '</strong>: ';
				echo esc_html( $property_internal_id );
				echo '</p>';
				// Status.
				$property_enabled = get_post_meta( $id, 'property_enabled', true );
				echo '<p><strong>' . esc_html__( 'Status', 'connect-crm-realstate' ) . '</strong>: ';
				echo ! empty( $property_enabled ) ? esc_html__( 'Available', 'connect-crm-realstate' ) : esc_html__( 'Sold', 'connect-crm-realstate' );
				echo '</p>';
				// Agent.
				$property_agent = get_post_meta( $id, 'property_agent', true );
				echo '<p><strong>' . esc_html__( 'Agent', 'connect-crm-realstate' ) . '</strong>: ';
				echo esc_html( $property_agent );
				echo '</p>';
				break;

			default:
				break;
		} // end switch
	}
}

