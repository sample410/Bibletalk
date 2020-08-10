import $ from 'jquery';
import Promise from 'promise/lib/es6-extensions';
import { currentuserid as user_id, userid as view_user_id } from 'peepsodata';

let queue = [];
let saving = false;

const saveNotification = (fieldname, value, priority = false) => {
	return new Promise(resolve => {
		let item = [fieldname, value, resolve, priority];
		priority ? queue.unshift(item) : queue.push(item);
		setTimeout(saveExecQueue, 500);
	});
};

const saveExecQueue = () => {
	if (!queue.length) {
		return;
	}

	if (saving) {
		return;
	}

	let item = queue.shift();
	let fieldname = item[0];
	let value = item[1];
	let resolves = [item[2]];
	let priority = item[3];
	let params = { user_id, view_user_id, fieldname, value };

	if (!priority && queue.length) {
		item = queue.shift();
		params.fieldname_extra = item[0];
		params.value_extra = item[1];
		resolves.push(item[2]);
	}

	saving = true;
	peepso.postJson('profilepreferencesajax.save_notifications', params, json => {
		saving = false;
		resolves.forEach(resolve => resolve(json));
		saveExecQueue();
	});
};

const toggleEmailOpts = (show = true) => {
	let $nofifHeader = $('.ps-js-preferences-header');
	let $emailBtns = $('.ps-preferences-notifications__menu-item').filter('[data-type=email]');
	let $emailChks = $('.ps-preferences__checkbox').children('[data-type=email]');

	if (show) {
		$nofifHeader.parent('.ps-form-controls').show();
		$emailBtns.show();
		$emailChks.show();
	} else {
		$nofifHeader.parent('.ps-form-controls').hide();
		$emailBtns.hide();
		$emailChks.hide();
	}
};

const togglePairedOpt = checkbox => {
	let $self = $(checkbox);
	let $pair = $self
		.closest('.ps-preferences__checkbox')
		.find('[type=checkbox]')
		.not($self);

	if (!$pair.length) {
		return;
	}

	// Do not proceed if the state is already the same.
	if ($pair[0].checked === $self[0].checked) {
		return;
	}

	// - Onsite notification should be enabled when email notification is enabled.
	// - Email notification should be disabled when onsite notification is disabled.
	let selfType = $self.closest('span[data-type]').data('type');
	if (
		('onsite' === selfType && !$self[0].checked) ||
		('email' === selfType && $self[0].checked)
	) {
		$pair.trigger('click');
	}
};

$(function() {
	// Handle change on nofification config.
	$('.ps-js-profile-list').on('change', '[type=checkbox]', function() {
		let $el = $(this);
		let $loading = $el.closest('.ps-form-controls').find('.ps-js-loading');
		let $loadingProgress = $loading.find('img');
		let $loadingComplete = $loading.find('i');

		// Show loading.
		$loadingComplete.stop().hide();
		$loadingProgress.show();

		saveNotification($(this).attr('name'), this.checked ? 1 : 0).then(function() {
			// Hide loading.
			$loadingProgress.hide();
			$loadingComplete
				.show()
				.delay(800)
				.fadeOut();
		});

		togglePairedOpt(this);
	});

	let $eni = $('select[name=email_intensity]');

	// Fix "profilepreferencesajax.savepreference" endpoint is accidentally triggered.
	$eni.off('change.savepref');

	$eni.on('change', function() {
		let value = this.value;
		let $loading = $eni.next('.ps-js-loading');
		let $loadingProgress = $loading.find('img');
		let $loadingComplete = $loading.find('i');

		// Show loading.
		$loadingComplete.stop().hide();
		$loadingProgress.show();

		saveNotification('email_intensity', value, true).then(json => {
			// Hide loading.
			$loadingProgress.hide();
			$loadingComplete
				.show()
				.delay(800)
				.fadeOut();

			if (json.success) {
				// Update description for the selected option.
				let $descs = $('#peepso_email_intensity_descriptions').children();
				let $desc = $descs.filter('#peepso_email_intensity_' + value);
				if ($desc.length) {
					$descs.not($desc).hide();
					$desc.show();
				}

				// Toggle email notification options.
				toggleEmailOpts(0 === +value);
			}
		});
	});

	// Also toggle email notification options on page load.
	if ($eni.length) {
		toggleEmailOpts(0 === +$eni.val());
	}

	// Make sure email notification checkbox states is consistent with onsite nofification checkbox states.
	$('.ps-preferences__checkbox')
		.children('[data-type=onsite]')
		.find('[type=checkbox]')
		.each(function() {
			togglePairedOpt(this);
		});
});
