jQuery( function( $ ) {

	var $dateFormatNoYear = $( 'select[name=date_format_no_year]' ),
		$dateFormatNoYearCustom = $( 'input[name=date_format_no_year_custom]' ).closest( '.form-group' );

	$dateFormatNoYear.on('change', function() {
		('custom' == this.value) ? $dateFormatNoYearCustom.show() : $dateFormatNoYearCustom.hide();
	} );
	$dateFormatNoYear.triggerHandler('change');

} );
