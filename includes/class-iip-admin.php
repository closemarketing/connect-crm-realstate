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
		//register our settings
		register_setting( 'iip_plugin_settings_group', 'iip_agency_number' );
		register_setting( 'iip_plugin_settings_group', 'iip_agency_pass' );
		register_setting( 'iip_plugin_settings_group', 'iip_post_type' );

		// Register Merge Settings.
		//register_setting( 'iip_plugin_merge_group', 'iip_agency_pass' );
	}

	/**
	 * Adds plugin settings page
	 *
	 * @return void
	 */
	public function plugin_settings_page() {
		$agency_number = get_option( 'iip_agency_number' );
		$agency_pass   = get_option( 'iip_agency_pass' );
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
						<th scope="row"><?php esc_html_e( 'Custom Post Type to import', 'import-inmovilla-properties' ); ?></th>
						<td><select name="iip_post_type"><?php echo $select_cpt_options; ?></select></td>
					</tr>

				</table>

				<?php submit_button(); ?>

			</form>
		</div>
		<?php
		if ( $show_merge_vars ) {
			$this->get_properties();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Merge Variables with custom values', 'import-inmovilla-properties'); ?></h1>
				<form method="post" action="options.php">
					<?php settings_fields( 'iip_plugin_merge_group' ); ?>
					<?php do_settings_sections( 'iip_plugin_merge_group' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php esc_html_e( 'Agency number', 'import-inmovilla-properties'); ?></th>
							<td><input type="text" name="agency_number" value="<?php echo esc_attr( $agency_number ); ?>" /></td>
						</tr>

					</table>

					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}

	}
	/**
	 * Gets information from Inmovilla CRM
	 *
	 * @return array
	 */
	private function get_properties() {
		$agency_number = get_option( 'iip_agency_number' );
		$agency_pass = get_option( 'iip_agency_pass' );
		
		$args = array(
			'headers' => array(
				'Accept'     => 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5',
				'Connection' => 'keep-alive'
			)
		);
		$response = wp_remote_get( 'http://apiweb.inmovilla.com/apiweb/apiweb_demo.php', $args );
		echo '<pre>';
		print_r($response);
		echo '</pre>';
		if ( 200 === $response['response']['code'] ) {
			$body = wp_remote_retrieve_body( $response );

			return $body;
		} else {
			return false;
		}
/*
function geturl($url,$campospost)
{
	$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
	$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,/*;q=0.5";
	$header[] = "Cache-Control: max-age=0";
	$header[] = "Connection: keep-alive";
	$header[] = "Keep-Alive: 300";
	$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
	$header[] = "Accept-Language: en-us,en;q=0.5";
	$header[] = "Pragma: ";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_POSTFIELDS,'');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	if (strlen($campospost)>0) {
		//los datos tienen que ser reales, de no ser asi se desactivara el servicio
		$_SERVER["REMOTE_ADDR"] = '84.220.176.253';
		$_SERVER["HTTP_X_FORWARDED_FOR"] = '84.120.176.252';

		$campospost=$campospost . "&ia=84.120.210.5&ib=42.5.120.1";
		curl_setopt($ch, CURLOPT_POSTFIELDS, $campospost);
	}
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
	$page = curl_exec($ch);
	curl_close($ch);

	return $page;
}*/

	}
}

new IIP_Admin( __FILE__ );
