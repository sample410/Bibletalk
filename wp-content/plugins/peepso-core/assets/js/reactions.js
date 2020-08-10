(function($, factory) {
	reactions = new (factory($))();
})(jQuery, function($) {
	var $loadingImg = '<img src="' + peepsoreactionsdata.ajaxloader + '"/>';

	function PsReactions() {
		var $doc = $(document.body);

		$doc.on('keyup', $.proxy(this._on_document_keyup, this));
		$doc.on('click', $.proxy(this._on_document_click, this));
	}

	PsReactions.prototype.action_reactions = function(elem, act_id) {
		var $toggle = $('.ps-reaction-toggle--' + act_id);
		var $options = $('.ps-js-act-reactions-options--' + act_id);
		var $delete = $('.ps-reaction-option-delete--' + act_id);
		var $items = $options.find('.ps-reaction-option').not('.ps-reaction-option-delete');
		var $list = $options.find('.ps-reactions__list');

		if ($items.length === 1) {
			if ($toggle.hasClass('liked')) {
				$delete.click();
			} else {
				$items.click();
			}
			return;
		}

		if ($toggle.hasClass('liked')) {
			$delete.show();
			$list.addClass('ps-reactions__list--selected');
		} else {
			$delete.hide();
			$list.removeClass('ps-reactions__list--selected');
		}

		$options.fadeToggle(200);
	};

	PsReactions.prototype.action_react = function(elem, act_id, post_id, react_id) {
		if (this.action_react_progress) return;
		this.action_react_progress = true;

		var req = { act_id: act_id, post_id: post_id, react_id: react_id };
		var that = this;

		var $reactions_output = $('.ps-js-act-reactions--' + act_id);

		// hide the reactions-options box
		$('.ps-js-act-reactions-options--' + act_id).fadeOut(200);

		$like_span = $('.ps-reaction-toggle--' + act_id + ':first-child');

		$like_a = $('.ps-reaction-toggle--' + act_id);
		$like_a.removeClass();
		$like_a.addClass('ps-reaction-toggle--' + act_id + ' ps-js-reaction-toggle');
		$like_a.addClass('ps-icon-reaction');
		$like_a.addClass('liked');
		//$like_a.addClass(json.data.reaction_mine_class);
		$like_a.addClass('ps-reaction-emoticon-' + react_id);

		$reactions_output.animate({ opacity: 0.5 });

		$like_span.html($loadingImg);

		peepso.postJson('reactionsajax.react', req, function(json) {
			var hiddenClass = 'ps-stream-reactions-hidden';

			that.action_react_progress = false;

			if (json.success) {
				if ($.isNumeric(json.data.reaction_mine_id)) {
					$like_span.html('<span>' + json.data.reaction_mine_label + '<span>');
				}

				// update the reactions html
				if (json.data.html_reactions) {
					$reactions_output.removeClass(hiddenClass);
					$reactions_output.html(json.data.html_reactions).animate({ opacity: 1 });
				} else {
					$reactions_output.addClass(hiddenClass);
					$reactions_output.html('');
				}
			} else {
				alert('Something went wrong');
			}
		});
	};

	PsReactions.prototype.action_react_delete = function(elem, act_id, post_id) {
		if (this.action_react_progress) return;
		this.action_react_progress = true;

		var req = { act_id: act_id, post_id: post_id };
		var that = this;

		var $reactions_output = $('.ps-js-act-reactions--' + act_id);

		// unbold all reaction options
		$('.ps-reaction-option--' + act_id).removeClass('ps-reaction-option-selected');

		// hide the reactions-options box
		$('.ps-js-act-reactions-options--' + act_id).fadeOut(200);

		$like_span = $('.ps-reaction-toggle--' + act_id + ':first-child');
		$like_span.html($loadingImg);

		$like_a = $('.ps-reaction-toggle--' + act_id);
		$like_a.removeClass();
		$like_a.addClass('ps-reaction-toggle--' + act_id + ' ps-js-reaction-toggle');
		$like_a.addClass('ps-icon-reaction');
		$like_a.removeClass('liked');
		$like_a.addClass('ps-reaction-emoticon-0');

		$reactions_output.animate({ opacity: 0.5 });

		peepso.postJson('reactionsajax.react_delete', req, function(json) {
			var hiddenClass = 'ps-stream-reactions-hidden';

			that.action_react_progress = false;

			if (json.success) {
				if (!$.isNumeric(json.data.reaction_mine_id)) {
					$like_span.html('<span>' + json.data.reaction_mine_label + '<span>');
				}

				// update the reactions html
				if (json.data.html_reactions) {
					$reactions_output.removeClass(hiddenClass);
					$reactions_output.html(json.data.html_reactions).animate({ opacity: 1 });
				} else {
					$reactions_output.addClass(hiddenClass);
					$reactions_output.html('');
				}
			} else {
				alert('Something went wrong');
			}
		});
	};

	PsReactions.prototype.action_html_reactions = function(elem, act_id) {
		if (this.action_react_progress) return;
		this.action_react_progress = true;

		var req = { act_id: act_id };
		var that = this;

		var $reactions_output = $('.ps-js-act-reactions--' + act_id);
		$reactions_output.html($reactions_output.html() + ' ' + $loadingImg);
		$reactions_output.animate({ opacity: 0.5 });

		peepso.postJson('reactionsajax.html_reactions', req, function(json) {
			that.action_react_progress = false;
			if (json.success) {
				$reactions_output.html(json.data.html_reactions).animate({ opacity: 1 });
			} else {
				alert('Something went wrong');
			}
		});
	};

	PsReactions.prototype.action_html_reactions_details = function(elem, act_id, post_id) {
		if (this.action_react_progress) return;
		this.action_react_progress = true;

		var req = { act_id: act_id };
		var that = this;

		var $reactions_output = $('.ps-js-act-reactions--' + act_id),
			originalContent = $reactions_output.html();

		$reactions_output.animate({ opacity: 0.5 });
		$reactions_output.html(originalContent + ' ' + $loadingImg);

		peepso.postJson('reactionsajax.html_reactions_details', req, function(json) {
			that.action_react_progress = false;
			$reactions_output
				.html(json.success ? json.data.html_reactions : originalContent)
				.animate({ opacity: 1 });
		});
	};

	PsReactions.prototype._on_document_keyup = function(e) {
		if (e.keyCode === 27) {
			$('.ps-js-reaction-options:visible').fadeOut(200);
		}
	};

	PsReactions.prototype._on_document_click = function(e) {
		var $target = $(e.target).closest('.ps-js-reaction-options, .ps-js-reaction-toggle'),
			$act,
			$options;

		if (!$target.length) {
			$('.ps-js-reaction-options:visible').fadeOut(200);
		} else {
			$act = $target.closest('.ps-stream-actions');
			$options = $act.nextAll('.ps-js-reaction-options');
			$('.ps-js-reaction-options:visible')
				.not($options)
				.fadeOut(200);
		}
	};

	return PsReactions;
});
