import $ from 'jquery';
import { observer } from 'peepso';

function update( fieldData, data ) {
	let {
		profile_completeness,
		profile_completeness_message,
		missing_required,
		missing_required_message
	} = data;

	let completed = 0;

	if ( typeof profile_completeness !== 'undefined' ) {
		let $status = $( '.ps-completeness-status' ),
			$bar = $( '.ps-completeness-bar' );

		if ( +profile_completeness >= 100 ) {
			completed++;
			$status.hide();
			$bar.hide();
		} else {
			$status.html( profile_completeness_message ).show();
			$bar.show();
			$bar.children( 'span' ).css( { width: +profile_completeness + '%' } );
		}
	}

	if ( typeof missing_required !== 'undefined' ) {
		let $missing = $( '.ps-missing-required-message' );

		if ( +missing_required <= 0 ) {
			completed++;
			$missing.hide();
		} else {
			$missing.html( missing_required_message ).show();
		}
	}

	let $container = $( '.ps-completeness-info' );
	if ( completed >= 2 ) {
		$container.hide();
	} else {
		$container.show();
	}
}

export default {
	init() {
		observer.addAction( 'profile_field_updated', update, 10, 2 );
	}
};
