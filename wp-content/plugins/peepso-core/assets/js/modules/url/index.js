/**
 * URL content fetching module.
 *
 * @module url
 * @example
 * let { getContent } = peepso.modules.url;
 *
 * getEmbedData( 'https://www.google.com' )
 *     .then( data => console.log( data ) )
 *     .catch( error => console.error( error ) );
 */
export { getEmbed } from './get-embed';
