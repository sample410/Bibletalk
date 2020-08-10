(function($, factory) {
	var PsPageMembers = factory($);
	var ps_page_members = new PsPageMembers('.ps-js-members');
})(jQuery, function($) {
	var HIDE_BEFORE_SEARCH = +peepsodata.members_hide_before_search;

	function PsPageMembers() {
		PsPageMembers.super_.apply(this, arguments);
		$($.proxy(this.init_page, this));
	}

	// inherit from `PsPageAutoload`
	peepso.npm.inherits(PsPageMembers, PsPageAutoload);

	peepso.npm.objectAssign(PsPageMembers.prototype, {
		onDocumentLoaded: function() {
			this._search_$ct = $(this._css_prefix).eq(0);
			this._search_$trigger = $(this._css_prefix + '-triggerscroll');
			this._search_$loading = $(this._css_prefix + '-loading');
			this._search_$nomore = $(peepsodata.activity.template_no_more)
				.hide()
				.insertBefore(this._search_$trigger);
		},

		init_page: function() {
			this._search_$query = $('.ps-js-members-query').on(
				'input',
				$.proxy(this._filter, this)
			);
			this._search_$gender = $('.ps-js-members-gender').on(
				'change',
				$.proxy(this._filter, this)
			);
			this._search_$sortby = $('.ps-js-members-sortby').on(
				'change',
				$.proxy(this._filter, this)
			);
			this._search_$avatar = $('.ps-js-members-avatar').on(
				'click',
				$.proxy(this._filter, this)
			);
			this._search_$following = $('.ps-js-members-following').on(
				'change',
				$.proxy(this._filter, this)
			);

			// Extended profile filters.
			this._search_$extended = $('.ps-js-filter-extended').find('input[type=radio], select');
			this._search_$extended.prop('oninput', '');
			this._search_$extended
				.filter('select')
				.addClass('ps-select')
				.on('change', $.proxy(this._filter, this));
			this._search_$extended.filter('input[type=radio]').on(
				'click',
				$.proxy(function(e) {
					var $input = $(e.target);
					if ($input.data('ps-checked')) {
						$input.removeData('ps-checked');
						$input[0].checked = false;
					} else {
						$input.data('ps-checked', 1);
					}
					this._filter();
				}, this)
			);

			// toggle search filter form
			$('.ps-form-search-opt').on('click', $.proxy(this._toggle, this));

			this._filter();
		},

		_search_url: 'membersearch.search',

		_search_params: {
			uid: peepsodata.currentuserid,
			user_id: peepsodata.userid,
			query: undefined,
			order_by: undefined,
			order: undefined,
			peepso_gender: undefined,
			peepso_avatar: undefined,
			peepso_following: undefined,
			limit: 2,
			page: 1
		},

		_search_render_html: function(data) {
			if (data.members && data.members.length) {
				return data.members.join('');
			}
			return '';
		},

		_search_get_items: function() {
			return this._search_$ct.children('.ps-members-item-wrapper');
		},

		/**
		 * @param {object} params
		 * @returns jQuery.Deferred
		 */
		_fetch: function(params) {
			return $.Deferred(
				$.proxy(function(defer) {
					// Multiply limit value by 2 which translate to 2 rows each call.
					params = $.extend({}, params);
					if (!_.isUndefined(params.limit)) {
						params.limit *= 2;
					}

					this._fetch_xhr && this._fetch_xhr.abort();
					this._fetch_xhr = peepso
						.disableAuth()
						.disableError()
						.getJson(
							this._search_url,
							params,
							$.proxy(function(response) {
								if (response.success) {
									defer.resolveWith(this, [response.data]);
								} else {
									defer.rejectWith(this, [response.errors]);
								}
							}, this)
						);
				}, this)
			);
		},

		/**
		 * Filter search based on selected elements.
		 */
		_filter: function() {
			var query = $.trim(this._search_$query.val()),
				sortby = this._search_$sortby.val().split('|'),
				gender = this._search_$gender.val(),
				avatar = this._search_$avatar[0].checked ? 1 : 0,
				following = this._search_$following[0].value,
				extended = {};

			// abort current request
			this._fetch_xhr && this._fetch_xhr.abort();

			if (HIDE_BEFORE_SEARCH && !query) {
				clearTimeout(this._search_debounced_timer);
				this._search_toggle_autoscroll('off');
				this._search_toggle_loading('hide');
				this._search_$ct.empty();
				this._search_$nomore.hide();
				return;
			}

			this._search_params.query = query || undefined;
			this._search_params.order_by = sortby[0] || undefined;
			this._search_params.order = sortby[1] || undefined;
			this._search_params.peepso_gender = gender || undefined;
			this._search_params.peepso_avatar = avatar || undefined;
			this._search_params.peepso_following = following || undefined;
			this._search_params.page = 1;

			// Increase the limit for recently online sort to avoid duplicate.
			if ('peepso_last_activity' === this._search_params.order_by) {
				this._search_params.limit = 25;
			} else {
				this._search_params.limit = 2;
			}

			// Add extended profile filters.
			this._search_$extended.each(function() {
				var $input = $(this);
				if ($input[0].tagName === 'SELECT') {
					extended[this.name] = this.value;
				} else if ($input.attr('type') === 'radio') {
					if (typeof extended[this.name] === 'undefined') {
						extended[this.name] = undefined;
					}
					if ($input[0].checked) {
						extended[this.name] = this.value;
					}
				}
			});
			_.extend(this._search_params, extended);

			this._search();
		},

		/**
		 * Toggle search filter form.
		 */
		_toggle: function() {
			$('.ps-js-page-filters')
				.stop()
				.slideToggle();
		}
	});

	return PsPageMembers;
});
