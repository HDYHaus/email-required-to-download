<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-dom-ready',
		'wp-element',
		'wp-i18n',
	),
	'version'      => '1.2.0-' . md5_file( __DIR__ . '/index.js' ),
);
