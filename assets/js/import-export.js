/**
 * Import/Export dropzone filename display.
 *
 * @package CBFW
 */
( function() {
	var input = document.getElementById( 'cbfw_import_file' );
	if ( ! input ) {
		return;
	}
	var label = input.closest( '.cbfw-ie-dropzone' ).querySelector( '.cbfw-ie-dropzone__filename' );
	input.addEventListener( 'change', function() {
		label.textContent = input.files && input.files.length ? input.files[0].name : label.getAttribute( 'data-placeholder' );
	} );
} )();
