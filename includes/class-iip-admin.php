<?php
/**
 * Library for admin settings
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2019 Closemarketing
 * @version    1.0
 */

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
class IIP_Admin {

	/**
	 * The plugin file
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Construct and intialize
	 *
	 * @param string $file File of this class.
	 */
	public function __construct( $file ) {
		$this->file = $file;

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
			IIP_VERSION
		);
		wp_enqueue_style( 'iip_admin-styles' );

		// Create new top-level menu.
		add_menu_page( 'Import Inmovilla', 'Import Inmovilla', 'administrator', 'iip-options', array( $this, 'plugin_options_page' ), 'dashicons-rest-api' );

		// Call register settings function.
		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_plugin_settings() {
		// Register our settings.
		register_setting( 'iip_plugin_settings_group', 'iip_agency_number' );
		register_setting( 'iip_plugin_settings_group', 'iip_agency_pass' );
		register_setting( 'iip_plugin_settings_group', 'iip_post_type' );

		// Register Merge Settings.
		$properties_fields = $this->get_properties_fields();

		foreach ( $properties_fields as $property_field ) {
			register_setting( 'iip_plugin_merge_group', 'iip_var_' . esc_html( $property_field['section'] ) );
		}
	}

	/**
	 * Adds plugin settings page
	 *
	 * @return void
	 */
	public function plugin_options_page() {
		// Set active class for navigation tabs.
		$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'iip-settings' );

