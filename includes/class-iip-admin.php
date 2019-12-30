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

		// Create new top-level menu.
		add_menu_page( 'Import Inmovilla', 'Import Inmovilla', 'administrator', __FILE__, array( $this, 'plugin_settings_page' ), 'dashicons-rest-api' );

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
		foreach ( $properties_fields as $key => $value ) {
			register_setting( 'iip_plugin_merge_group', 'iip_var_' . $key );
		}
	}

	/**
	 * Adds plugin settings page
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
			<h1><?php esc_html_e( 'Inmovilla Properties Import Settings', 'import-inmovilla-properties' ); ?></h1>

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

			</form>
		</div>
		<?php
		if ( $show_merge_vars ) {

			$args_query = array( 'paginacion', 1, 1, 'ascensor=1', 'precioinmo, precioalq' );
			$property_base = $this->get_properties( $args_query );
			/*
			echo '<pre>iip_merge_vars';
			print_r($property_base['paginacion'][1]);
			echo '</pre>';*/
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Merge Variables with custom values', 'import-inmovilla-properties'); ?></h1>
				<form method="post" action="options.php">
					<?php settings_fields( 'iip_plugin_merge_group' ); ?>
					<?php do_settings_sections( 'iip_plugin_merge_group' ); ?>
					<table class="form-table">
						<?php
						$properties_fields = $this->get_properties_fields();
						foreach ( $properties_fields as $key => $value ) {
							echo '<tr valign="top">';
							echo '<th scope="row">' . $value . '</th>';
							echo '<td><select name="iip_var_' . $value . '">';
							echo '</select></td>';
							echo '</tr>';
						}
						?>
					</table>

					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

	}

	private function get_properties_fields() {
		$properties_fields = [
			'cod_ofer'         => __( '', 'import-inmovilla-properties' ),
			'keyacci'          => __( '', 'import-inmovilla-properties' ),
			'banyos'           => __( '', 'import-inmovilla-properties' ),
			'ref'              => __( 'SKU', 'import-inmovilla-properties' ),
			'nodisponible'     => __( '', 'import-inmovilla-properties' ),
			'precioreal'       => __( 'Real price', 'import-inmovilla-properties' ),
			'preciotraspaso'   => __( '', 'import-inmovilla-properties' ),
			'precioinmo'       => __( '', 'import-inmovilla-properties' ),
			'key_loca'         => __( '', 'import-inmovilla-properties' ),
			'key_zona'         => __( '', 'import-inmovilla-properties' ),
			'key_tipo'         => __( '', 'import-inmovilla-properties' ),
			'm_parcela'        => __( '', 'import-inmovilla-properties' ),
			'balcon'           => __( '', 'import-inmovilla-properties' ),
			'm_cons'           => __( '', 'import-inmovilla-properties' ),
			'm_uties'          => __( '', 'import-inmovilla-properties' ),
			'conservacion'     => __( '', 'import-inmovilla-properties' ),
			'calefacentral'    => __( '', 'import-inmovilla-properties' ),
			'airecentral'      => __( '', 'import-inmovilla-properties' ),
			'plaza_gara'       => __( '', 'import-inmovilla-properties' ),
			'terraza'          => __( '', 'import-inmovilla-properties' ),
			'ascensor'         => __( '', 'import-inmovilla-properties' ),
			'montacargas'      => __( '', 'import-inmovilla-properties' ),
			'muebles'          => __( '', 'import-inmovilla-properties' ),
			'calefaccion'      => __( '', 'import-inmovilla-properties' ),
			'aire_con'         => __( '', 'import-inmovilla-properties' ),
			'primera_line'     => __( '', 'import-inmovilla-properties' ),
			'piscina_com'      => __( '', 'import-inmovilla-properties' ),
			'piscina_prop'     => __( '', 'import-inmovilla-properties' ),
			'habitaciones'     => __( '', 'import-inmovilla-properties' ),
			'total_hab'        => __( '', 'import-inmovilla-properties' ),
			'sumaseos'         => __( '', 'import-inmovilla-properties' ),
			'repercusion'      => __( '', 'import-inmovilla-properties' ),
			'exclu'            => __( '', 'import-inmovilla-properties' ),
			'parking'          => __( '', 'import-inmovilla-properties' ),
			'todoext'          => __( '', 'import-inmovilla-properties' ),
			'distmar'          => __( '', 'import-inmovilla-properties' ),
			'numagencia'       => __( '', 'import-inmovilla-properties' ),
			'estadoficha'      => __( '', 'import-inmovilla-properties' ),
			'precioalq'        => __( '', 'import-inmovilla-properties' ),
			'keycalefa'        => __( '', 'import-inmovilla-properties' ),
			'eninternet'       => __( '', 'import-inmovilla-properties' ),
			'zonaauxiliar'     => __( '', 'import-inmovilla-properties' ),
			'urbanizacion'     => __( '', 'import-inmovilla-properties' ),
			'destacado'        => __( '', 'import-inmovilla-properties' ),
			'habdobles'        => __( '', 'import-inmovilla-properties' ),
			'destestrella'     => __( '', 'import-inmovilla-properties' ),
			'opcioncompra'     => __( '', 'import-inmovilla-properties' ),
			'm_terraza'        => __( '', 'import-inmovilla-properties' ),
			'interesante'      => __( '', 'import-inmovilla-properties' ),
			'altitud'          => __( '', 'import-inmovilla-properties' ),
			'latitud'          => __( '', 'import-inmovilla-properties' ),
			'mls'              => __( '', 'import-inmovilla-properties' ),
			'numfotos'         => __( '', 'import-inmovilla-properties' ),
			'fotoletra'        => __( '', 'import-inmovilla-properties' ),
			'keypromo'         => __( '', 'import-inmovilla-properties' ),
			'fechacambio'      => __( '', 'import-inmovilla-properties' ),
			'fechaact'         => __( '', 'import-inmovilla-properties' ),
			'entidadbancaria'  => __( '', 'import-inmovilla-properties' ),
			'vistasalmar'      => __( '', 'import-inmovilla-properties' ),
			'grupomls'         => __( '', 'import-inmovilla-properties' ),
			'numsucursal'      => __( '', 'import-inmovilla-properties' ),
			'energiarecibido'  => __( '', 'import-inmovilla-properties' ),
			'vistasdespejadas' => __( '', 'import-inmovilla-properties' ),
			'grupoxmls'        => __( '', 'import-inmovilla-properties' ),
			'grupomil'         => __( '', 'import-inmovilla-properties' ),
			'x_personal'       => __( '', 'import-inmovilla-properties' ),
			'mascotas'         => __( '', 'import-inmovilla-properties' ),
			'aconsultar'       => __( '', 'import-inmovilla-properties' ),
			'outlet'           => __( '', 'import-inmovilla-properties' ),
			'aseos'            => __( '', 'import-inmovilla-properties' ),
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
}

new IIP_Admin( __FILE__ );
