( function( $ ) {
	// privacy dropdown
	$( document ).on( 'click', '.ps-privacy-dropdown ul li a', function() {
		var $a = $( this ).closest( 'a' ),
			$menu = $a.closest( 'ul' ),
			$input = $menu.siblings( 'input' ),
			$btn = $menu.siblings( '.ps-btn,.ps-js-dropdown-toggle' ),
			$icon = $btn.find( 'i' ),
			$label = $btn.find( '.ps-privacy-title' );

		$input.val( $a.attr( 'data-option-value' ) );
		$icon.attr( 'class', $a.find( 'i' ).attr( 'class' ) );
		$label.html( $a.find( 'span' ).html() );
		$menu.css( 'display', 'none' );
	} );

	// init datepicker
	function initDatepicker( $dp ) {
		if ( ! $dp ) {
			return;
		}

		$dp.each( function() {
			var $input = $( this ),
				value = $input.data( 'value' ),
				startDate = $input.data( 'dateStartDate' ),
				endDate = $input.data( 'dateEndDate' ),
				yearMin = String( $input.data( 'dateRangeMin' ) ),
				yearMax = String( $input.data( 'dateRangeMax' ) ),
				yearCurrent = new Date().getFullYear(),
				defaultDate = null,
				minDate = null,
				maxDate = null,
				yearRange,
				date;

			/**
			 * Since version 1.10.4, plus (+) or minus (-) sign is explicitly
			 * added to indicate year range relative to current year.
			 *
			 * @since 1.10.4
			 */
			yearMin = yearMin.match( /^[-+]\d+$/ ) ? +yearMin : -yearMin;
			yearMax = +yearMax;
			yearRange = _.map( [ yearMin, yearMax ], function( year ) {
				if ( year === -999 ) {
					return 'c-100';
				} else if ( year === 999 ) {
					return 'c+100';
				} else {
					year = Math.min( Math.max( year, -100 ), 100 );
					if ( year < 0 ) {
						return '' + year;
					} else {
						return '+' + year;
					}
				}
			} ).join( ':' );

			// Make sure the minimum and maximum date respects year range.
			if ( yearMin > 0 ) {
				defaultDate = '+' + yearMin + 'y';
				minDate = new Date( yearCurrent + yearMin, 0, 1 );
			} else if ( yearMax < 0 ) {
				defaultDate = yearMax + 'y';
				maxDate = new Date( yearCurrent - yearMax, 11, 31 );
			}

			$input.psDatepicker( {
				startDate: startDate,
				endDate: endDate,
				yearRange: yearRange,
				defaultDate: defaultDate,
				minDate: minDate,
				maxDate: maxDate,
				onSelect: function( dateText, inst ) {
					var $input = $( this ),
						date = $input.datepicker( 'getDate' ),
						value = [];

					if ( date ) {
						value.push( date.getFullYear() );
						value.push( date.getMonth() + 1 );
						value.push( date.getDate() );

						// Add zero padding.
						value[ 1 ] = ( value[ 1 ] < 10 ? '0' : '' ) + value[ 1 ];
						value[ 2 ] = ( value[ 2 ] < 10 ? '0' : '' ) + value[ 2 ];
					}

					$input.data( 'value', value.join( '-' ) );
					$input.trigger( 'input' );
				}
			} );

			if ( value ) {
				value = value.split( '-' );
				date = new Date( +value[ 0 ], +value[ 1 ] - 1, +value[ 2 ] );
				$input.psDatepicker( 'setDate', date );
			}
		} );

		$dp.addClass( 'datepickerInitialized' );
	}

	ps_datepicker = {
		init: initDatepicker
	};

	$( function() {
		initDatepicker( $( '#peepso-wrap .datepicker' ).not( '.datepickerInitialized' ) );
	} );
} )( jQuery );
