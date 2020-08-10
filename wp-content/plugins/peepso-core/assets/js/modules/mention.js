/**
 * Mention resolver module.
 *
 * @module mention
 * @example
 * let { getHtml } = peepso.modules.mention;
 *
 * getHtml( 'user', 99, 'User' )
 *     .then( data => console.log( data ) )
 *     .catch( error => console.error( error ) );
 */

import { Promise } from 'peepso';

/**
 * Mention HTML cache.
 *
 * @type {Object.<string>}
 * @private
 */
const cache = {};

/**
 * Cache age.
 */
const cacheAge = 60000; // 60 seconds.

/**
 * Mention HTML queue.
 *
 * @type {Object}
 */
let getHtmlQueue = {};

/**
 * Get the mention HTML.
 *
 * @param {string} type
 * @param {number} id
 * @param {string} [name]
 * @returns {Promise.<string,?string>}
 */
export function getHtml( type, id, name ) {
	return new Promise( ( resolve, reject ) => {
		let endpoint = 'mentionsajax.get_mention',
			params = { type, id, name },
			cacheKey = `${ type }_${ id }${ name ? '_' + name : '' }`,
			cacheData = cache[ cacheKey ],
			cacheHtml,
			cacheTime,
			transport,
			json;

		// Check cache data availability before performing ajax request.
		if ( cacheData ) {
			// Just add promise handler to callback queue if cache data is flagged as fetching.
			// Promise handler will be then executed by currently-running ajax request.
			if ( '__fetching__' === cacheData ) {
				getHtmlQueue[ cacheKey ] = getHtmlQueue[ cacheKey ] || [];
				getHtmlQueue[ cacheKey ].push([ resolve, reject ]);
				return;
			}

			// Remove expired cache data if needed.
			let prev = cacheData.time;
			let now = ( new Date ).getTime();
			if ( now - prev > cacheAge ) {
				delete cache[ cacheKey ];
				cacheData = cache[ cacheKey ];
			}

			// Immediately resolve if cache data is available.
			if ( cacheData ) {
				resolve( cacheData.html );
				return;
			}
		}

		// Flag it as fetching.
		cache[ cacheKey ] = '__fetching__';

		transport = peepso.postJson( endpoint, params ).ret;
		transport
			.done( resp => ( json = resp ) )
			.always( () => {
				// Clear fetching flag first.
				delete cache[ cacheKey ];

				if ( json && json.success && ! json.errors ) {
					if ( json.data && json.data.html ) {
						let html = json.data.html;

						// Update cache data.
						cache[ cacheKey ] = { html, time: ( new Date ).getTime() };

						// Resolve current request.
						resolve( html );

						// Also resolve queued requests.
						if ( getHtmlQueue[ cacheKey ] ) {
							while ( getHtmlQueue[ cacheKey ].length ) {
								let [ resolve, reject ] = getHtmlQueue[ cacheKey ].shift();
								resolve( html );
							}
							delete getHtmlQueue[ cacheKey ];
						}

						return;
					}
				}

				let error = ( json && json.errors && json.errors.join( '\n' ) ) || undefined;

				// reject current request.
				reject( error );

				// Also reject queued requests.
				if ( getHtmlQueue[ cacheKey ] ) {
					while ( getHtmlQueue[ cacheKey ].length ) {
						let [ resolve, reject ] = getHtmlQueue[ cacheKey ].shift();
						reject( error );
					}
					delete getHtmlQueue[ cacheKey ];
				}
			} );
	} );
}
