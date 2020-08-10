/** @module util */

import $ from 'jquery';

/* global FB:false */
export default {
	/**
	 * Checks whether an element is fully visible in the viewport.
	 *
	 * @param {HTMLElement} el
	 * @returns {boolean}
	 */
	isElementInViewport( el ) {
		let rect = el.getBoundingClientRect();

		return (
			rect.top >= 0 &&
			rect.left >= 0 &&
			rect.bottom <= ( window.innerHeight || document.documentElement.clientHeight ) &&
			rect.right <= ( window.innerWidth || document.documentElement.clientWidth )
		);
	},

	/**
	 * Checks whether an element is partly visible in the viewport.
	 *
	 * @param {HTMLElement} el
	 * @returns {boolean}
	 */
	isElementPartlyInViewport( el ) {
		let rect = el.getBoundingClientRect();

		return (
			rect.top < ( window.innerHeight || document.documentElement.clientHeight ) &&
			rect.left < ( window.innerWidth || document.documentElement.clientWidth ) &&
			rect.bottom > 0 &&
			rect.right > 0
		);
	},

	/**
	 * Scroll element into the top of the viewport.
	 *
	 * @param {HTMLElement} el
	 * @param {Object} opts
	 */
	scrollIntoView( el, opts = {} ) {
		el = $( el );
		if ( el.length && ! this.isElementInViewport( el[ 0 ] ) ) {
			let duration = opts.duration || 1000,
				position = opts.position || 'top',
				scrollTop = el.offset().top;

			if ( 'center' === position ) {
				scrollTop = Math.max( 0, scrollTop - window.innerHeight / 2 + el.height() / 2 );
			} else {
				scrollTop = Math.max( 0, scrollTop - 10 );
			}

			$( 'html, body' ).animate( { scrollTop }, duration );
		}
	},

	/**
	 * Scroll element into the top of the viewport if it is not already visible.
	 *
	 * @param {HTMLElement} el
	 * @param {Object} opts
	 */
	scrollIntoViewIfNeeded( el, opts = {} ) {
		el = $( el );
		if ( el.length && ! this.isElementPartlyInViewport( el[ 0 ] ) ) {
			let duration = opts.duration || 1000,
				position = opts.position || 'top',
				scrollTop = el.offset().top;

			if ( 'center' === position ) {
				scrollTop = Math.max( 0, scrollTop - window.innerHeight / 2 + el.height() / 2 );
			} else {
				scrollTop = Math.max( 0, scrollTop - 10 );
			}

			$( 'html, body' ).animate( { scrollTop }, duration );
		}
	},

	/**
	 * Load Facebook Javascript SDK.
	 *
	 * @return {JQuery.Deferred}
	 */
	fbLoadSDK() {
		let js,
			fjs,
			timer,
			count,
			d = document,
			s = 'script',
			id = 'facebook-jssdk';

		return $.Deferred( function( defer ) {
			if ( d.getElementById( id ) ) {
				count = 0;
				timer = setInterval( function() {
					if ( ++count > 20 || window.FB ) {
						clearInterval( timer );
						if ( window.FB ) {
							defer.resolve();
						}
					}
				}, 1000 );
				return;
			}

			// Set callback handler.

			window.fbAsyncInit = function() {
				FB.init( {
					version: 'v2.9', // https://developers.facebook.com/docs/apps/changelog/#versions
					status: false,
					xfbml: false
				} );
				defer.resolve( FB );
				delete window.fbAsyncInit;
			};

			// Attach script to the document.
			fjs = d.getElementsByTagName( s )[ 0 ];
			js = d.createElement( s );
			js.id = id;
			js.src = '//connect.facebook.net/en_US/sdk.js';
			fjs.parentNode.insertBefore( js, fjs );
		} );
	},

	/**
	 * Parse Facebook XFBML tags found in the document.
	 */
	fbParseXFBML() {
		let unrenderedLength = $( '.fb-post, .fb-video' ).not( '[fb-xfbml-state=rendered]' ).length;

		// Parse unrendered XFBML tag.
		if ( unrenderedLength ) {
			this.fbLoadSDK().done( function() {
				FB.XFBML.parse();
			} );
		}
	},

	/**
	 * Copy string to a clipbroad.
	 *
	 * @param {string} str
	 */
	copyToClipboard( str ) {
		const el = document.createElement( 'textarea' );

		el.value = str;
		document.body.appendChild( el );
		el.select();
		document.execCommand( 'copy' );
		document.body.removeChild( el );
	}
};