		echo '<div class="wrap bialty-containter">';
		echo '<h2><span class="dashicons dashicons-media-text" style="margin-top: 6px; font-size: 24px;"></span>' . esc_html__( 'Import Inmovilla Properties Settings', 'import-inmovilla-properties' ). '</h2>';
		echo '<h2 class="nav-tab-wrapper">';
		// Import Properties.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-import' ) . '" class="nav-tab ';
		echo  ( 'iip-import' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Import Properties', 'import-inmovilla-properties' ) . '</a>';

		// Settings Properties.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-settings' ) . '" class="nav-tab ';
		echo  ( 'iip-settings' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Settings', 'import-inmovilla-properties' ) . '</a>';

		// Merge variables.
		echo '<a href="' . esc_url( '?page=iip-options&tab=iip-merge' ) . '" class="nav-tab ';
		echo  ( 'iip-merge' === $active_tab ? 'nav-tab-active' : '' );
		echo '">' . esc_html__( 'Merge variables', 'import-inmovilla-properties' ) . '</a>';

		echo '</h2>';

		if ( 'iip-import' === $active_tab ) {

		}

		if ( 'iip-settings' === $active_tab ) {
			$this->plugin_settings_page();
		}

		if ( 'iip-merge' === $active_tab ) {
			$this->plugin_merge_page();
		}
	}
	/**
	 * Settings and Merge variables page
	 *
	 * @return void
	 */
	public function plugin_settings_page() {
		$agency_number = get_option( 'iip_agency_number' );
		$agency_pass   = get_option( 'iip_agency_pass' );
		$language      = get_option( 'iip_language' );
		$post_type     = get_option( 'iip_post_type' );
		if ( $agency_number && $agency_pass ) {
			$show_merge_vars = true;
		} else {
			$show_merge_vars = false;
		}
		// Select Custom post types.
		$select_cpt_options = '<option value=""';
		if ( ! $post_type ) {
			$select_cpt_options .= ' selected';
		}
		$select_cpt_options .= '></option>';
		$args = array(
			'public'   => true,
			'_builtin' => false,
		);
		$list_post_types = get_post_types( $args, 'objects', 'and' );
		foreach ( $list_post_types as $list_post_type ) {
			$select_cpt_options .= '<option value="' . $list_post_type->name . '"';
			if ( $post_type === $list_post_type->name ) {
				$select_cpt_options .= ' selected';
			}
			$select_cpt_options .= '>' . $list_post_type->label . '</option>';
		}
		// Language.
		$select_lang = '<option value=""';
		if ( ! $language ) {
			$select_lang .= ' selected';
		}
		$select_lang .= '></option>';
		$inmovilla_langs = array(
			1  => __( 'Spanish', 'import-inmovilla-properties' ),
			2  => __( 'English', 'import-inmovilla-properties' ),
			3  => __( 'German', 'import-inmovilla-properties' ),
			4  => __( 'French', 'import-inmovilla-properties' ),
			5  => __( 'Dutch', 'import-inmovilla-properties' ),
			6  => __( 'Norweigian', 'import-inmovilla-properties' ),
			7  => __( 'Russian', 'import-inmovilla-properties' ),
			8  => __( 'Portuguese', 'import-inmovilla-properties' ),
			9  => __( 'Swedish', 'import-inmovilla-properties' ),
			10 => __( 'Finnish', 'import-inmovilla-properties' ),
			11 => __( 'Chinese', 'import-inmovilla-properties' ),
			12 => __( 'Catalan', 'import-inmovilla-properties' ),
			15 => __( 'Italian', 'import-inmovilla-properties' ),
			16 => __( 'Basque', 'import-inmovilla-properties' ),
			17 => __( 'Polish', 'import-inmovilla-properties' ),
			18 => __( 'Galician', 'import-inmovilla-properties' ),
		);
		foreach ( $inmovilla_langs as $key => $value ) {
			$select_lang .= '<option value="' . $key . '"';
			if ( ( $language === $key) || ( ! $language && 1 === $key ) ) {
				$select_lang .= ' selected';
			}
			$select_lang .= '>' . $value . '</option>';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Connection Settings', 'import-inmovilla-properties' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'iip_plugin_settings_group' ); ?>
				<?php do_settings_sections( 'iip_plugin_settings_group' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Agency number', 'import-inmovilla-properties'); ?></th>
						<td><input type="text" name="iip_agency_number" value="<?php echo esc_attr( $agency_number ); ?>" /></td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Agency API Password', 'import-inmovilla-properties' ); ?></th>
						<td><input type="password" name="iip_agency_pass" value="<?php echo esc_attr( $agency_pass ); ?>" /></td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Language', 'import-inmovilla-properties' ); ?></th>
						<td><select name="iip_language"><?php echo $select_lang; ?></select></td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Custom Post Type to import', 'import-inmovilla-properties' ); ?></th>
						<td><select name="iip_post_type"><?php echo $select_cpt_options; ?></select></td>
					</tr>

				</table>
				<?php submit_button(); ?>

				</div>

			</form>
		</div>
		<?php
	}

	public function plugin_merge_page() {
		
		$agency_number = get_option( 'iip_agency_number' );
		$agency_pass   = get_option( 'iip_agency_pass' );
		$language      = get_option( 'iip_language' );
		$post_type     = get_option( 'iip_post_type' );
		if ( $agency_number && $agency_pass ) {
			$show_merge_vars = true;
		} else {
			$show_merge_vars = false;
		}
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
				<h1><?php esc_html_e( 'Merge Variables with custom values', 'import-inmovilla-properties'); ?></h1>
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

					submit_button();
					?>
				</form>
			</div>
			<?php
		}

	}

	/**
	 * Properties fields array from Inmovilla
	 *
	 * @return array Property Fields.
	 */
	private function get_properties_fields() {
		$properties_fields = [
			[
				'section' => 'features',
				'label'   => __( 'Features', 'import-inmovilla-properties' ),
				'fields'  => [
					'cod_ofer'     => __( 'Reference', 'import-inmovilla-properties' ),
					'keyacci'      => __( 'Operation Type', 'import-inmovilla-properties' ),
					'key_tipo'     => __( 'Property Type', 'import-inmovilla-properties' ),
					'key_loca'     => __( 'City', 'import-inmovilla-properties' ),
					'key_zona'     => __( 'Zone', 'import-inmovilla-properties' ),
					'zonaauxiliar' => __( 'Complementary Zone', 'import-inmovilla-properties' ),
					'keycalle'     => __( 'Street key', 'import-inmovilla-properties' ),
					'calle'        => __( 'Street', 'import-inmovilla-properties' ),
					'numero'       => __( 'Street number', 'import-inmovilla-properties' ),
					'cp'           => __( 'ZIP', 'import-inmovilla-properties' ),
					'altura'       => __( 'Height', 'import-inmovilla-properties' ),
					'planta'       => __( 'Block', 'import-inmovilla-properties' ),
					'planta'       => __( 'Floor', 'import-inmovilla-properties' ),
					'puerta'       => __( 'Door', 'import-inmovilla-properties' ),
					'escalera'     => __( 'Stairs', 'import-inmovilla-properties' ),
					'bloque'       => __( 'Block', 'import-inmovilla-properties' ),
					'edificio'     => __( 'Building', 'import-inmovilla-properties' ),
					'fecha'        => __( 'Date creation', 'import-inmovilla-properties' ),
					'fechaact'     => __( 'Date updated', 'import-inmovilla-properties' ),
				],
			],
			[
				'section' => 'surfaces',
				'label'   => __( 'Surfaces', 'import-inmovilla-properties' ),
				'fields'  => [
					'm_uties'      => __( 'Useful square meters', 'import-inmovilla-properties' ),
					'm_cons'       => __( 'Square Meters built', 'import-inmovilla-properties' ),
					'm_parcela'    => __( 'Square Meters plot', 'import-inmovilla-properties' ),
					'm_terraza'    => __( 'Square Meters terrace', 'import-inmovilla-properties' ),
					'm_cocina'     => __( 'Square Meters kitchen', 'import-inmovilla-properties' ),
					'm_comedor'    => __( 'Square Meters dinning room', 'import-inmovilla-properties' ),
					'm_salon'      => __( 'Square Meters living room', 'import-inmovilla-properties' ),
					'm_patio'      => __( 'Square Meters playground', 'import-inmovilla-properties' ),
					'm_buhardilla' => __( 'Square Meters attic', 'import-inmovilla-properties' ),
					'm_pplanta'    => __( 'Square Meters first floor', 'import-inmovilla-properties' ),
					'm_sotano'     => __( 'Square Meters ground floor', 'import-inmovilla-properties' ),
				],
			],
			[
				'section' => 'distribution',
				'label'   => __( 'Distribution', 'import-inmovilla-properties' ),
				'fields'  => [
					'habdobles'    => __( 'Number of Double rooms', 'import-inmovilla-properties' ),
					'habitaciones' => __( 'Number of Rooms', 'import-inmovilla-properties' ),
					'banyos'       => __( 'Number of Bathrooms', 'import-inmovilla-properties' ),
					'aseos'        => __( 'Number of Toilets', 'import-inmovilla-properties' ),
					'salon'        => __( 'Number of Living rooms', 'import-inmovilla-properties' ),
					'numapar'      => __( 'Number of Parkings', 'import-inmovilla-properties' ),
					'numplanta'    => __( 'Number of floors', 'import-inmovilla-properties' ),
					'numplanta'    => __( 'Number of floors', 'import-inmovilla-properties' ),
					'antiguedad'   => __( 'Construction year', 'import-inmovilla-properties' ),
					'distmar'      => __( 'Beach distance', 'import-inmovilla-properties' ),
					'gastos_com'   => __( 'Community Expenses', 'import-inmovilla-properties' ),
					'tgascom'      => __( 'Community periodicity', 'import-inmovilla-properties' ),
					'ibi'          => __( 'I.B.I.', 'import-inmovilla-properties' ),
				],
			],
			[
				'section' => 'property_data',
				'label'   => __( 'Property Data', 'import-inmovilla-properties' ),
				'fields'  => [
					'conservacion'    => __( 'Status', 'import-inmovilla-properties' ),
					'keycarpinext'    => __( 'External woodwork', 'import-inmovilla-properties' ),
					'keysuelo'        => __( 'Ground', 'import-inmovilla-properties' ),
					'keyori'          => __( 'Orientation', 'import-inmovilla-properties' ),
					'keycarpin'       => __( 'Internal woodwork', 'import-inmovilla-properties' ),
					'todoext'         => __( 'All external', 'import-inmovilla-properties' ),
					'keyvista'        => __( 'Views', 'import-inmovilla-properties' ),
					'keycalefa'       => __( 'Heating Type', 'import-inmovilla-properties' ),
					'keyagua'         => __( 'Hot water', 'import-inmovilla-properties' ),
					'cocina_inde'     => __( 'Kitchen Type', 'import-inmovilla-properties' ),
					'electro'         => __( 'Home Appliances', 'import-inmovilla-properties' ),
					'tipovpo'         => __( 'Regimen', 'import-inmovilla-properties' ),
					'keyelectricidad' => __( 'Electrical installation', 'import-inmovilla-properties' ),
					'keyfachada'      => __( 'Facade', 'import-inmovilla-properties' ),
				],
			],
			[
				'section' => 'energetic_certification',
				'label'   => __( 'Energetic certification', 'import-inmovilla-properties' ),
				'fields' => [
					'energiarecibido' => __( 'Energetic certification', 'import-inmovilla-properties' ),
					'energialetra'    => __( 'Energetic certification rating', 'import-inmovilla-properties' ),
					'energiavalor'    => __( 'Energetic certification value', 'import-inmovilla-properties' ),
					'emisionesletra'  => __( 'Emissions Rating', 'import-inmovilla-properties' ),
					'emisionesvalor'  => __( 'Emissions value', 'import-inmovilla-properties' ),
					'refcertificado'  => __( 'Certification reference', 'import-inmovilla-properties' ),
				],
			],
			[
				'section' => 'others',
				'label'   => __( 'Others', 'import-inmovilla-properties' ),
				'fields' => [

					'fechacambio'      => __( '', 'import-inmovilla-properties' ),
					'ref'              => __( 'SKU', 'import-inmovilla-properties' ),
					'nodisponible'     => __( '', 'import-inmovilla-properties' ),
					'precioreal'       => __( 'Real price', 'import-inmovilla-properties' ),
					'preciotraspaso'   => __( '', 'import-inmovilla-properties' ),
					'precioinmo'       => __( '', 'import-inmovilla-properties' ),
					'balcon'           => __( '', 'import-inmovilla-properties' ),
					'calefacentral'    => __( '', 'import-inmovilla-properties' ),
					'airecentral'      => __( '', 'import-inmovilla-properties' ),
					'plaza_gara'       => __( '', 'import-inmovilla-properties' ),
					'terraza'          => __( '', 'import-inmovilla-properties' ),
					'ascensor'         => __( 'Lift', 'import-inmovilla-properties' ),
					'montacargas'      => __( '', 'import-inmovilla-properties' ),
					'muebles'          => __( '', 'import-inmovilla-properties' ),
					'calefaccion'      => __( '', 'import-inmovilla-properties' ),
					'aire_con'         => __( '', 'import-inmovilla-properties' ),
					'primera_line'     => __( '', 'import-inmovilla-properties' ),
					'piscina_com'      => __( '', 'import-inmovilla-properties' ),
					'piscina_prop'     => __( '', 'import-inmovilla-properties' ),
					'total_hab'        => __( '', 'import-inmovilla-properties' ),
					'sumaseos'         => __( '', 'import-inmovilla-properties' ),
					'repercusion'      => __( '', 'import-inmovilla-properties' ),
					'exclu'            => __( '', 'import-inmovilla-properties' ),
					'parking'          => __( '', 'import-inmovilla-properties' ),
					'numagencia'       => __( '', 'import-inmovilla-properties' ),
					'estadoficha'      => __( '', 'import-inmovilla-properties' ),
					'precioalq'        => __( '', 'import-inmovilla-properties' ),
					'eninternet'       => __( '', 'import-inmovilla-properties' ),
					'urbanizacion'     => __( '', 'import-inmovilla-properties' ),
					'destacado'        => __( '', 'import-inmovilla-properties' ),
					'destestrella'     => __( '', 'import-inmovilla-properties' ),
					'opcioncompra'     => __( '', 'import-inmovilla-properties' ),
					'interesante'      => __( '', 'import-inmovilla-properties' ),
					'altitud'          => __( '', 'import-inmovilla-properties' ),
					'latitud'          => __( '', 'import-inmovilla-properties' ),
					'mls'              => __( '', 'import-inmovilla-properties' ),
					'numfotos'         => __( '', 'import-inmovilla-properties' ),
					'fotoletra'        => __( '', 'import-inmovilla-properties' ),
					'keypromo'         => __( '', 'import-inmovilla-properties' ),
					'entidadbancaria'  => __( '', 'import-inmovilla-properties' ),
					'vistasalmar'      => __( '', 'import-inmovilla-properties' ),
					'grupomls'         => __( '', 'import-inmovilla-properties' ),
					'numsucursal'      => __( '', 'import-inmovilla-properties' ),
					'vistasdespejadas' => __( '', 'import-inmovilla-properties' ),
					'grupoxmls'        => __( '', 'import-inmovilla-properties' ),
					'grupomil'         => __( '', 'import-inmovilla-properties' ),
					'x_personal'       => __( '', 'import-inmovilla-properties' ),
					'mascotas'         => __( '', 'import-inmovilla-properties' ),
					'aconsultar'       => __( '', 'import-inmovilla-properties' ),
					'outlet'           => __( '', 'import-inmovilla-properties' ),
					'tipomensual'      => __( '', 'import-inmovilla-properties' ),
					'idagente'         => __( '', 'import-inmovilla-properties' ),
					'nombreagente'     => __( '', 'import-inmovilla-properties' ),
					'apellidosagente'  => __( '', 'import-inmovilla-properties' ),
					'emailagente'      => __( '', 'import-inmovilla-properties' ),
					'telefono1agente'  => __( '', 'import-inmovilla-properties' ),
					'telefono2agente'  => __( '', 'import-inmovilla-properties' ),
					'srvfotos'         => __( '', 'import-inmovilla-properties' ),
					'soysrv'           => __( '', 'import-inmovilla-properties' ),
					'agencia'          => __( '', 'import-inmovilla-properties' ),
					'ciudad'           => __( '', 'import-inmovilla-properties' ),
					'zona'             => __( '', 'import-inmovilla-properties' ),
					'nbtipo'           => __( '', 'import-inmovilla-properties' ),
					'nbconservacion'   => __( '', 'import-inmovilla-properties' ),
					'fotoagente'       => __( '', 'import-inmovilla-properties' ),
					'keyprov'          => __( '', 'import-inmovilla-properties' ),
				],
			],
		];

		return $properties_fields;
	}
	/**
	 * Gets information from Inmovilla CRM
	 *
	 * @return array
	 */
	private function get_properties( $query_array ) {
		$agency_number = get_option( 'iip_agency_number' );
		$agency_pass   = get_option( 'iip_agency_pass' );
		$language      = get_option( 'iip_language' );


		if ( '2' === $agency_number ) {
			$ia = '84.120.176.252';
			$ib = '42.5.120.1';
		} else {
			$ia = isset( $_SERVER['REMOTE_ADDR'] ) ? esc_url_raw( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$ib = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '';
		}
		$query_text = $agency_number . ';' . $agency_pass . ';' . $language . ';lostipos';
		foreach ( $query_array as $query_item ) {
			$query_text = $query_text . ';' . $query_item;
		}
		$query_text   = rawurlencode( $query_text );
		$query_string = 'param=' . $query_text . '&json=1';

		// API connection.
		$args = array(
			'headers' => array(
				'Accept'     => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
				'Connection' => 'keep-alive'
			),
		);
		$response = wp_remote_get( 'http://apiweb.inmovilla.com/apiweb/apiweb_demo.php?' . $query_string, $args );
		if ( 200 === $response['response']['code'] ) {
			$body = wp_remote_retrieve_body( $response );
			return json_decode( $body, true );
		} else {
			return false;
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

new IIP_Admin( __FILE__ );
