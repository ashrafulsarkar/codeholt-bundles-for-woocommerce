/**
 * Codeholt Bundles for WooCommerce — admin bundle builder.
 */
( function ( $ ) {
	'use strict';

	var $body = $( document.body );

	function panel() {
		return $( '#cbfw_bundled_products_data' );
	}

	function itemsBody() {
		return $( '#cbfw_items_body' );
	}

	/* -------------------------------------------------------------------- */
	/* Product type integration                                              */
	/* -------------------------------------------------------------------- */

	// Let WooCommerce core show/hide the right panels for the bundle type.
	$( '.inventory_tab, .shipping_tab' ).addClass( 'show_if_cbfw_bundle' );
	$( '#inventory_product_data ._manage_stock_field' ).addClass( 'show_if_cbfw_bundle' );
	$( '#inventory_product_data ._sold_individually_field' ).parent().addClass( 'show_if_cbfw_bundle' );
	$( '#inventory_product_data ._sold_individually_field' ).addClass( 'show_if_cbfw_bundle' );

	$body.on( 'woocommerce-product-type-change', function ( e, type ) {
		if ( 'cbfw_bundle' === type ) {
			$( '.show_if_cbfw_bundle' ).show();
			togglePricingFields();
		}
	} );

	// Initial state when editing an existing bundle.
	if ( 'cbfw_bundle' === $( '#product-type' ).val() ) {
		$( '.show_if_cbfw_bundle' ).show();
	}

	/* -------------------------------------------------------------------- */
	/* Pricing mode                                                          */
	/* -------------------------------------------------------------------- */

	function togglePricingFields() {
		var fixed = 'fixed' === $( '#cbfw_pricing_mode' ).val();
		$( '.cbfw_fixed_price_field' ).toggle( fixed );
		updateTotals();
	}

	$body.on( 'change', '#cbfw_pricing_mode', togglePricingFields );
	togglePricingFields();

	/* -------------------------------------------------------------------- */
	/* Totals preview                                                        */
	/* -------------------------------------------------------------------- */

	function updateTotals() {
		var regular = 0,
			active = 0;

		itemsBody()
			.find( 'tr.cbfw-item' )
			.each( function () {
				var $row = $( this ),
					qty = parseInt( $row.find( '.cbfw-item-qty' ).val(), 10 ) || 1;

				regular += ( parseFloat( $row.data( 'regular' ) ) || 0 ) * qty;
				active += ( parseFloat( $row.data( 'price' ) ) || 0 ) * qty;
			} );

		var price = active;

		if ( 'fixed' === $( '#cbfw_pricing_mode' ).val() ) {
			var fixed = parseFloat( String( $( '#cbfw_fixed_price' ).val() ).replace( ',', '.' ) );
			if ( ! isNaN( fixed ) ) {
				price = fixed;
			}
		}

		var savings = Math.max( 0, regular - price );

		panel()
			.find( '.cbfw-total-regular' )
			.text( cbfwAdmin.currency + regular.toFixed( 2 ) );

		panel()
			.find( '.cbfw-total-savings' )
			.text( savings > 0 ? ' — ' + cbfwAdmin.i18n.save + ' ' + cbfwAdmin.currency + savings.toFixed( 2 ) : '' );

		panel()
			.find( '.cbfw-empty-row' )
			.toggle( 0 === itemsBody().find( 'tr.cbfw-item' ).length );
	}

	$body.on( 'change input', '.cbfw-item-qty, #cbfw_fixed_price', updateTotals );

	/* -------------------------------------------------------------------- */
	/* Add / remove / sort items                                             */
	/* -------------------------------------------------------------------- */

	var rowIndex = 1000; // High base avoids collisions with server-rendered indexes.

	$body.on( 'select2:select', '#cbfw_add_product', function ( e ) {
		var productId = parseInt( e.params.data.id, 10 ),
			$select = $( this );

		$select.val( null ).trigger( 'change' );

		if ( ! productId ) {
			return;
		}

		var duplicate = false;
		itemsBody()
			.find( '.cbfw-item-id' )
			.each( function () {
				if ( parseInt( $( this ).val(), 10 ) === productId ) {
					duplicate = true;
				}
			} );

		if ( duplicate ) {
			window.alert( cbfwAdmin.i18n.duplicate );
			return;
		}

		$.post(
			cbfwAdmin.ajaxUrl,
			{
				action: 'cbfw_add_bundle_item',
				nonce: cbfwAdmin.nonce,
				product_id: productId,
				index: rowIndex++,
			},
			function ( response ) {
				if ( response && response.success ) {
					itemsBody().append( response.data.row );
					updateTotals();
				} else if ( response && response.data && response.data.message ) {
					window.alert( response.data.message );
				}
			}
		);
	} );

	$body.on( 'click', '.cbfw-remove-item', function () {
		$( this ).closest( 'tr' ).remove();
		updateTotals();
	} );

	itemsBody().sortable( {
		items: 'tr.cbfw-item',
		handle: '.cbfw-col-sort',
		axis: 'y',
		cursor: 'move',
		opacity: 0.8,
	} );

	updateTotals();
} )( jQuery );
