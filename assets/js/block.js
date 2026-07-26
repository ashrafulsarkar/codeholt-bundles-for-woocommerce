/**
 * Bundle Product for WooCommerce — Gutenberg block (no build step).
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var Placeholder = wp.components.Placeholder;
	var ServerSideRender = wp.serverSideRender;

	var bundleChoices =
		window.bpfwBlockData && window.bpfwBlockData.bundles
			? window.bpfwBlockData.bundles
			: [ { value: 0, label: __( '— Select a bundle —', 'bundle-product-for-woocommerce' ) } ];

	registerBlockType( 'bpfw/bundle', {
		title: __( 'Product Bundle', 'bundle-product-for-woocommerce' ),
		description: __( 'Display a WooCommerce product bundle.', 'bundle-product-for-woocommerce' ),
		icon: 'archive',
		category: 'woocommerce',
		attributes: {
			bundleId: { type: 'number', default: 0 },
			layout: { type: 'string', default: 'card' },
			showImage: { type: 'boolean', default: true },
			showItems: { type: 'boolean', default: true },
		},

		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var inspector = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Bundle settings', 'bundle-product-for-woocommerce' ) },
					el( SelectControl, {
						label: __( 'Bundle', 'bundle-product-for-woocommerce' ),
						value: attributes.bundleId,
						options: bundleChoices,
						onChange: function ( value ) {
							setAttributes( { bundleId: parseInt( value, 10 ) || 0 } );
						},
					} ),
					el( SelectControl, {
						label: __( 'Layout', 'bundle-product-for-woocommerce' ),
						value: attributes.layout,
						options: [
							{ value: 'card', label: __( 'Card', 'bundle-product-for-woocommerce' ) },
							{ value: 'list', label: __( 'List', 'bundle-product-for-woocommerce' ) },
						],
						onChange: function ( value ) {
							setAttributes( { layout: value } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Show bundle image', 'bundle-product-for-woocommerce' ),
						checked: attributes.showImage,
						onChange: function ( value ) {
							setAttributes( { showImage: value } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Show bundled products', 'bundle-product-for-woocommerce' ),
						checked: attributes.showItems,
						onChange: function ( value ) {
							setAttributes( { showItems: value } );
						},
					} )
				)
			);

			var preview;

			if ( ! attributes.bundleId ) {
				preview = el( Placeholder, {
					icon: 'archive',
					label: __( 'Product Bundle', 'bundle-product-for-woocommerce' ),
					instructions: __( 'Choose a bundle in the block settings sidebar.', 'bundle-product-for-woocommerce' ),
				} );
			} else {
				preview = el( ServerSideRender, {
					block: 'bpfw/bundle',
					attributes: attributes,
				} );
			}

			return el( 'div', blockProps, inspector, preview );
		},

		save: function () {
			return null; // Dynamic block — rendered in PHP.
		},
	} );
} )( window.wp );
