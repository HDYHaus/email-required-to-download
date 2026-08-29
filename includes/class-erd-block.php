<?php
/**
 * Block editor integration.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Block {
	private $repository;
	private $frontend;

	public function __construct( ERD_Repository $repository, ERD_Frontend $frontend ) {
		$this->repository = $repository;
		$this->frontend   = $frontend;
	}

	public function init() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		wp_register_script(
			'erd-block-editor',
			ERD_PLUGIN_URL . 'blocks/email-required-download/index.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render' ),
			ERD_VERSION,
			true
		);

		wp_localize_script(
			'erd-block-editor',
			'erdBlockData',
			array(
				'offers' => $this->block_offers(),
			)
		);

		register_block_type(
			ERD_PLUGIN_DIR . 'blocks/email-required-download',
			array(
				'editor_script'   => 'erd-block-editor',
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	public function render( $attributes ) {
		$offer_id = isset( $attributes['offerId'] ) ? absint( $attributes['offerId'] ) : 0;
		return $offer_id ? $this->frontend->render_offer( $offer_id ) : '';
	}

	private function block_offers() {
		$options = array(
			array(
				'label' => __( 'Select an offer', 'email-required-to-download' ),
				'value' => 0,
			),
		);

		foreach ( $this->repository->get_offers() as $offer ) {
			$options[] = array(
				'label' => $offer->title,
				'value' => absint( $offer->id ),
			);
		}

		return $options;
	}
}
