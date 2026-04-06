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
		$this->settings     = get_option( 'ccrmre_settings' );
		$settings_post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'property';

		if ( 'property' === $settings_post_type ) {
			add_action( 'init', array( $this, 'cpt_property' ) );
		}

		add_action( 'add_meta_boxes', array( $this, 'metabox_property' ) );
		add_filter( 'manage_edit-' . $settings_post_type . '_columns', array( $this, 'add_property_columns' ) );
		add_action( 'manage_' . $settings_post_type . '_posts_custom_column', array( $this, 'manage_post_type_columns' ), 10, 2 );
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
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array(
				'slug'       => $settings_post_type_slug,
				'with_front' => false,
			),
			'has_archive'        => false,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-admin-users',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
		);
		register_post_type( CCRMRE_POST_TYPE, $args );
	}

	/**
	 * Adds metabox
	 *
	 * @return void
	 */
	public function metabox_property() {
		$post_type = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : CCRMRE_POST_TYPE;

		add_meta_box(
			'property',
			__( 'Property Meta', 'connect-crm-realstate' ),
			array( $this, 'metabox_show_property' ),
			$post_type,
			'normal'
		);

		add_meta_box(
			'property-info',
			__( 'Property Info', 'connect-crm-realstate' ),
			array( $this, 'metabox_show_property_info' ),
			$post_type,
			'side',
			'high'
		);

		add_meta_box(
			'property-photos',
			__( 'Property Photos', 'connect-crm-realstate' ),
			array( $this, 'metabox_show_photos' ),
			$post_type,
			'side',
			'high'
		);
	}

	/**
	 * Metabox inputs for post type.
	 *
	 * @param object $post Post object.
	 * @return void
	 */
	public function metabox_show_property( $post ) {
		$merge_fields = get_option( 'ccrmre_merge_fields', array() );

		if ( empty( $merge_fields ) ) {
			echo '<p>' . esc_html__( 'No merge fields configured. Please configure merge fields in the plugin settings.', 'connect-crm-realstate' ) . '</p>';
			return;
		}

		$settings = get_option( 'ccrmre_settings', array() );
		$crm_type = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';

		$api_fields   = API::get_properties_fields( $crm_type );
		$field_labels = array();

		if ( isset( $api_fields['data'] ) && is_array( $api_fields['data'] ) ) {
			foreach ( $api_fields['data'] as $field ) {
				if ( isset( $field['name'] ) && isset( $field['label'] ) ) {
					$field_labels[ $field['name'] ] = $field['label'];
				}
			}
		}

		?>
		<table class="property-meta-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'CRM Field', 'connect-crm-realstate' ); ?></th>
					<th><?php esc_html_e( 'WordPress Field', 'connect-crm-realstate' ); ?></th>
					<th><?php esc_html_e( 'Value', 'connect-crm-realstate' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $merge_fields as $crm_field => $wp_field ) {
					if ( empty( $wp_field ) ) {
						continue;
					}

					$value     = get_post_meta( $post->ID, $wp_field, true );
					$crm_label = isset( $field_labels[ $crm_field ] ) ? $field_labels[ $crm_field ] : $crm_field;

					echo '<tr>';
					echo '<td><strong>' . esc_html( $crm_label ) . '</strong><br/><small>' . esc_html( $crm_field ) . '</small></td>';
					echo '<td><code>' . esc_html( $wp_field ) . '</code></td>';
					echo '<td>' . esc_html( is_array( $value ) ? wp_json_encode( $value ) : $value ) . '</td>';
					echo '</tr>';
				}
				?>
			</tbody>
		</table>

		<?php
		// Show taxonomy mappings assigned to this post.
		$taxonomy_mappings = get_option( 'ccrmre_taxonomy_mappings', array() );
		if ( ! empty( $taxonomy_mappings ) && is_array( $taxonomy_mappings ) ) :
			?>
			<h4 style="margin: 16px 0 8px;"><?php esc_html_e( 'Taxonomy Mappings', 'connect-crm-realstate' ); ?></h4>
			<table class="property-meta-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'CRM Field', 'connect-crm-realstate' ); ?></th>
						<th><?php esc_html_e( 'Taxonomy', 'connect-crm-realstate' ); ?></th>
						<th><?php esc_html_e( 'Assigned Terms', 'connect-crm-realstate' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $taxonomy_mappings as $mapping ) : ?>
						<?php
						$crm_field = isset( $mapping['crm_field'] ) ? $mapping['crm_field'] : '';
						$taxonomy  = isset( $mapping['taxonomy'] ) ? $mapping['taxonomy'] : '';

						if ( empty( $crm_field ) || empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
							continue;
						}

						$tax_object = get_taxonomy( $taxonomy );
						$tax_label  = $tax_object ? $tax_object->labels->name : $taxonomy;
						$crm_label  = isset( $field_labels[ $crm_field ] ) ? $field_labels[ $crm_field ] : $crm_field;

						$terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'names' ) );
						?>
						<tr>
							<td><strong><?php echo esc_html( $crm_label ); ?></strong><br/><small><?php echo esc_html( $crm_field ); ?></small></td>
							<td><?php echo esc_html( $tax_label ); ?><br/><small><?php echo esc_html( $taxonomy ); ?></small></td>
							<td>
								<?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
									<?php echo esc_html( implode( ', ', $terms ) ); ?>
								<?php else : ?>
									<em style="color:#999;"><?php esc_html_e( 'None', 'connect-crm-realstate' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Metabox for property identification info in sidebar.
	 *
	 * @param object $post Post object.
	 * @return void
	 */
	public function metabox_show_property_info( $post ) {
		$property_id  = get_post_meta( $post->ID, 'ccrmre_property_id', true );
		$reference    = get_post_meta( $post->ID, 'ccrmre_reference', true );
		$status       = get_post_meta( $post->ID, 'ccrmre_status', true );
		$last_updated = get_post_meta( $post->ID, 'ccrmre_last_updated', true );
		?>
		<table class="ccrmre-property-info-table">
			<tr>
				<th><?php esc_html_e( 'ID', 'connect-crm-realstate' ); ?></th>
				<td>
					<?php if ( ! empty( $property_id ) ) : ?>
						<code><?php echo esc_html( $property_id ); ?></code>
					<?php else : ?>
						<em><?php esc_html_e( 'Not synced', 'connect-crm-realstate' ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Reference', 'connect-crm-realstate' ); ?></th>
				<td>
					<?php if ( '' !== $reference ) : ?>
						<code><?php echo esc_html( $reference ); ?></code>
					<?php else : ?>
						<em><?php esc_html_e( 'Not synced', 'connect-crm-realstate' ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Status', 'connect-crm-realstate' ); ?></th>
				<td>
					<?php
					if ( '' !== $status && null !== $status ) {
						$is_active = ! empty( $status ) && '0' !== $status && false !== $status;
						echo $is_active
							? esc_html__( 'Available', 'connect-crm-realstate' )
							: esc_html__( 'Unavailable', 'connect-crm-realstate' );
					} else {
						echo '<em>' . esc_html__( 'Not synced', 'connect-crm-realstate' ) . '</em>';
					}
					?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Last updated', 'connect-crm-realstate' ); ?></th>
				<td>
					<?php if ( ! empty( $last_updated ) ) : ?>
						<?php echo esc_html( $last_updated ); ?>
					<?php else : ?>
						<em><?php esc_html_e( 'Not synced', 'connect-crm-realstate' ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Metabox for property photos in sidebar.
	 *
	 * @param object $post Post object.
	 * @return void
	 */
	public function metabox_show_photos( $post ) {
		$featured_image_url = get_post_meta( $post->ID, 'ccrmre_featured_image_url', true );
		$gallery_urls       = get_post_meta( $post->ID, 'ccrmre_gallery_urls', true );

		if ( empty( $featured_image_url ) && ( empty( $gallery_urls ) || ! is_array( $gallery_urls ) ) ) {
			?>
			<div class="ccrmre-no-photos">
				<p><?php esc_html_e( 'No photos available from CRM', 'connect-crm-realstate' ); ?></p>
			</div>
			<?php
			return;
		}

		if ( ! empty( $featured_image_url ) ) {
			?>
			<div class="ccrmre-sidebar-featured">
				<strong><?php esc_html_e( 'Featured Image', 'connect-crm-realstate' ); ?></strong>
				<img src="<?php echo esc_url( $featured_image_url ); ?>" alt="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>" />
				<p><?php echo esc_url( $featured_image_url ); ?></p>
			</div>
			<?php
		}

		if ( ! empty( $gallery_urls ) && is_array( $gallery_urls ) && count( $gallery_urls ) > 1 ) {
			?>
			<div class="ccrmre-sidebar-gallery">
				<h4>
					<?php
					/* translators: %d: number of photos */
					echo esc_html( sprintf( __( 'Gallery (%d photos)', 'connect-crm-realstate' ), count( $gallery_urls ) ) );
					?>
				</h4>
				<div class="ccrmre-sidebar-gallery-grid">
					<?php foreach ( $gallery_urls as $photo_url ) : ?>
						<img src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( get_the_title( $post->ID ) ); ?>" title="<?php echo esc_attr( $photo_url ); ?>" />
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Adds columns to post type
	 *
	 * @param array $post_type_columns Header of admin post type list.
	 * @return array $new_columns New elements for header.
	 */
	public function add_property_columns( $post_type_columns ) {
		unset( $post_type_columns );

		$new_columns['cb']            = '<input type="checkbox" />';
		$new_columns['title']         = __( 'Title', 'connect-crm-realstate' );
		$new_columns['property_data'] = __( 'Property', 'connect-crm-realstate' );

		if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo-premium/wp-seo-premium.php' ) ) {
			$new_columns['wpseo-score']             = __( 'SEO', 'connect-crm-realstate' );
			$new_columns['wpseo-score-readability'] = __( 'Readability', 'connect-crm-realstate' );
			$new_columns['wpseo-title']             = __( 'SEO Title', 'connect-crm-realstate' );
			$new_columns['wpseo-metadesc']          = __( 'Meta Desc.', 'connect-crm-realstate' );
			$new_columns['wpseo-focuskw']           = __( 'Focus KW', 'connect-crm-realstate' );
		}

		if ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
			$new_columns['rank_math_seo_details'] = __( 'SEO Details', 'connect-crm-realstate' );
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
		if ( 'property_data' === $column_name ) {
			$this->render_property_data_column( $id );
		}
	}

	/**
	 * Render property data column
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_property_data_column( $post_id ) {
		$settings = get_option( 'ccrmre_settings' );
		$crm_type = isset( $settings['type'] ) ? $settings['type'] : 'anaconda';

		$property_id = $this->get_property_meta_value( $post_id, 'id', $crm_type );
		if ( ! empty( $property_id ) ) {
			$this->render_meta_field( __( 'Property ID', 'connect-crm-realstate' ), $property_id );
		}

		if ( 'anaconda' === $crm_type ) {
			$internal_id = $this->get_property_meta_value( $post_id, 'internal_property_id', $crm_type );
			if ( ! empty( $internal_id ) ) {
				$this->render_meta_field( __( 'Property Internal ID', 'connect-crm-realstate' ), $internal_id );
			}
		}

		$enabled = $this->get_property_meta_value( $post_id, 'enabled', $crm_type );
		$status  = ! empty( $enabled ) ? __( 'Available', 'connect-crm-realstate' ) : __( 'Sold', 'connect-crm-realstate' );
		$this->render_meta_field( __( 'Status', 'connect-crm-realstate' ), $status );

		$agent = $this->get_property_meta_value( $post_id, 'agent', $crm_type );
		if ( ! empty( $agent ) ) {
			$this->render_meta_field( __( 'Agent', 'connect-crm-realstate' ), $agent );
		}
	}

	/**
	 * Get property meta value using merge fields mapping
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $crm_field CRM field name.
	 * @param string $crm_type  CRM type.
	 * @return mixed Meta value or empty string.
	 */
	private function get_property_meta_value( $post_id, $crm_field, $crm_type = 'anaconda' ) {
		$merge_fields = get_option( 'ccrmre_merge_fields', array() );

		$field_map = array(
			'anaconda'  => array(
				'id'                   => 'id',
				'internal_property_id' => 'internal_property_id',
				'enabled'              => 'enabled',
				'agent'                => 'agent',
			),
			'inmovilla' => array(
				'id'      => 'cod_ofer',
				'enabled' => 'nodisponible',
				'agent'   => 'captadopor',
			),
		);

		$crm_field_name = isset( $field_map[ $crm_type ][ $crm_field ] ) ? $field_map[ $crm_type ][ $crm_field ] : $crm_field;

		if ( isset( $merge_fields[ $crm_field_name ] ) ) {
			$meta_key = $merge_fields[ $crm_field_name ];
		} else {
			$meta_key = 'property_' . $crm_field_name;
		}

		return get_post_meta( $post_id, $meta_key, true );
	}

	/**
	 * Render a meta field row
	 *
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return void
	 */
	private function render_meta_field( $label, $value ) {
		echo '<p><strong>' . esc_html( $label ) . '</strong>: ';
		echo esc_html( $value );
		echo '</p>';
	}
}
