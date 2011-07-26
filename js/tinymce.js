/**
 * Every Calendar +1 WordPress Plugin TinyMCE init script
 */
jQuery(document).ready(function($) {
	if ( $('#ecp1_description').length ) {
		var elemId = $('#ecp1_description').attr('id');
		tinyMCE.execCommand('mceAddControl', false, elemId);
	}
});