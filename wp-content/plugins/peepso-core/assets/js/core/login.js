/** @module login */

import $ from 'jquery';
import Promise from 'promise/lib/es6-extensions';
import observer from './observer';

/**
 * Check if OTP is enabled by a user.
 *
 * @param {string} username
 * @returns {Promise}
 */
const tfaCheck = function( username ) {
	return new Promise( resolve => {
		let tfaCodeNeeded = false;

		$.ajax( {
			url: peepsodata.ajaxurl,
			type: 'POST',
			data: {
				action: 'simbatfa-init-otp',
				user: username
			},
			dataType: 'json',
			success( json ) {
				try {
					if ( json.status === true ) {
						tfaCodeNeeded = true;
					}
				} catch ( e ) {}
			},
			complete() {
				resolve( tfaCodeNeeded );
			}
		} );
	} );
};

/**
 * Validation before submitting login form.
 *
 * @function
 * @param {*} form
 */
const presubmit = function( form ) {
	let $form = $( form ),
		$username = $form.find( '[name=username]' ),
		$password = $form.find( '[name=password]' ),
		$tfa = $form.find( '[name=two_factor_code]' ),
		$submit = $form.find( '[type=submit]' ),
		username = $username.val().trim(),
		password = $password.val().trim();

	if ( ! username && ! password ) {
		return false;
	}

	$submit.attr( 'disabled', true );
	$submit.find( 'img' ).show();

	if ( ! $tfa.length ) {
		submit( form );
		return false;
	}

	tfaCheck( username ).then( tfaCodeNeeded => {
		if ( tfaCodeNeeded && ! $tfa.is( ':visible' ) ) {
			$username.closest( '.ps-js-username-field' ).hide();
			$password.closest( '.ps-js-password-field' ).hide();
			$tfa.closest( '.ps-js-tfa-field' ).show();
			$submit.removeAttr( 'disabled' );
			$submit.find( 'img' ).hide();
		} else {
			submit( form );
		}
	} );

	return false;
};

/**
 * Submit login form.
 *
 * @function
 * @param {HTMLFormElement} form
 */
const submit = function( form ) {
	let $form = $( form ),
		$submit = $form.find( '[type=submit]' ),
		username = $form.find( '[name=username]' ).val(),
		password = $form.find( '[name=password]' ).val(),
		security = $form.find( '[name=security]' ).val(),
		remember = $form.find( '[name=remember]' ).is( ':checked' ) ? 1 : 0,
		redirect = $form.find( '[name=redirect_to]' ).val(),
		$extras = $form.find( '[name][data-ps-extra]' ),
		data,
		ajax;

	// Prepare login parameters.
	data = { username, password, security, remember };
	if ( $extras.length ) {
		$extras.each( function() {
			data[ this.name ] = this.value;
		} );
	}

	// Send login request.
	ajax = peepso.postJson( 'auth.login', data, function() {} ).ret;

	// Handle login response.
	ajax.always( function( data /* or jqXHR */ ) {
		let response = data;

		// Handle non-200 response code.
		if ( response.responseJSON ) {
			response = response.responseJSON;
		}

		$submit.removeAttr( 'disabled' );
		$submit.find( 'img' ).hide();

		if ( response.success ) {
			observer.doAction( 'login.success' );
			if ( redirect && window.location.href !== redirect ) {
				window.location = redirect;
			} else {
				window.location.reload( true );
			}
		} else if ( response.errors ) {
			try {
				let title = ( response.data && response.data.dialog_title ) || '';
				pswindow.hide();
				if ( false === pswindow.acknowledge( response.errors, title ) ) {
					form
						.find( '.errlogin' )
						.html( response.errors[ 0 ] )
						.css( 'display', 'block' );
				}

				// Show pending activation message if needed.
				let codes = ( response.data && response.data.error_code ) || [];
				if ( codes.indexOf( 'pending_approval' ) > -1 ) {
					$( '.ps-js-register-activation' ).show();
				}
			} catch ( e ) {}
			observer.doAction( 'login.error', form, response );
		} else {
			// Assume non-JSON response.
			observer.doAction( 'login.error', form, response );
			console.error( 'Non-JSON ajax response', response );
		}
	} );

	return false;
};

observer.addAction(
	'login.error',
	form => {
		let $form = $( form ),
			$username = $form.find( '[name=username]' ),
			$password = $form.find( '[name=password]' ),
			$tfa = $form.find( '[name=two_factor_code]' );

		// Clear out password field on failed login attempt.
		$password.val( '' );

		if ( $tfa.length ) {
			$tfa.val( '' );
			$tfa.closest( '.ps-js-tfa-field' ).hide();
			$username.closest( '.ps-js-username-field' ).show();
			$password.closest( '.ps-js-password-field' ).show();
		}
	},
	10,
	1
);

export default { submit: presubmit };

$( function() {
	/**
	 * Display the login dialog if a session_timeout is returned and authRequired is set to true
	 *
	 * @param {Event} e
	 * @param {boolean} reload
	 */
	$( window ).on( 'peepso_auth_required', function( e, reload ) {
		$( '.login-area input' ).attr( 'disabled', true );
		// Hide any open pswindows
		if ( pswindow.is_visible ) {
			pswindow.hide();
		}
		// TODO: string needs to be translatable
		pswindow.show( peepsodata.login_dialog_title, peepsodata.login_dialog );
		$( document ).trigger( 'peepso_login_shown' );
		$( '#ps-window' ).one( 'pswindow.hidden', function() {
			$( '.login-area input' ).removeAttr( 'disabled' );
		} );
		if ( reload ) {
			$( 'input[name=redirect_to]' ).val( window.location );
		}
	} );
} );
