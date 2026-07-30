/**
 * Import/Export dropzone filename display.
 *
 * @package BPFW
 */
( function() {
	var input = document.getElementById( 'bpfw_import_file' );
	if ( ! input ) {
		return;
	}
	var label = input.closest( '.bpfw-ie-dropzone' ).querySelector( '.bpfw-ie-dropzone__filename' );
	input.addEventListener( 'change', function() {
		label.textContent = input.files && input.files.length ? input.files[0].name : label.getAttribute( 'data-placeholder' );
	} );
} )();
