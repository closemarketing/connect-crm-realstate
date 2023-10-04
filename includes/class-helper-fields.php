<?php
/**
 * Library for FIELD connection
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2023 Closemarketing
 * @version    1.0
 */

namespace Close\ConnectCRM\RealState;

defined( 'ABSPATH' ) || exit;

/**
 * FIELD Connection.
 *
 * @since 1.0.0
 */
class FIELD {
	public static function get_meta() {
		
	}
	public static function get_fields_crm( $crm = 'anaconda' ) {
		if ( 'anaconda' === $crm ) {
			return self::get_fields_anaconda();
		} elseif ( 'inmovilla' === $crm ) {
			return self::get_fields_inmovilla();
		}
	}
	private static function get_fields_anaconda() {
		return $fields;
	}
	private static function get_fields_inmovilla() {
		$properties_fields = [
			[
				'section' => 'features',
				'label'   => __( 'Features', 'connect-crm-realstate' ),
				'fields'  => [
					'cod_ofer'     => __( 'Reference', 'connect-crm-realstate' ),
					'keyacci'      => __( 'Operation Type', 'connect-crm-realstate' ),
					'key_tipo'     => __( 'Property Type', 'connect-crm-realstate' ),
					'key_loca'     => __( 'City', 'connect-crm-realstate' ),
					'key_zona'     => __( 'Zone', 'connect-crm-realstate' ),
					'zonaauxiliar' => __( 'Complementary Zone', 'connect-crm-realstate' ),
					'keycalle'     => __( 'Street key', 'connect-crm-realstate' ),
					'calle'        => __( 'Street', 'connect-crm-realstate' ),
					'numero'       => __( 'Street number', 'connect-crm-realstate' ),
					'cp'           => __( 'ZIP', 'connect-crm-realstate' ),
					'altura'       => __( 'Height', 'connect-crm-realstate' ),
					'planta'       => __( 'Block', 'connect-crm-realstate' ),
					'planta'       => __( 'Floor', 'connect-crm-realstate' ),
					'puerta'       => __( 'Door', 'connect-crm-realstate' ),
					'escalera'     => __( 'Stairs', 'connect-crm-realstate' ),
					'bloque'       => __( 'Block', 'connect-crm-realstate' ),
					'edificio'     => __( 'Building', 'connect-crm-realstate' ),
					'fecha'        => __( 'Date creation', 'connect-crm-realstate' ),
					'fechaact'     => __( 'Date updated', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'surfaces',
				'label'   => __( 'Surfaces', 'connect-crm-realstate' ),
				'fields'  => [
					'm_uties'      => __( 'Useful square meters', 'connect-crm-realstate' ),
					'm_cons'       => __( 'Square Meters built', 'connect-crm-realstate' ),
					'm_parcela'    => __( 'Square Meters plot', 'connect-crm-realstate' ),
					'm_terraza'    => __( 'Square Meters terrace', 'connect-crm-realstate' ),
					'm_cocina'     => __( 'Square Meters kitchen', 'connect-crm-realstate' ),
					'm_comedor'    => __( 'Square Meters dinning room', 'connect-crm-realstate' ),
					'm_salon'      => __( 'Square Meters living room', 'connect-crm-realstate' ),
					'm_patio'      => __( 'Square Meters playground', 'connect-crm-realstate' ),
					'm_buhardilla' => __( 'Square Meters attic', 'connect-crm-realstate' ),
					'm_pplanta'    => __( 'Square Meters first floor', 'connect-crm-realstate' ),
					'm_sotano'     => __( 'Square Meters ground floor', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'distribution',
				'label'   => __( 'Distribution', 'connect-crm-realstate' ),
				'fields'  => [
					'habdobles'    => __( 'Number of Double rooms', 'connect-crm-realstate' ),
					'habitaciones' => __( 'Number of Rooms', 'connect-crm-realstate' ),
					'banyos'       => __( 'Number of Bathrooms', 'connect-crm-realstate' ),
					'aseos'        => __( 'Number of Toilets', 'connect-crm-realstate' ),
					'salon'        => __( 'Number of Living rooms', 'connect-crm-realstate' ),
					'numapar'      => __( 'Number of Parkings', 'connect-crm-realstate' ),
					'numplanta'    => __( 'Number of floors', 'connect-crm-realstate' ),
					'numplanta'    => __( 'Number of floors', 'connect-crm-realstate' ),
					'antiguedad'   => __( 'Construction year', 'connect-crm-realstate' ),
					'distmar'      => __( 'Beach distance', 'connect-crm-realstate' ),
					'gastos_com'   => __( 'Community Expenses', 'connect-crm-realstate' ),
					'tgascom'      => __( 'Community periodicity', 'connect-crm-realstate' ),
					'ibi'          => __( 'I.B.I.', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'property_data',
				'label'   => __( 'Property Data', 'connect-crm-realstate' ),
				'fields'  => [
					'conservacion'    => __( 'Status', 'connect-crm-realstate' ),
					'keycarpinext'    => __( 'External woodwork', 'connect-crm-realstate' ),
					'keysuelo'        => __( 'Ground', 'connect-crm-realstate' ),
					'keyori'          => __( 'Orientation', 'connect-crm-realstate' ),
					'keycarpin'       => __( 'Internal woodwork', 'connect-crm-realstate' ),
					'todoext'         => __( 'All external', 'connect-crm-realstate' ),
					'keyvista'        => __( 'Views', 'connect-crm-realstate' ),
					'keycalefa'       => __( 'Heating Type', 'connect-crm-realstate' ),
					'keyagua'         => __( 'Hot water', 'connect-crm-realstate' ),
					'cocina_inde'     => __( 'Kitchen Type', 'connect-crm-realstate' ),
					'electro'         => __( 'Home Appliances', 'connect-crm-realstate' ),
					'tipovpo'         => __( 'Regimen', 'connect-crm-realstate' ),
					'keyelectricidad' => __( 'Electrical installation', 'connect-crm-realstate' ),
					'keyfachada'      => __( 'Facade', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'energetic_certification',
				'label'   => __( 'Energetic certification', 'connect-crm-realstate' ),
				'fields' => [
					'energiarecibido' => __( 'Energetic certification', 'connect-crm-realstate' ),
					'energialetra'    => __( 'Energetic certification rating', 'connect-crm-realstate' ),
					'energiavalor'    => __( 'Energetic certification value', 'connect-crm-realstate' ),
					'emisionesletra'  => __( 'Emissions Rating', 'connect-crm-realstate' ),
					'emisionesvalor'  => __( 'Emissions value', 'connect-crm-realstate' ),
					'refcertificado'  => __( 'Certification reference', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'features',
				'label'   => __( 'Features', 'connect-crm-realstate' ),
				'type'    => 'taxonomy',
				'fields'  => [
					'ofertas.adaptadominus'   => __( 'Adaptado PMR', 'connect-crm-realstate' ),
					'ofertas.agua'            => __( 'Agua', 'connect-crm-realstate' ),
					'ofertas.airecentral'     => __( 'Aire Acond. Central', 'connect-crm-realstate' ),
					'ofertas.aire_con'        => __( 'Aire Acondicionado ', 'connect-crm-realstate' ),
					'ofertas.alarma'          => __( 'Alarma ', 'connect-crm-realstate' ),
					'ofertas.alarmaincendio'  => __( 'Alarma Incendio', 'connect-crm-realstate' ),
					'ofertas.alarmarobo'      => __( 'Alarma Robo', 'connect-crm-realstate' ),
					'ofertas.altillo'         => __( 'Altillo', 'connect-crm-realstate' ),
					'ofertas.apartseparado'   => __( 'Apart. Separado', 'connect-crm-realstate' ),
					'ofertas.arma_empo'       => __( 'Armarios empotrados', 'connect-crm-realstate' ),
					'ofertas.ascensor'        => __( 'Ascensor', 'connect-crm-realstate' ),
					'ofertas.balcon'          => __( 'Balcón ', 'connect-crm-realstate' ),
					'ofertas.bar'             => __( 'Bar', 'connect-crm-realstate' ),
					'ofertas.barbacoa'        => __( 'Barbacoa', 'connect-crm-realstate' ),
					'ofertas.bombafriocalor'  => __( 'Bomba frío y calor', 'connect-crm-realstate' ),
					'ofertas.buardilla'       => __( 'Buhardilla ', 'connect-crm-realstate' ),
					'ofertas.cajafuerte'      => __( 'Caja fuerte', 'connect-crm-realstate' ),
					'ofertas.calefaccion'     => __( 'Calefacción', 'connect-crm-realstate' ),
					'ofertas.calefacentral'   => __( 'Calefacción central', 'connect-crm-realstate' ),
					'ofertas.chimenea'        => __( 'Chimenea', 'connect-crm-realstate' ),
					'ofertas.depoagua'        => __( 'Deposito Agua', 'connect-crm-realstate' ),
					'ofertas.descalcificador' => __( 'Descalcificador', 'connect-crm-realstate' ),
					'ofertas.despensa'        => __( 'Despensa', 'connect-crm-realstate' ),
					'ofertas.diafano'         => __( 'Diáfano', 'connect-crm-realstate' ),
					'ofertas.esquina'         => __( 'Esquina', 'connect-crm-realstate' ),
					'ofertas.galeria'         => __( 'Galería', 'connect-crm-realstate' ),
					'ofertas.plaza_gara'      => __( 'Plazas Garage', 'connect-crm-realstate' ),
					'ofertas.garajedoble'     => __( 'Garaje Doble', 'connect-crm-realstate' ),
					'ofertas.gasciudad'       => __( 'Gas Ciudad ', 'connect-crm-realstate' ),
					'ofertas.gimnasio'        => __( 'Gimnasio', 'connect-crm-realstate' ),
					'ofertas.habjuegos'       => __( 'Hab. Juegos', 'connect-crm-realstate' ),
					'ofertas.hidromasaje'     => __( 'Hidromasaje', 'connect-crm-realstate' ),
					'ofertas.hilomusical'     => __( 'Hilo Musical', 'connect-crm-realstate' ),
					'ofertas.jacuzzi'         => __( 'Jacuzzi', 'connect-crm-realstate' ),
					'ofertas.jardin'          => __( 'Jardín ', 'connect-crm-realstate' ),
					'ofertas.lavanderia'      => __( 'Lavandería ', 'connect-crm-realstate' ),
					'ofertas.linea_tlf'       => __( 'Línea telefónica', 'connect-crm-realstate' ),
					'ofertas.luminoso'        => __( 'Luminoso', 'connect-crm-realstate' ),
					'ofertas.luz'             => __( 'Luz', 'connect-crm-realstate' ),
					'ofertas.mirador'         => __( 'Mirador', 'connect-crm-realstate' ),
					'ofertas.montacargas'     => __( 'Montacargas', 'connect-crm-realstate' ),
					'ofertas.muebles'         => __( 'Muebles', 'connect-crm-realstate' ),
					'ofertas.ojobuey'         => __( 'Ojos de Buey', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'entorno',
				'label'   => __( 'Entorno', 'connect-crm-realstate' ),
				'type'    => 'taxonomy',
				'fields'  => [
					'entorno9'                 => __( 'Autobuses', 'connect-crm-realstate' ),
					'entorno0'                 => __( 'Árboles', 'connect-crm-realstate' ),
					'entorno14'                => __( 'Céntrico', 'connect-crm-realstate' ),
					'entorno10'                => __( 'Centros comerciales', 'connect-crm-realstate' ),
					'entorno15'                => __( 'Centros médicos', 'connect-crm-realstate' ),
					'entorno18'                => __( 'Cerca de Universidad', 'connect-crm-realstate' ),
					'entorno13'                => __( 'Colegios', 'connect-crm-realstate' ),
					'entorno7'                 => __( 'Costa', 'connect-crm-realstate' ),
					'entorno4'                 => __( 'Golf', 'connect-crm-realstate' ),
					'entorno1'                 => __( 'Hospitales', 'connect-crm-realstate' ),
					'entorno3'                 => __( 'Metro', 'connect-crm-realstate' ),
					'entorno5'                 => __( 'Montaña', 'connect-crm-realstate' ),
					'ofertas.primera_line'     => __( 'Primera Línea', 'connect-crm-realstate' ),
					'entorno6'                 => __( 'Rural', 'connect-crm-realstate' ),
					'entorno19'                => __( 'Supermercados', 'connect-crm-realstate' ),
					'entorno11'                => __( 'Tranvía', 'connect-crm-realstate' ),
					'entorno2'                 => __( 'Tren', 'connect-crm-realstate' ),
					'ofertas.urbanizacion'     => __( 'Urbanización', 'connect-crm-realstate' ),
					'entorno8'                 => __( 'Vallado', 'connect-crm-realstate' ),
					'entorno17'                => __( 'Vigilancia 24H', 'connect-crm-realstate' ),
					'ofertas.vistasalmar'      => __( 'Vistas al mar', 'connect-crm-realstate' ),
					'ofertas.vistasdespejadas' => __( 'Vistas despejadas', 'connect-crm-realstate' ),
					'entorno16'                => __( 'Zona de paso', 'connect-crm-realstate' ),
					'entorno12'                => __( 'Zonas infantiles', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'general-description',
				'label'   => __( 'General Description', 'connect-crm-realstate' ),
				'fields'  => [
					'ofertas.tinterior'     => __( 'Descripción General', 'connect-crm-realstate' ),
					'comentadd.tfachadaofe' => __( 'Fachada', 'connect-crm-realstate' ),
					'comentadd.tcocinaofe'  => __( 'Cocina', 'connect-crm-realstate' ),
					'comentadd.tpostigoofe' => __( 'Portal', 'connect-crm-realstate' ),
					'comentadd.tbanoofe'    => __( 'Baños', 'connect-crm-realstate' ),
				],
			],
			[
				'section' => 'sell-data',
				'label'   => __( 'Sell data', 'connect-crm-realstate' ),
				'fields'  => [
					'ofertas.preciotraspaso' => __( 'Precio Traspaso', 'connect-crm-realstate' ),
					'ofertas.precio'         => __( 'Precio Propietario ', 'connect-crm-realstate' ),
					'ofertas.porcen'         => __( 'Valor Honorarios %', 'connect-crm-realstate' ),
					'ofertas.comision'       => __( 'Valor Honorarios €', 'connect-crm-realstate' ),
					'ofertas.porceniva'      => __( 'I.V.A %', 'connect-crm-realstate' ),
					'ofertas.precioiva'      => __( 'I.V.A Precio', 'connect-crm-realstate' ),
					'ofertas.precioinmo'     => __( 'Precio Inmobiliaria', 'connect-crm-realstate' ),
					'ofertas.outlet'         => __( 'Precio Anterior', 'connect-crm-realstate' ),
					'ofertas.tasar'          => __( 'Valoración', 'connect-crm-realstate' ),
					'ofertas.valorfiscal'    => __( 'Valor Fiscal', 'connect-crm-realstate' ),
					'ofertas.aconsultar'     => __( 'Precio a consultar', 'connect-crm-realstate' ),
					'ofertas.alta_exclusiva' => __( 'Exclusivas Desde', 'connect-crm-realstate' ),
					'ofertas.baja_exclusiva' => __( 'Exclusivas Hasta', 'connect-crm-realstate' ),
				],
			],
		];

		return $properties_fields;
	}
}
