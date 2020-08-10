(function($, peepso, factory) {
	/**
	 * PsComment global instance.
	 * @name peepso.comment
	 * @type {PsComment}
	 */
	peepso.comment = new (factory($, peepso, peepso.modules))();
})(jQuery, peepso, function($, peepso, modules) {
	/**
	 * Handle commenting.
	 * @class PsComment
	 */
	function PsComment() {
		this.init();
	}

	/**
	 * Triggers "human-friendly" ajax calls for a specific comment.
	 *
	 * @param {HTMLInputElement} hidden
	 */
	function humanFriendly(hidden) {
		var $hidden = $(hidden),
			$content = $hidden.siblings('.ps-comment-message'),
			$comment = $content.closest('.ps-comment-item'),
			isAdmin = +peepsodata.is_admin,
			canSubmit = isAdmin || +$hidden.data('author') === +peepsodata.currentuserid,
			content,
			extras;

		if (canSubmit) {
			content = $content
				.find('.ps-comment__content')
				.get(0)
				.innerText.trim();

			extras = peepso.observer.applyFilters(
				'human_friendly_extras',
				[],
				content,
				$comment[0]
			);

			// Append extra informations to the content string.
			if (extras.length) {
				if (content) {
					extras.unshift(content);
				}
				content = extras.join('. ');
			}

			content = content.replace(/\r?\n/g, ' ');
			modules.post.setHumanReadable($hidden.val(), content);
		}
	}

	peepso.npm.objectAssign(
		PsComment.prototype,
		/** @lends PsComment.prototype */ {
			/**
			 * Initialize commenting.
			 */
			init: function() {
				this.ajax = {};

				// reveal comment on single activity view which ids are defined in the url hash
				// for example `#comment.00.11.22.33` will be translated as follow:
				//   00 = post's act_id
				//   22 = comment's post_id
				//   11 = comment's act_id
				//   33 = reply's act_id (optional, if you want to show reply)
				$(
					$.proxy(function() {
						var hash = window.location.hash || '',
							// Check for `#comment.00.11.22.33` pattern in URL.
							// Also accept `#comment=00.11.22.33` pattern as fallback for older URLs.
							reComment = /[#&]comment(?:\.|=)(\d+)\.(\d+)(?:\.(\d+)(?:\.(\d+))?)?/,
							data = hash.match(reComment);

						if (data && data[2]) {
							this.whenLoaded(data[1]).done(
								$.proxy(function() {
									this.reveal(data[1], data[2], data[3], data[4]);
								}, this)
							);
						}
					}, this)
				);

				peepso.observer.addAction(
					'post_loaded',
					function(element) {
						var $post = $(element);
						var $textarea = $post.find('textarea[name=comment]');

						// Initialize autosize.
						$textarea.ps_autosize();

						// Initialize droppable elements.
						$textarea.each(function() {
							var textarea = this;
							peepso.elements.droppable(textarea, {
								dropped: function(files) {
									peepso.observer.doAction(
										'commentbox_drop_files',
										textarea,
										files
									);
								}
							});
						});

						// Send human-friendly content back to server if a flag is exist.
						$post
							.find('.ps-comment-body input[name=peepso_set_human_friendly]')
							.each(function() {
								humanFriendly(this);
							});

						// Handle comment link.
						$post.on('click', '.ps-comment-time .activity-post-age', function(e) {
							$(e.currentTarget)
								.children('a')
								.each(function() {
									var $link = $(this);
									if ($link.children('.ps-js-autotime').length) {
										var destHref = $link.attr('href');
										var currHref = location.href.replace(location.hash, '');
										if (0 === destHref.indexOf(currHref)) {
											window.location = destHref;
											window.location.reload();
										}
									}
								});
						});
					},
					10,
					1
				);

				// Filter activity items.
				// peepso.observer.addFilter( 'peepso_activity', function( $posts ) {
				// 	return $posts.map(function() {
				// 		var $post = $( this );
				// 		var $comments = $post.find( '.ps-js-comment-container' );
				// 		var commentsOpen = 1;

				// 		// Hide new comments and reply button if comments are disabled.
				// 		if ( $comments.length ) {
				// 			commentsOpen = +$comments.data( 'comments-open' );
				// 			if ( ! commentsOpen ) {
				// 				$comments.next( '.ps-js-comment-new' ).remove();
				// 				$comments.find( '.ps-comment-links .actaction-reply' ).remove();
				// 			}
				// 		}

				// 		return this;
				// 	});
				// }, 20, 1 );
			},

			/**
			 * Watch if parent activity is already loaded.
			 * @param {number} id
			 * @return {jQuery.Deferred}
			 */
			whenLoaded: function(id) {
				return $.Deferred(function(defer) {
					var maxLoops = 60,
						countLoops = 0,
						timer;

					// Watch post availability.
					timer = setInterval(function() {
						if ($('.ps-js-activity--' + id).length) {
							clearInterval(timer);
							defer.resolve();
						} else if (countLoops++ > maxLoops) {
							clearInterval(timer);
							defer.reject();
						}
					}, 1000);
				});
			},

			/**
			 * TODO: docblock
			 */
			add: function() {},

			/**
			 * TODO: docblock
			 */
			edit: function() {},

			/**
			 * Reply to a comment.
			 */
			reply: function(act_id, post_id, elem, data) {
				var $comment,
					$btn,
					$container,
					$textarea,
					nested,
					parentID = '#comment-item-' + post_id;

				if (elem) {
					$comment = $(elem).closest(parentID);
				} else {
					$comment = $(parentID);
				}

				$btn = $comment.find('.actaction-reply');
				nested = $btn.closest('.ps-comment').hasClass('ps-comment-nested');

				if (nested) {
					$container = $btn.closest('.ps-comment').children('.ps-comment-reply');
					$textarea = $container.find('textarea');
				} else {
					$container = $btn
						.closest('.ps-comment-item')
						.next('.ps-comment-nested')
						.children('.ps-comment-reply');
					$textarea = $container.find('textarea');
				}

				if ($container.not(':visible')) {
					$container.show();
				}

				$textarea.focus();

				data = data || {};
				peepso.observer.applyFilters(
					'comment.reply',
					$textarea,
					$.extend({}, data, { act_id: act_id, post_id: post_id })
				);

				$textarea
					.off('keyup.peepso')
					.on('keyup.peepso', function(e) {
						e.stopPropagation();
						activity.update_beautifier(e.target);
					})
					.trigger('keyup.peepso');
			},

			/**
			 * TODO: docblock
			 */
			show_previous: function(act_id, elem) {
				var $ct,
					$more,
					$loading,
					parentID = '.ps-js-comment-container--' + act_id;

				if (elem) {
					$ct = $(elem).closest(parentID);
				} else {
					$ct = $(parentID);
				}

				$more = $ct.find('.ps-js-comment-more').eq(0);
				$loading = $more.find('.ps-js-loading');

				function getPrevious(callback) {
					var $first = $ct.children('.cstream-comment:first');

					$loading.removeClass('hidden');
					peepso.postJson(
						'activity.show_previous_comments',
						{
							act_id: act_id,
							uid: peepsodata.currentuserid,
							first: $first.data('comment-id')
						},
						function(json) {
							// Filter posts.
							var $wrapper = jQuery('<div />').append(json.data.html);
							$wrapper = peepso.observer.applyFilters('peepso_activity', $wrapper);
							var html = peepso.observer.applyFilters(
								'peepso_activity_content',
								$wrapper.html()
							);

							// Manually fix problem with WP Embed as described here:
							// https://core.trac.wordpress.org/ticket/34971
							html = html.replace(
								/\/embed\/(#\?secret=[a-zA-Z0-9]+)?"/g,
								'/?embed=true$1"'
							);

							// Send human-friendly content back to server if a flag is exist.
							$(html)
								.find('.ps-comment-body input[name=peepso_set_human_friendly]')
								.each(function() {
									humanFriendly(this);
								});

							$loading.addClass('hidden');
							if ($first.length == 0) {
								$first = $ct.children('.ps-comment-more');
								$first.after(html);
							} else {
								$first.before(html);
							}
							if (json.data.comments_remain > 0) {
								$more.find('a').html(json.data.comments_remain_caption);
							} else {
								$more.remove();
							}
							$(document).trigger('ps_comment_added');
							callback();
						}
					);
				}

				return $.Deferred(function(defer) {
					getPrevious(function() {
						defer.resolve();
					});
				});
			},

			/**
			 * TODO: docblock
			 */
			show_all: function(act_id) {
				var $ct = $('.ps-js-comment-container--' + act_id),
					$more = $ct.children('.ps-js-comment-more'),
					$loading = $more.find('.ps-js-loading');

				function getPrevious(callback) {
					var $first = $ct.children('.cstream-comment:first');

					$loading.removeClass('hidden');
					peepso.postJson(
						'activity.show_previous_comments',
						{
							act_id: act_id,
							uid: peepsodata.currentuserid,
							all: 1,
							first: $first.data('comment-id')
						},
						function(json) {
							// Filter posts.
							var $wrapper = jQuery('<div />').append(json.data.html);
							$wrapper = peepso.observer.applyFilters('peepso_activity', $wrapper);
							var html = peepso.observer.applyFilters(
								'peepso_activity_content',
								$wrapper.html()
							);

							// Manually fix problem with WP Embed as described here:
							// https://core.trac.wordpress.org/ticket/34971
							html = html.replace(
								/\/embed\/(#\?secret=[a-zA-Z0-9]+)?"/g,
								'/?embed=true$1"'
							);

							// Send human-friendly content back to server if a flag is exist.
							$(html)
								.find('.ps-comment-body input[name=peepso_set_human_friendly]')
								.each(function() {
									humanFriendly(this);
								});

							$loading.addClass('hidden');
							if ($first.length == 0) {
								$first = $ct.children('.ps-comment-more');
								$first.after(html);
							} else {
								$first.before(html);
							}
							if (json.data.comments_remain > 0) {
								$more.find('a').html(json.data.comments_remain_caption);
								getPrevious(callback);
							} else {
								$more.remove();
								$(document).trigger('ps_comment_added');
								callback();
							}
						}
					);
				}

				return $.Deferred(function(defer) {
					getPrevious(function() {
						defer.resolve();
					});
				});
			},

			/**
			 * TODO: docblock
			 */
			reveal_comment: function(container_id, comment_id) {
				return $.Deferred(
					$.proxy(function(defer) {
						var $comment = $('#comment-item-' + comment_id);
						if ($comment.length) {
							defer.resolve();
						} else {
							this.show_all(container_id).done(
								$.proxy(function() {
									defer.resolve();
								}, this)
							);
						}
					}, this)
				);
			},

			/**
			 * TODO: docblock
			 */
			reveal: function(post_act_id, comment_post_id, comment_act_id, reply_act_id) {
				// hightligh and scroll to particular comment
				function highlight($comment) {
					var color = $comment
							.find('a.ps-comment-user')
							.eq(0)
							.css('color'),
						scrollTop =
							$comment.offset().top -
							($(window).height() - $comment.outerHeight()) / 2;
					$comment.css({ backgroundColor: color });
					$comment.css({ transition: 'background-color 2s ease' });
					$('html, body')
						.delay(50)
						.animate({ scrollTop: scrollTop }, 500, function() {
							$comment.css({ backgroundColor: '' });
						});
				}

				this.reveal_comment(post_act_id, comment_post_id).done(
					$.proxy(function() {
						var $comment;

						if (!reply_act_id) {
							$comment = $('#comment-item-' + comment_post_id);
							highlight($comment);
						} else {
							this.reveal_comment(comment_act_id, reply_act_id).done(function() {
								$comment = $('#comment-item-' + reply_act_id);
								highlight($comment);
							});
						}
					}, this)
				);
			}
		}
	);

	return PsComment;
});
