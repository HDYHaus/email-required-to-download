(function (blocks, blockEditor, components, element, i18n, domReady, serverSideRender) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender && (serverSideRender.default || serverSideRender);
	var __ = i18n.__;
	var blockName = 'email-required-to-download/download';

	function colorControl(label, value, onChange) {
		return el(
			'label',
			{ className: 'erd-block-color-control' },
			el('span', null, label),
			el('input', {
				type: 'color',
				value: value || '#1d2327',
				onChange: function (event) {
					onChange(event.target.value);
				}
			})
		);
	}

	function registerBlock() {
		if (blocks.getBlockType(blockName) && blocks.unregisterBlockType) {
			blocks.unregisterBlockType(blockName);
		}

		blocks.registerBlockType(blockName, {
			apiVersion: 3,
			title: __('Email Required Download', 'email-required-to-download'),
			category: 'widgets',
			icon: 'download',
			description: __('Show an Email Required to Download offer.', 'email-required-to-download'),
			attributes: {
				offerId: {
					type: 'number',
					default: 0
				},
				titleMode: {
					type: 'string',
					default: 'offer'
				},
				descriptionMode: {
					type: 'string',
					default: 'offer'
				},
				buttonText: {
					type: 'string',
					default: ''
				},
				useButtonColors: {
					type: 'boolean',
					default: false
				},
				buttonBgColor: {
					type: 'string',
					default: ''
				},
				buttonTextColor: {
					type: 'string',
					default: ''
				}
			},
			supports: {
				html: false
			},
			edit: function (props) {
				var offers = window.erdBlockData && window.erdBlockData.offers ? window.erdBlockData.offers : [];
				var offerId = props.attributes.offerId || 0;
				var blockProps = useBlockProps({
					className: 'erd-block-editor'
				});
				var update = function (key, value) {
					var next = {};
					next[key] = value;
					props.setAttributes(next);
				};
				var offerSelect = function () {
					return el(SelectControl, {
						label: __('Offer', 'email-required-to-download'),
						value: offerId,
						options: offers,
						onChange: function (value) {
							update('offerId', parseInt(value, 10) || 0);
						}
					});
				};

				return el(
					Fragment,
					null,
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __('Download Offer', 'email-required-to-download') },
							offerSelect()
						),
						el(
							PanelBody,
							{ title: __('Display', 'email-required-to-download'), initialOpen: true },
							el(SelectControl, {
								label: __('Title', 'email-required-to-download'),
								value: props.attributes.titleMode || 'offer',
								options: [
									{ label: __('Use offer setting', 'email-required-to-download'), value: 'offer' },
									{ label: __('Show', 'email-required-to-download'), value: 'show' },
									{ label: __('Hide', 'email-required-to-download'), value: 'hide' }
								],
								onChange: function (value) {
									update('titleMode', value);
								}
							}),
							el(SelectControl, {
								label: __('Description', 'email-required-to-download'),
								value: props.attributes.descriptionMode || 'offer',
								options: [
									{ label: __('Use offer setting', 'email-required-to-download'), value: 'offer' },
									{ label: __('Show', 'email-required-to-download'), value: 'show' },
									{ label: __('Hide', 'email-required-to-download'), value: 'hide' }
								],
								onChange: function (value) {
									update('descriptionMode', value);
								}
							}),
							el(TextControl, {
								label: __('Button text override', 'email-required-to-download'),
								value: props.attributes.buttonText || '',
								placeholder: __('Use offer button text', 'email-required-to-download'),
								onChange: function (value) {
									update('buttonText', value);
								}
							}),
							el(ToggleControl, {
								label: __('Use custom button colors', 'email-required-to-download'),
								checked: !!props.attributes.useButtonColors,
								onChange: function (value) {
									update('useButtonColors', value);
								}
							}),
							props.attributes.useButtonColors ? colorControl(
								__('Button background', 'email-required-to-download'),
								props.attributes.buttonBgColor || '#1d2327',
								function (value) {
									update('buttonBgColor', value);
								}
							) : null,
							props.attributes.useButtonColors ? colorControl(
								__('Button text', 'email-required-to-download'),
								props.attributes.buttonTextColor || '#ffffff',
								function (value) {
									update('buttonTextColor', value);
								}
							) : null
						)
					),
					el(
						'div',
						blockProps,
						el(
							'div',
							{ className: 'erd-block-editor-toolbar' },
							offerSelect()
						),
						offerId && ServerSideRender ? el(ServerSideRender, {
							block: blockName,
							attributes: props.attributes
						}) : el(
							'div',
							{ className: 'components-placeholder' },
							el('div', { className: 'components-placeholder__label' }, __('Email Required Download', 'email-required-to-download')),
							el(
								'div',
								{ className: 'components-placeholder__instructions' },
								offerId ? __('Offer selected. Save or preview the page to see the rendered download form.', 'email-required-to-download') : __('Select an offer to show its download form.', 'email-required-to-download')
							)
						)
					)
				);
			},
			save: function () {
				return null;
			}
		});
	}

	if (domReady) {
		domReady(registerBlock);
	} else {
		registerBlock();
	}
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n, window.wp.domReady, window.wp.serverSideRender);
