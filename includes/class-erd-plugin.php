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

		add_filter( 'plugin_action_links_' . plugin_basename( ERD_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );

		if ( is_admin() ) {
			( new ERD_Admin( $this->repository ) )->init();
		}

		( new ERD_Frontend( $this->repository ) )->init();
	}

	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=erd-settings' ) ),
			esc_html__( 'Settings', 'email-required-to-download' )
		);

		array_unshift( $links, $settings_link );
		return $links;
	}
}
