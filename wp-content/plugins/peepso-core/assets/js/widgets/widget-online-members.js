(function($, factory) {
	var onlineMembers = factory($);

	peepso.widget = peepso.widget || {};
	peepso.widget.onlineMembers = onlineMembers;

	// Initialize on document loaded.
	$($.proxy(onlineMembers.init, onlineMembers));
})(jQuery, function($) {
	return {
		/**
		 * Initialize member widgets.
		 */
		init: function() {
			var initialized = 'ps-js-initialized',
				$widgets = $('.ps-js-widget-online-members').not('.' + initialized);

			$widgets.each(
				$.proxy(function(index, elem) {
					var $widget = $(elem).addClass(initialized),
						$content = $widget.find('.ps-js-widget-content'),
						hideEmpty = +$widget.data('hideempty'),
						showTotalMember = +$widget.data('totalmember'),
						showTotalOnline = +$widget.data('totalonline'),
						limit = +$widget.data('limit');

					this.getData(limit, showTotalMember, showTotalOnline).done(function(
						html,
						isEmpty
					) {
						if (isEmpty && hideEmpty) {
							$content.empty();
							$widget.hide();
						} else {
							$content.html(html);
							$widget.show();
						}
					});
				}, this)
			);
		},

		/**
		 * Get member listing.
		 * @param {number} limit
		 * @param {boolean} showTotalMember
		 * @param {boolean} showTotalOnline
		 * @return {jQuery.Deferred}
		 */
		getData: function(limit, showTotalMember, showTotalOnline) {
			return $.Deferred(function(defer) {
				var url = 'widgetajax.online_members',
					params = {};

				// Delay data fetching to give time for more important Ajax requests.
				setTimeout(function() {
					params.limit = +limit;
					params.totalmember = showTotalMember ? 1 : 0;
					params.totalonline = showTotalOnline ? 1 : 0;
					peepso.getJson(url, params, function(json) {
						if (json.success) {
							defer.resolve(json.data.html, +json.data.empty);
						} else {
							defer.reject();
						}
					});
				}, 3000);
			});
		}
	};
});
