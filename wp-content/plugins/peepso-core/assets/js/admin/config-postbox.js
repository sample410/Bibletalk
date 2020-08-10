// Handle toggle allow non-alphanumeric hashtags.
jQuery( function( $ ) {
	var $enable = $( 'input[name=hashtags_enable]' ),
		$everything = $( 'input[name=hashtags_everything]' ),
		$min = $( 'select[name=hashtags_min_length]' ),
		$max = $( 'select[name=hashtags_max_length]' ),
		$letter = $( 'input[name=hashtags_must_start_with_letter]' );

	$enable.on( 'click', function() {
		var $field = $( this ).closest( '.form-group' ),
			$childFields = $field.nextAll( '.form-group' );

		if ( this.checked ) {
			$childFields.show();
			$everything.triggerHandler( 'click' );
		} else {
			$childFields.hide();
		}
	} );

	$everything.on( 'click', function() {
		var $minField = $min.closest( '.form-group' ),
			$maxField = $max.closest( '.form-group' ),
			$letterField = $letter.closest( '.form-group' ),
			$fields = $minField.add( $maxField ).add( $letterField );

		if ( this.checked ) {
			$fields.hide();
		} else {
			$fields.show();
		}
	} );

	$enable.triggerHandler( 'click' );
} );
