<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
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
 * @copyright  2019 Closemarketing
 * @version    0.1
 */
class Admin {
	/**
	 * Settings CRM
	 *
	 * @var array
	 */
	private $settings;


	/**
	 * Construct and intialize
	 *
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'plugin_settings' ) );
	}

	/**
	 * Create custom plugin settings menu
	 *
	 * @return void
	 */
	public function plugin_settings() {
		wp_register_style(
			'iip_admin-styles',
			plugin_dir_url( __FILE__ ) . 'assets/iip-styles-admin.css',
			array(),
			CCRMRE_VERSION
		);
		wp_enqueue_style( 'iip_admin-styles' );

		// Create new top-level menu.
		add_menu_page(
			__( 'Connect CRM Real State', 'connect-crm-realstate' ),
			__( 'Connect CRM Real State', 'connect-crm-realstate' ),
			'administrator',
			'iip-options',
			array( $this, 'plugin_options_page' ),
			'dashicons-rest-api'
		);

		// Call register settings function.
		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
	}

	/**
	 * Adds plugin settings page
	 *
	 * @return void
	 */
	public function plugin_options_page() {
		$this->settings = get_option( 'conncrmreal_settings' );

		// Set active class for navigation tabs.
		$active_tab = ( isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'iip-import' );

		echo '<div class="wrap bialty-containter">';
		echo '<h2><span class="dashicons dashicons-media-text" style="margin-top: 6px; font-size: 24px;"></span> ' . esc_html__( 'Connect CRM Real State Settings', 'connect-crm-realstate' ). '</h2>';
		echo '<h2 class="nav-tab-wrapper">';
		// Import Properties.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-import' ) . '" class="nav-tab ';
		echo ( 'iip-import' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Import Properties', 'connect-crm-realstate' ) . '</a>';

		// Settings Properties.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-settings' ) . '" class="nav-tab ';
		echo ( 'iip-settings' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Settings', 'connect-crm-realstate' ) . '</a>';

		// Merge variables.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-merge' ) . '" class="nav-tab ';
		echo ( 'iip-merge' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Merge variables', 'connect-crm-realstate' ) . '</a>';

		echo '</h2>';

		if ( 'iip-import' === $active_tab ) {
			$this->plugin_import_page();
		}

		if ( 'iip-settings' === $active_tab ) {
			echo '<form method="post" action="options.php">';
			settings_fields( 'admin_conncrmreal_settings' );
			do_settings_sections( 'conncrmreal_settings' );
			submit_button( esc_html__( 'Save changes', 'connect-crm-realstate' ) );
			echo '</form>';
		}

		if ( 'iip-merge' === $active_tab ) {
			$this->plugin_merge_page();
		}
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_plugin_settings() {
		$this->settings = get_option( 'conncrmreal_settings' );

		// Register our settings.
		register_setting(
			'admin_conncrmreal_settings',
			'conncrmreal_settings',
			array( $this, 'sanitize_fields_settings' )
		);
	
		add_settings_section(
			'admin_conncrmreal_settings',
			__( 'Settings for Integration with CRM Real State', 'connect-crm-realstate' ),
			array( $this, 'admin_section_settings_info' ),
			'conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_type',
			__( 'Type', 'connect-crm-realstate' ),
			array( $this, 'type_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_apipassword',
			__( 'API Password / Token', 'connect-crm-realstate' ),
			array( $this, 'apipassword_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		add_settings_field(
			'conncrmreal_post_type',
			__( 'Post Type', 'connect-crm-realstate' ),
			array( $this, 'post_type_callback' ),
			'conncrmreal_settings',
			'admin_conncrmreal_settings'
		);

		if ( isset( $this->settings['post_type'] ) && 'property' === $this->settings['post_type'] ) {
			add_settings_field(
				'conncrmreal_post_type_slug',
				__( 'Post Type SLUG', 'connect-crm-realstate' ),
				array( $this, 'post_type_slug_callback' ),
				'conncrmreal_settings',
				'admin_conncrmreal_settings'
			);
		}
	}

	/**
	 * Sanitize fiels before saves in DB
	 *
	 * @param array $input Input fields.
	 * @return array
	 */
	public function sanitize_fields_settings( $input ) {
		$sanitary_values = array();

		$field_values = array(
			'type',
			'apipassword',
			'post_type',
			'post_type_slug',
		);

		foreach ( $field_values as $field_value ) {
			if ( isset( $input[ $field_value ] ) ) {
				$sanitary_values[ $field_value ] = sanitize_text_field( $input[ $field_value ] );
			}
		}

		return $sanitary_values;
	}

	/**
	 * Show title callback
	 *
	 * @return void
	 */
	public function type_callback() {
		$type_option = isset( $this->settings['type'] ) ? $this->settings['type'] : 'show';
		?>
		<select name="conncrmreal_settings[type]" id="type">
			<option value="anaconda" <?php selected( $type_option, 'anaconda' ); ?>><?php esc_html_e( 'Anaconda', 'connect-crm-realstate' ); ?></option>
			<option value="inmovilla" <?php selected( $type_option, 'inmovilla' ); ?>><?php esc_html_e( 'Inmovilla', 'connect-crm-realstate' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Password callback
	 *
	 * @return void
	 */
	public function apipassword_callback() {
		printf(
			'<input class="regular-text" type="password" name="conncrmreal_settings[apipassword]" id="apipassword" value="%s">',
			isset( $this->settings['apipassword'] ) ? esc_attr( $this->settings['apipassword'] ) : ''
		);
	}

	/**
	 * Show title callback
	 *
	 * @return void
	 */
	public function post_type_callback() {
		$post_type_option = isset( $this->settings['post_type'] ) ? $this->settings['post_type'] : 'show';

		$args = array(
			'public'   => true,
			'_builtin' => true,
		);
		$post_types = get_post_types( $args );
		unset( $post_types['attachment'] );
		?>
		<select name="conncrmreal_settings[post_type]" id="post_type">
			<option value="property" <?php selected( $post_type_option, 'property' ); ?>><?php esc_html_e( 'Created by this plugin', 'connect-crm-realstate' ); ?></option>
			<?php
			foreach ( $post_types as $post_type ) {
				?>
				<option value="<?php echo esc_attr( $post_type ); ?>" <?php selected( $post_type_option, $post_type ); ?>><?php echo esc_html( $post_type ); ?></option>
				<?php
			}
			?>
		</select>
		<?php
	}

	/**
	 * Password callback
	 *
	 * @return void
	 */
	public function post_type_slug_callback() {
		printf(
			'<input class="regular-text" type="text" name="conncrmreal_settings[post_type_slug]" id="post_type_slug" value="%s">',
			isset( $this->settings['post_type_slug'] ) ? esc_attr( $this->settings['post_type_slug'] ) : ''
		);
		echo sprintf( 
			'<p class="description">%s</p>',
			__( 'Slug for the post type. If you change this, you need to save the permalinks again.', 'connect-crm-realstate' )
		);
	}

	/**
	 * Import Page
	 *
	 * @return void
	 */
	public function plugin_import_page() {
		?>
		<div class="connect-realstate-manual-action">
			<h2><?php esc_html_e( 'Import Properties', 'connect-crm-realstate' ); ?></h2>
			<p><?php esc_html_e( 'After you fillup the settings, use the button below to import the properties. The importing process may take a while and you need to keep this page open to complete it.', 'connect-crm-realstate' ); ?><br/></p>
			<div id="manual_import" name="manual_import" class="button button-large button-primary" onclick="syncManualProperties(this, 0);" ><?php esc_html_e( 'Start Import', 'connect-crm-realstate' ); ?></div>
			<fieldset id="logwrapper"><legend><?php esc_html_e( 'Log', 'connect-crm-realstate' ); ?></legend><div id="loglist"></div></fieldset>
		</div>
		<?php
	}

	/**
	 * Info for neo automate section.
	 *
	 * @return void
	 */
	public function admin_section_settings_info() {
		esc_html_e( 'Put the connection API key settings in order to connect external data.', 'connect-crm-realstate' );
	}

	public function plugin_merge_page() {
		$crm_type = isset( $this->settings['type'] ) ? $this->settings['type'] : 'anaconda';

		$show_merge_vars = true;
		
		if ( $show_merge_vars ) {
			$custom_fields = $this->get_all_custom_fields( $post_type );
			// Get Options .
			$properties_fields = $this->get_properties_fields();
			foreach ( $properties_fields as $property_field ) {
				$section = esc_html( $property_field['section'] );
				$property_values[ $section ] = get_option( 'iip_var_' . $section );
			}
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Merge Variables with custom values', 'connect-crm-realstate' ); ?></h1>
				<form method="post" action="options.php">
					<?php settings_fields( 'iip_plugin_merge_group' ); ?>
					<?php do_settings_sections( 'iip_plugin_merge_group' ); ?>

					<?php
					$col = 1;
					foreach ( $properties_fields as $property_field ) {
						if ( 1 === $col ) {
							echo '<div class="iip-row">';
						}
						echo '<div class="iip-column col-6">';
						echo '<table class="form-table iip-table">';
						echo '<tr valign="top">';
						echo '<th scope="row"><h3>' . esc_html( $property_field['label'] ) . '</h3></th>';
						echo '</tr>';
						foreach ( $property_field['fields'] as $key => $value ) {
							$section = esc_html( $property_field['section'] );
							echo '<th scope="row">' . $value . '</th>';
							echo '<td><select name="iip_var_' . $section;
							echo '[' . $key . ']">';
							$this->fields_to_option( $custom_fields, $property_values[ $section ][ $key ] );
							echo '</select></td>';
							echo '</tr>';
						}
						echo '</table>';
						echo '</div>';
						$col++;
						if ( $col > 2 ) {
							echo '</div>';
							$col = 1;
						}
					}
					echo '</div>';

					submit_button();
					?>
				</form>
			</div>
			<?php
		}

	}

	/**
	 * Return all meta keys from WordPress database in post type
	 *
	 * @param string $post_type Post type.
	 * @return array Array of metakeys.
	 */
	private function get_all_custom_fields( $post_type ) {
		global $wpdb, $table_prefix;
		// If not, query for it and store it for later.
		$fields    = array();
		$sql       = "SELECT DISTINCT( {$table_prefix}postmeta.meta_key )
				FROM {$table_prefix}posts
				LEFT JOIN {$table_prefix}postmeta
					ON {$table_prefix}posts.ID = {$table_prefix}postmeta.post_id
					WHERE {$table_prefix}posts.post_type = '{$post_type}'";
		$meta_keys = $wpdb->get_col( $sql );

		return $meta_keys;
	}

	/**
	 * Converts an array to option html
	 *
	 * @param array  $custom_fields Custom fields array.
	 * @param string $value Value of option selected.
	 * @return void
	 */
	private function fields_to_option( $custom_fields, $value ) {
		echo '<option value=""';
		if ( '' === $value ) {
			echo ' selected';
		}
		echo '></option>';
		foreach ( $custom_fields as $meta_key ) {
			echo '<option value="' . esc_html( $meta_key ) . '"';
			if ( ( $value === $meta_key ) || ( ! $value && 1 === $meta_key ) ) {
				echo ' selected';
			}
			echo '>' . esc_html( $meta_key ) . '</option>';
		}
	}
}

