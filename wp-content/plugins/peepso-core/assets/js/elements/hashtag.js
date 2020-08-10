import { observer } from 'peepso';
import peepsodata from 'peepsodata';

const hashtagsData = peepsodata.hashtags || {};

const HASHTAG_URL = hashtagsData.url;
const HASHTAG_EVERYTHING = +hashtagsData.everything || 0;
const HASHTAG_MIN_LENGTH = HASHTAG_EVERYTHING ? 0 : +hashtagsData.min_length || 0;
const HASHTAG_MAX_LENGTH = HASHTAG_EVERYTHING ? 10000 : +hashtagsData.max_length || 10000;
const HASHTAG_MUST_START_WITH_LETTER = HASHTAG_EVERYTHING
	? 0
	: +hashtagsData.must_start_with_letter || 0;

// Build hashtag pattern based on above configuration.
const HASHTAG_PATTERN = (() => {
	let startWithLetter, minLength, maxLength, pattern;

	if (HASHTAG_EVERYTHING) {
		pattern = '(^|>|\\s)(#([^#\\s<]+))';
	} else {
		startWithLetter = HASHTAG_MUST_START_WITH_LETTER;
		minLength = Math.max(0, HASHTAG_MIN_LENGTH - (startWithLetter ? 1 : 0));
		maxLength = Math.max(0, HASHTAG_MAX_LENGTH - (startWithLetter ? 1 : 0));

		pattern =
			'(^|>|\\s)(#(' +
			(startWithLetter ? '[a-z]' : '') +
			'[a-z0-9]{' +
			minLength +
			',' +
			maxLength +
			'}' +
			'))';
	}

	return new RegExp(pattern, 'ig');
})();

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

function filterContent(html) {
	if (html.match(HASHTAG_PATTERN)) {
		html = html.replace(HASHTAG_PATTERN, function(match, before, hashtag, label) {
			let newHtml = `${before}<a href="${HASHTAG_URL}${label}/"><span class="ps-stream-hashtag">${hashtag}</span></a>`;
			return newHtml;
		});
	}
	return html;
}

function scanElement(rootElement) {
	let elements = rootElement.querySelectorAll('*');

	// Add the root element to the element list.
	elements = [rootElement, ...elements];

	// Iterate through all elements.
	elements.forEach(element => {
		// Skip non-relevant elements.
		if (SKIP_TAGS.indexOf(element.tagName.toLowerCase()) > -1) {
			return;
		}

		let childNodes = [...element.childNodes];

		// Iterate through the element's childNodes.
		childNodes.forEach(node => {
			let text, replacer;

			// Skip non-text nodes.
			// https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
			if (node.nodeType !== 3) {
				return;
			}

			// Skip empty text nodes.
			text = node.textContent;
			if (!text.trim()) {
				return;
			}

			// Skip if it does not contain mention tags.
			if (!text.match(HASHTAG_PATTERN)) {
				return;
			}

			// Generate nodes to replace the text node.
			replacer = document.createElement('div');
			replacer.innerHTML = filterContent(text);

			// Replace text node with new nodes.
			// https://developer.mozilla.org/en-US/docs/Web/API/ChildNode/replaceWith
			node.replaceWith.apply(node, replacer.childNodes);

			// Update childNodes list.
			childNodes = childNodes.concat([...replacer.childNodes]);
		});
	});
}

function init() {
	// Do not run if hashtag feature is disabled.
	if (!hashtagsData.url) {
		return;
	}

	// Scan and replace every activity items added.
	observer.addFilter(
		'peepso_activity',
		$posts =>
			$posts.map(function() {
				if (this.nodeType === 1) {
					scanElement(this);
				}
				return this;
			}),
		10,
		1
	);

	// Render hashtags in blogpost.
	jQuery('.peepso-wp-post-hashtags').each(function() {
		scanElement(this);
	});
}

export default { init };
