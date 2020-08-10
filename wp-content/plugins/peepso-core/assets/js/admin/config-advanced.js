jQuery( function( $ ) {
	var $allowEmbed = $( 'input[name=allow_embed]' ),
		$allowNonSSL = $( 'input[name=allow_non_ssl_embed]' );

	$allowEmbed.on( 'click', function() {
		var $field = $allowNonSSL.closest( '.form-group' );
		this.checked ? $field.show() : $field.hide();
	} );
	$allowEmbed.triggerHandler( 'click' );


	var $loadMore = $( 'select[name=loadmore_enable]' ),
		$loadMoreRepeat = $( 'select[name=loadmore_repeat]' );

	$loadMore.on( 'change', function() {
		var $field = $loadMoreRepeat.closest( '.form-group' );
		this.value == 0 ? $field.hide() : $field.show();
	} );
	$loadMore.triggerHandler( 'change' );
} );
