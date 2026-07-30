/**
 * Codeholt Bundles for WooCommerce — admin bundle builder.
 */
( function ( $ ) {
	'use strict';

	var $body = $( document.body );

	function panel() {
		return $( '#bpfw_bundled_products_data' );
	}

	function itemsBody() {
		return $( '#bpfw_items_body' );
	}

	/* -------------------------------------------------------------------- */
	/* Product type integration                                              */
	/* -------------------------------------------------------------------- */

	// Let WooCommerce core show/hide the right panels for the bundle type.
	$( '.inventory_tab, .shipping_tab' ).addClass( 'show_if_bundle' );
	$( '#inventory_product_data ._manage_stock_field' ).addClass( 'show_if_bundle' );
	$( '#inventory_product_data ._sold_individually_field' ).parent().addClass( 'show_if_bundle' );
	$( '#inventory_product_data ._sold_individually_field' ).addClass( 'show_if_bundle' );

	$body.on( 'woocommerce-product-type-change', function ( e, type ) {
		if ( 'bundle' === type ) {
			$( '.show_if_bundle' ).show();
			togglePricingFields();
		}
	} );

	// Initial state when editing an existing bundle.
	if ( 'bundle' === $( '#product-type' ).val() ) {
		$( '.show_if_bundle' ).show();
	}

	/* -------------------------------------------------------------------- */
	/* Pricing mode                                                          */
	/* -------------------------------------------------------------------- */

	function togglePricingFields() {
		var fixed = 'fixed' === $( '#bpfw_pricing_mode' ).val();
		$( '.bpfw_fixed_price_field' ).toggle( fixed );
		updateTotals();
	}

	$body.on( 'change', '#bpfw_pricing_mode', togglePricingFields );
	togglePricingFields();

	/* -------------------------------------------------------------------- */
	/* Totals preview                                                        */
	/* -------------------------------------------------------------------- */

	function updateTotals() {
		var regular = 0,
			active = 0;

		itemsBody()
			.find( 'tr.bpfw-item' )
			.each( function () {
				var $row = $( this ),
					qty = parseInt( $row.find( '.bpfw-item-qty' ).val(), 10 ) || 1;

				regular += ( parseFloat( $row.data( 'regular' ) ) || 0 ) * qty;
				active += ( parseFloat( $row.data( 'price' ) ) || 0 ) * qty;
			} );

		var price = active;

		if ( 'fixed' === $( '#bpfw_pricing_mode' ).val() ) {
			var fixed = parseFloat( String( $( '#bpfw_fixed_price' ).val() ).replace( ',', '.' ) );
			if ( ! isNaN( fixed ) ) {
				price = fixed;
			}
		}

		var savings = Math.max( 0, regular - price );

		panel()
			.find( '.bpfw-total-regular' )
			.text( bpfwAdmin.currency + regular.toFixed( 2 ) );

		panel()
			.find( '.bpfw-total-savings' )
			.text( savings > 0 ? ' — ' + bpfwAdmin.i18n.save + ' ' + bpfwAdmin.currency + savings.toFixed( 2 ) : '' );

		panel()
			.find( '.bpfw-empty-row' )
			.toggle( 0 === itemsBody().find( 'tr.bpfw-item' ).length );
	}

	$body.on( 'change input', '.bpfw-item-qty, #bpfw_fixed_price', updateTotals );

	/* -------------------------------------------------------------------- */
	/* Add / remove / sort items                                             */
	/* -------------------------------------------------------------------- */

	var rowIndex = 1000; // High base avoids collisions with server-rendered indexes.

	$body.on( 'select2:select', '#bpfw_add_product', function ( e ) {
		var productId = parseInt( e.params.data.id, 10 ),
			$select = $( this );

		$select.val( null ).trigger( 'change' );

		if ( ! productId ) {
			return;
		}

		var duplicate = false;
		itemsBody()
			.find( '.bpfw-item-id' )
			.each( function () {
				if ( parseInt( $( this ).val(), 10 ) === productId ) {
					duplicate = true;
				}
			} );

		if ( duplicate ) {
			window.alert( bpfwAdmin.i18n.duplicate );
			return;
		}

		$.post(
			bpfwAdmin.ajaxUrl,
			{
				action: 'bpfw_add_bundle_item',
				nonce: bpfwAdmin.nonce,
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

	$body.on( 'click', '.bpfw-remove-item', function () {
		$( this ).closest( 'tr' ).remove();
		updateTotals();
	} );

	itemsBody().sortable( {
		items: 'tr.bpfw-item',
		handle: '.bpfw-col-sort',
		axis: 'y',
		cursor: 'move',
		opacity: 0.8,
	} );

	updateTotals();
} )( jQuery );
