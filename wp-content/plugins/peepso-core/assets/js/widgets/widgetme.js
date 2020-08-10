(function($, factory) {
	$(function() {
		new (factory($))();
	});
})(jQuery, function($) {
	function PsWidgetMe() {
		this.$el = $('.ps-widget--profile');
		if (this.$el.length) {
			this.init();
		}
	}

	PsWidgetMe.prototype = {
		init: function() {
			var $notification = this.$el.find('.ps-widget--profile__notifications');
			if ($notification.length) {
				peepso.observer.doAction('notification_start');
			}

			this.$coverImage = this.$el.find('.ps-widget--profile__cover-image');
			if (this.$coverImage.length) {
				peepso.observer.addAction('profile_cover_update', this.coverUpdate, 10, 2, this);
			}
		},

		coverUpdate: function(id, imageUrl) {
			if (+id == +peepsodata.currentuserid) {
				this.$coverImage.css('background', 'url(' + imageUrl + ') no-repeat center center');
			}
		}
	};

	return PsWidgetMe;
});
