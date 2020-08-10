import $ from 'jquery';
import { observer, modules } from 'peepso';

const MENTION_PATTERN = /@peepso_([a-z]+)_(\d+)(?:\(([^\)]+)\))?/g;

const SKIP_TAGS = [
	'a',
	'area',
	'audio',
	'base',
	'br',
	'button',
	'code',
	'col',
	'embed',
	'frame',
	'hr',
	'iframe',
	'img',
	'input',
	'keygen',
	'link',
	'meta',
	'param',
	'script',
	'select',
	'source',
	'style',
	'textarea',
	'track',
	'video',
	'wbr'
];

let replaceCount = 0;

function filterContent( html ) {
	if ( html.match( MENTION_PATTERN ) ) {
		html = html.replace( MENTION_PATTERN, function( mention, type, id, name ) {
			let placeholderClass = `ps-js-mention-${ ++replaceCount }`,
				placeholderHtml = `<a class="${ placeholderClass }">${ name || mention }</a>`;

			maybeDelayGetHtml( placeholderClass, [ type, id, name ] );

			return placeholderHtml;
		} );
	}
	return html;
}

function scanElement( rootElement ) {
	let elements = rootElement.querySelectorAll( '*' );

	// Add the root element to the element list.
	elements = [ rootElement, ...elements ];

	// Iterate through all elements.
	elements.forEach( element => {
		// Skip non-relevant elements.
		if ( SKIP_TAGS.indexOf( element.tagName.toLowerCase() ) > -1 ) {
			return;
		}

		let childNodes = [ ...element.childNodes ];

		// Iterate through the element's childNodes.
		childNodes.forEach( node => {
			let text, replacer;

			// Skip non-text nodes.
			// https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
			if ( node.nodeType !== 3 ) {
				return;
			}

			// Skip empty text nodes.
			text = node.textContent;
			if ( ! text.trim() ) {
				return;
			}

			// Skip if it does not contain mention tags.
			if ( ! text.match( MENTION_PATTERN ) ) {
				return;
			}

			// Generate nodes to replace the text node.
			replacer = document.createElement( 'div' );
			replacer.innerHTML = filterContent( text );

			// Replace text node with new nodes.
			// https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/replaceWith
			node.replaceWith.apply( node, replacer.childNodes );

			// Update childNodes list.
			childNodes = childNodes.concat( [ ...replacer.childNodes ] );
		} );
	} );
}

function getHtml( elements, params ) {
	let [ type, id, name ] = params;

	modules.mention.getHtml( type, id, name ).then( html => {
		elements = [ ...document.getElementsByClassName( elements ) ];

		// Iterate through all elements.
		elements.forEach( element => {
			// Generate nodes to replace the text node.
			let replacer = document.createElement( 'div' );
			replacer.innerHTML = html;
			// Replace link placeholder with an actual one.
			[ ...replacer.childNodes ].forEach( node => {
				element.parentNode.insertBefore( node, element );
			} );
			element.parentNode.removeChild( element );
		} );
	} );
}

let requestDelay = true;
let requestQueue = [];

function maybeDelayGetHtml( elements, params ) {
	if ( requestDelay ) {
		requestQueue.push({ elements, params });
	} else {
		getHtml( elements, params );
	}
}

function init() {
	// Scan and replace every activity items added.
	observer.addFilter(
		'peepso_activity',
		$posts =>
			$posts.map( function() {
				if ( this.nodeType === 1 ) {
					scanElement( this );
				}
				return this;
			} ),
		10,
		1
	);

	$( () => {
		scanElement( document.body );

		// Delay fetching mention information to give time for more important Ajax requests.
		setTimeout( () => {
			requestDelay = false;

			// Execute queues.
			while( requestQueue.length ) {
				let { elements, params } = requestQueue.shift();
				getHtml( elements, params );
			}
		}, 3000 );
	} );
}

export default { init };
