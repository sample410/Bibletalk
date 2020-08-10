/*
 * Interactions for share dialog box
 * @package PeepSo
 * @author PeepSo
 */

function escapeRegExp(string) {
	return string.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, '\\$1');
}

function replaceAll(find, replace, str) {
	return str.replace(new RegExp(find, 'g'), replace);
}

function PsShare() {}

window.share = new PsShare();

PsShare.prototype.share_url = function(url) {
	let $title, $content;

	url = encodeURIComponent(url);

	$title = jQuery('#share-dialog-title').html();

	$content = jQuery('#share-dialog-content').html();
	$content = replaceAll('--peepso-url--', url, $content);
	$content = jQuery($content);

	// Open share link in a "popup" instead of opening a new tab/window.
	$content
		.filter('.ps-list--share')
		.find('a.ps-list__item')
		.on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			let width = 550;
			let height = 400;
			let left = Math.max(0, (window.innerWidth - width) / 2 || 0);
			let top = Math.max(0, (window.innerHeight - height) / 2 || 0);
			let opts = [
				'toolbar=no',
				'location=no',
				'status=no',
				'menubar=no',
				'scrollbars=yes',
				'resizable=yes',
				`width=${width}`,
				`height=${height}`,
				`left=${left}`,
				`top=${top}`
			].join(',');

			window.open(this.href, 'targetWindow', opts);
			pswindow.hide();
		});

	pswindow.show($title, $content);

	return false;
};
