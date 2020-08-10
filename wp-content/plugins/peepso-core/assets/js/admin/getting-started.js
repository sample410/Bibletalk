jQuery(function( $ ) {
	var $div = $( '.psa-starter__content' ),
		$configs = $div.find( 'input[name], select[name]' ),
		$selects = $configs.filter( 'select' ),
		$checkboxes = $configs.filter( '[type=checkbox]' ),
		$images = $configs.filter('[type=hidden][data-type=image]'),
		$texts = $configs.filter( '[type=text]' ),
		ajaxUrlSet = 'configurationajax.set',
		ajaxUrlRemove = 'configurationajax.remove';

	// Handle comboboxes.
	$selects.on( 'change', function() {
		var $el = $( this ),
			$spinner = $el.next( 'span' ).hide(),
			$check = $spinner.next( 'i.fa-check' ).hide(),
			key = $el.attr( 'name' ),
			value = $el.val(),
			params = { key: key, value: value };

		$spinner.show();
		$check.hide();
		$configs.attr( 'disabled', 1 );
		peepso.getJson( ajaxUrlSet, params, function() {
			$spinner.hide();
			$check.show().delay( 500 ).fadeOut();
			$configs.removeAttr( 'disabled' );
		});
	});

	// Handle checkboxes.
	$checkboxes.on( 'click', function() {
		var $el = $( this ),
			$spinner = $el.next( 'span' ).hide(),
			$check = $spinner.next( 'i.fa-check' ).hide(),
			key = $el.attr( 'name' ),
			value = this.checked ? $el.val() : 0,
			params = { key: key, value: value };

		$spinner.show();
		$check.hide();
		$configs.attr( 'disabled', 1 );
		peepso.getJson( ajaxUrlSet, params, function() {
			$spinner.hide();
			$check.show().delay( 500 ).fadeOut();
			$configs.removeAttr( 'disabled' );
		});
	});

	// Handle text inputs.
	$texts.each(function() {
		var $el = $( this ),
			$spinner = $el.siblings( 'span' ).hide(),
			$check = $el.siblings( 'i.fa-check' ).hide(),
			$cancel = $el.siblings( '.ps-js-cancel' ),
			$save = $el.siblings( '.ps-js-save' ),
			initialValue = $el.val();

		$el.on( 'input', function() {
			$cancel.show();
			$save.show();
		}).on( 'keydown', function( e ) {
			if ( e.keyCode === 13 ) {
				e.preventDefault();
				e.stopPropagation();
				$save.click();
			}
		});

		$cancel.on( 'click', function() {
			$el.val( initialValue );
			$cancel.hide();
			$save.hide();
		});

		$save.on( 'click', function() {
			var params = {
				key: $el.attr( 'name' ),
				value: $el.val()
			};

			// Do not proceed if text input is currently disabled.
			if ( $el.attr( 'disabled' ) ) {
				return;
			}

			$spinner.show();
			$check.hide();
			$configs.attr( 'disabled', 1 );
			peepso.getJson( ajaxUrlSet, params, function() {
				$cancel.hide();
				$save.hide();
				$spinner.hide();
				$check.show().delay( 500 ).fadeOut();
				$configs.removeAttr( 'disabled' );
				initialValue = $el.val();
			});
		});
	});

	// Handle image inputs.
	$images.each(function() {
		var $el = $( this ),
			$img = $el.siblings( '.ps-js-img' ),
			$select = $el.siblings( '.ps-js-select' ),
			$remove = $el.siblings( '.ps-js-remove' ),
			$notice = $el.siblings( '.ps-js-notice' ),
			$spinner = $el.siblings( 'span' ).not( $notice ).hide(),
			$check = $el.siblings( 'i.fa-check' ).hide();

		function beforeUpdate() {
			$spinner.show();
			$check.hide();
			$select.attr( 'disabled', 1 );
			$remove.attr( 'disabled', 1 );
			$remove.hide();
			$notice.hide();
		}

		function afterUpdate() {
			$spinner.hide();
			$check.show().delay( 500 ).fadeOut(function() {
				$select.removeAttr( 'disabled' );
				$remove.removeAttr( 'disabled' );
				if ( $el.val() ) {
					$notice.hide();
					$remove.show();
				} else {
					$remove.hide();
					$notice.show();
				}
			});
		}

		$select.on( 'click', function() {
			wp.media.editor.send.attachment = function( props, attachment ) {
				var params = {
					key: $el.attr( 'name' ),
					value: attachment.url
				};

				beforeUpdate();
				peepso.getJson( ajaxUrlSet, params, function() {
					$el.val( params.value );
					$img.attr( 'src', params.value );
					afterUpdate();
				});
			};
			wp.media.editor.open();
		});

		$remove.on( 'click', function() {
			var params = {
				key: $el.attr( 'name' )
			};

			beforeUpdate();
			peepso.getJson( ajaxUrlRemove, params, function() {
				$el.val( '' );
				$img.attr( 'src', $img.data( 'defaultsrc' ) );
				afterUpdate();
			});
		});
	});

});
