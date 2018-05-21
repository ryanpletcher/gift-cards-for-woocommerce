jQuery(document).ready(function($) {
	var $body = $('body');

	$('#_wpr_cp').change(function( $ ) {
	    var c = this.checked ? '1.00': '';

		$('#_regular_price').val( c );
	});

});
