import $ from 'jquery';
import { observer, modules } from 'peepso';
import { elements as elementsData } from 'peepsodata';

let postData = ( elementsData && elementsData.post ) || {};

const TEXT_SAVE = postData.text_save;
const TEXT_SAVED = postData.text_saved;
const HTML_SAVED_NOTICE = postData.html_saved_notice;

let $tooltip = null;

/**
 * Disable button.
 *
 * @param {Element} button
 */
function disable( button ) {
	button.style.opacity = 0.3;
	button.removeEventListener( 'click', toggleHandler );
}

/**
 * Enable button.
 *
 * @param {Element} button
 * @param {Object} data
 */
function enable( button, data = {} ) {
	button.style.opacity = '';
	button.addEventListener( 'click', toggleHandler );

	if ( +data.saved ) {
		button.setAttribute( 'data-object-id', data.id );
	}
}

function toggle( button, saved ) {
	let className = button.className,
		label = button.querySelector( 'span' );

	if ( saved ) {
		button.className = className.replace( 'ps-icon-bookmark-empty', 'ps-icon-bookmark' );
		label.innerHTML = TEXT_SAVED;
	} else {
		button.className = className.replace( /ps-icon-bookmark(?!-empty)/, 'ps-icon-bookmark-empty' );
		label.innerHTML = TEXT_SAVE;
	}
}

function toggleHandler( e ) {
	let button = e.currentTarget,
		saved = -1 === button.className.indexOf( 'ps-icon-bookmark-empty' ),
		id = +button.getAttribute( saved ? 'data-object-id' : 'data-stream-id' );

	e.preventDefault();
	e.stopPropagation();

	disable( button );
	toggle( button, ! saved );

	// Update state.
	modules.post.save( id, ! saved ).then( function( json ) {
		let notice = false;

		enable( button, json );

		// Display a message in a popup once a post is saved.
		if ( !! json.saved ) {
			notice = observer.applyFilters( 'post_saved_notice', true );
		}

		if ( notice ) {
			let offset = $( button ).offset(),
				height = $( button ).height();

			if ( ! $tooltip ) {
				$tooltip = $( '<div/>' ).html( HTML_SAVED_NOTICE );
				$tooltip.css( { position: 'absolute' } );
				$tooltip.appendTo( document.body );
			}

			$tooltip.stop().show();
			$tooltip.css( {
				top: Math.round( offset.top - $tooltip.height() / 2 + height / 2 ),
				left: Math.round( offset.left - $tooltip.width() - 10 )
			} );

			// Fade out after 2 seconds.
			$tooltip.delay( 2000 ).fadeOut();
		} else {
			$tooltip && $tooltip.stop().hide();
		}
	} );
}

function initActions( actions ) {
	let button = actions.querySelector( '.ps-js-save-toggle' );
	if ( ! button ) {
		return;
	}

	// Start with disabled button.
	disable( button );

	let id = +button.getAttribute( 'data-stream-id' );
	if ( ! id ) {
		return;
	}

	// Check initial state.
	modules.post.save( id ).then( function( json ) {
		// Enable the button when done.
		enable( button, json );
		toggle( button, !! json.saved );
	} );
}

function initPost( postElement ) {
	let actions = postElement.querySelector( '.ps-stream-actions' );
	if ( ! actions ) {
		return;
	}

	let button = actions.querySelector( '.ps-js-save-toggle' );
	if ( ! button ) {
		return;
	}

	// Remove the button if its not in the post view.
	if ( button.closest && ! button.closest( '.ps-js-activity' ) ) {
		button.remove();
		return;
	}

	// Initialize action buttons.
	initActions( actions );
}

function init() {
	// Initialize on each activity item added.
	observer.addFilter(
		'peepso_activity',
		$posts =>
			$posts.map( function() {
				if ( this.nodeType === 1 ) {
					initPost( this );
				}
				return this;
			} ),
		10,
		1
	);

	// Initialize activity actions.
	observer.addAction(
		'peepso_activity_actions',
		$actions => {
			$actions.map( function() {
				if ( this.nodeType === 1 ) {
					initActions( this );
				}
				return this;
			} );
		},
		10,
		1
	);
}

export default { init };
