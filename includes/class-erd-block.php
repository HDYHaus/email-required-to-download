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
		$block_type = register_block_type(
			ERD_PLUGIN_DIR . 'blocks/email-required-download',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);

		$editor_script_handles = is_object( $block_type ) && isset( $block_type->editor_script_handles ) ? $block_type->editor_script_handles : array();
		$editor_script_handle  = reset( $editor_script_handles );

		if ( $editor_script_handle ) {
			wp_add_inline_script(
				$editor_script_handle,
				'window.erdBlockData = ' . wp_json_encode(
					array(
						'offers' => $this->block_offers(),
					)
				) . ';',
				'before'
			);
		}
	}

	public function render( $attributes ) {
		$offer_id = isset( $attributes['offerId'] ) ? absint( $attributes['offerId'] ) : 0;
		return $offer_id ? $this->frontend->render_offer( $offer_id, $this->display_args( $attributes ) ) : '';
	}

	private function display_args( $attributes ) {
		$title_mode       = isset( $attributes['titleMode'] ) ? sanitize_key( $attributes['titleMode'] ) : 'offer';
		$description_mode = isset( $attributes['descriptionMode'] ) ? sanitize_key( $attributes['descriptionMode'] ) : 'offer';

		return array(
			'title_mode'        => in_array( $title_mode, array( 'offer', 'show', 'hide' ), true ) ? $title_mode : 'offer',
			'description_mode'  => in_array( $description_mode, array( 'offer', 'show', 'hide' ), true ) ? $description_mode : 'offer',
			'button_text'       => isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : '',
			'use_button_colors' => ! empty( $attributes['useButtonColors'] ),
			'button_bg_color'   => isset( $attributes['buttonBgColor'] ) ? sanitize_hex_color( $attributes['buttonBgColor'] ) : '',
			'button_text_color' => isset( $attributes['buttonTextColor'] ) ? sanitize_hex_color( $attributes['buttonTextColor'] ) : '',
		);
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
