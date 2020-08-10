import $ from 'jquery';

$(function() {
	let $verify = $('input[name=verify_password]');
	if (!$verify.length) {
		return;
	}

	let cssReadOnly = 'ps-input-readonly',
		$form = $verify.closest('form'),
		$fields = $form.find('input[type=text], input[type=password]').not($verify),
		$save = $form.find('[type=submit]');

	// Save initial field values.
	$fields.each(function() {
		$(this).data('ps-value', this.value);
	});

	// Determine whether the form field values are changed.
	function isChanged() {
		let changed = false;
		$fields.each(function() {
			if (this.value !== $(this).data('ps-value')) {
				changed = true;
				return false;
			}
		});
		return changed;
	}

	// Enable/disable editing form field values.
	function toggleEditing() {
		let value = $verify.val().trim();
		if (value.length < 5) {
			$fields.attr('readonly', 'readonly').addClass(cssReadOnly);
			$save.attr('disabled', 'disabled').addClass(cssReadOnly);
		} else {
			$fields.removeAttr('readonly').removeClass(cssReadOnly);
			if (!isChanged()) {
				$save.attr('disabled', 'disabled').addClass(cssReadOnly);
			} else {
				$save.removeAttr('disabled').removeClass(cssReadOnly);
			}
		}
	}

	// Rmove invalid characters on username field.
	$fields.filter('input[name=user_nicename]').on('input', function() {
		var sanitized = this.value.replace(/[^a-z0-9-_\.@]/gi, '');
		if (this.value !== sanitized) {
			this.value = sanitized;
		}
	});

	// Handle form fields input event.
	$verify.on('input', toggleEditing);
	$fields.on('input', toggleEditing);

	// Toggle editing state on page load.
	toggleEditing();
});
