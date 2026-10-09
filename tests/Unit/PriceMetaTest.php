<?php
/**
 * Tests for price metadata created during property synchronization.
 *
 * @package ConnectCRM\RealState\Tests\Unit
 */

namespace Close\ConnectCRM\RealState\Tests\Unit;

use Close\ConnectCRM\RealState\SYNC;
use WP_UnitTestCase;

/**
 * Price metadata test case.
 */
class PriceMetaTest extends WP_UnitTestCase {

	/**
	 * Both Inmovilla APIs create display and numeric price values.
	 *
	 * @dataProvider inmovilla_price_provider
	 *
	 * @param string $crm      CRM identifier.
	 * @param array  $property Property returned by the CRM.
	 * @return void
	 */
	public function test_sync_adds_formatted_and_raw_price_meta( $crm, $property ) {
		$result = SYNC::sync_property(
			$property,
			array(
				'type'      => $crm,
				'post_type' => 'post',
			),
			array(
				'precioinmo' => 'crm_precioinmo',
			)
		);

		$post_id = (int) $result['post_id'];

		try {
			$this->assertSame( '195.000 €', get_post_meta( $post_id, 'crm_precioinmo_formatted', true ) );
			$this->assertSame( '195000', get_post_meta( $post_id, 'crm_precioinmo_raw', true ) );
		} finally {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Missing merge settings fall back to native property meta keys.
	 *
	 * @return void
	 */
	public function test_sync_normalizes_missing_merge_settings() {
		delete_option( 'ccrmre_merge_fields' );

		$result  = SYNC::sync_property(
			$this->get_apiweb_property( 100003 ),
			array(
				'type'      => 'inmovilla',
				'post_type' => 'post',
			)
		);
		$post_id = (int) $result['post_id'];

		try {
			$this->assertSame( '195.000 €', get_post_meta( $post_id, 'property_precioinmo_formatted', true ) );
			$this->assertSame( '195000', get_post_meta( $post_id, 'property_precioinmo_raw', true ) );
		} finally {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Derived keys do not overwrite values mapped from other CRM fields.
	 *
	 * @return void
	 */
	public function test_sync_preserves_colliding_mapped_fields() {
		$property                    = $this->get_apiweb_property( 100004 );
		$property['raw_source']      = 'keep raw mapping';
		$property['formatted_source'] = 'keep formatted mapping';

		$result = SYNC::sync_property(
			$property,
			array(
				'type'      => 'inmovilla',
				'post_type' => 'post',
			),
			array(
				'precioinmo'       => 'crm_precioinmo',
				'raw_source'       => 'crm_precioinmo_raw',
				'formatted_source' => 'crm_precioinmo_formatted',
			)
		);
		$post_id = (int) $result['post_id'];

		try {
			$this->assertSame( 'keep raw mapping', get_post_meta( $post_id, 'crm_precioinmo_raw', true ) );
			$this->assertSame( 'keep formatted mapping', get_post_meta( $post_id, 'crm_precioinmo_formatted', true ) );
		} finally {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Returns a minimum APIWEB property fixture.
	 *
	 * @param int $property_id Property identifier.
	 * @return array
	 */
	private function get_apiweb_property( $property_id ) {
		return array(
			'cod_ofer'      => $property_id,
			'ref'           => 'PRICE-' . $property_id,
			'fechaact'      => '2026-09-30 10:00:00',
			'nodisponible'  => 0,
			'ciudad'        => 'Granada',
			'precioinmo'    => 195000,
			'descripciones' => array(
				'titulo'  => 'APIWEB price test',
				'descrip' => 'Property content.',
			),
		);
	}

	/**
	 * Provides the minimum property data for each Inmovilla API.
	 *
	 * @return array
	 */
	public function inmovilla_price_provider() {
		return array(
			'APIWEB'    => array(
				'inmovilla',
				array(
					'cod_ofer'      => 100001,
					'ref'           => 'PRICE-APIWEB',
					'fechaact'      => '2026-09-30 10:00:00',
					'nodisponible'  => 0,
					'ciudad'        => 'Granada',
					'precioinmo'    => 195000,
					'descripciones' => array(
						'titulo'  => 'APIWEB price test',
						'descrip' => 'Property content.',
					),
				),
			),
			'Procesos' => array(
				'inmovilla_procesos',
				array(
					'cod_ofer'     => 100002,
					'fechaact'     => '2026-09-30 10:00:00',
					'nodisponible' => 0,
					'ciudad'       => 'Granada',
					'precioinmo'   => 195000,
					'tituloes'     => 'Procesos price test',
					'descripciones' => 'Property content.',
				),
			),
		);
	}
}
