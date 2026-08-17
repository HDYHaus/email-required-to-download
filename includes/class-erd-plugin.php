<?php
/**
 * Plugin coordinator.
 *
 * @package EmailRequiredDownload
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ERD_Plugin {
	private static $instance;
	private $repository;

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		$this->repository = new ERD_Repository();

		if ( is_admin() ) {
			( new ERD_Admin( $this->repository ) )->init();
		}

		( new ERD_Frontend( $this->repository ) )->init();
	}
}
