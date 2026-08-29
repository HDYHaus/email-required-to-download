(function (blocks, blockEditor, components, element, i18n, serverSideRender) {
	'use strict';

	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ServerSideRender = serverSideRender.default || serverSideRender;
	var __ = i18n.__;

	blocks.registerBlockType('email-required-to-download/download', {
		edit: function (props) {
			var offers = window.erdBlockData && window.erdBlockData.offers ? window.erdBlockData.offers : [];
			var offerId = props.attributes.offerId || 0;

			return el(
				'div',
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Download offer', 'email-required-to-download') },
						el(SelectControl, {
							label: __('Offer', 'email-required-to-download'),
							value: offerId,
							options: offers,
							onChange: function (value) {
								props.setAttributes({ offerId: parseInt(value, 10) || 0 });
							}
						})
					)
				),
				offerId ? el(ServerSideRender, {
					block: 'email-required-to-download/download',
					attributes: props.attributes
				}) : el(
					'div',
					{ className: 'components-placeholder' },
					el('div', { className: 'components-placeholder__label' }, __('Email Required Download', 'email-required-to-download')),
					el(SelectControl, {
						label: __('Offer', 'email-required-to-download'),
						value: offerId,
						options: offers,
						onChange: function (value) {
							props.setAttributes({ offerId: parseInt(value, 10) || 0 });
						}
					})
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.serverSideRender);
