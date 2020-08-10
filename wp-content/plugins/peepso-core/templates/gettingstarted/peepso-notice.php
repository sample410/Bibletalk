<div class="notice notice-info peepso">
	<p>
	<?php echo sprintf(__("Thank you for using %s! We trust you'll love our plugin %s. Everything was already set up for you. If you'd like we can take you through a Getting Started procedure too. %sPlease note that %s. We do offer various %s for sale to extend its features. That allows us to put 100%% of our focus on not only development but also fanatical support.",'peepso-core'),
		'<strong>'.__('PeepSo','peepso-core').'</strong>',
		'<img draggable="false" class="emoji" alt="❤" src="https://s.w.org/images/core/emoji/2.4/svg/2764.svg">',
		'<br>',
		'<strong>'.__('PeepSo is FREE forever','peepso-core').'</strong>',
		'<a href="admin.php?page=peepso-extensions">'.__('addons','peepso-core').'</a>');
	?>
	</p>

	<p>
		<a href="admin.php?page=peepso-getting-started" class="button button-primary"><?php echo __('See Getting Started Page','peepso-core') ?></a>
		<a id="ps-gs-notice-dismiss" href="#" class="button ps-js-gs-notice-dimiss">
			<?php echo __('Dismiss','peepso-core') ?>
		</a>
	</p>
</div>
<script>
setTimeout(function() {
	jQuery(function( $ ) {
		$( '.ps-js-gs-notice-dimiss' ).on( 'click', function( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( this ).closest( '.notice' ).remove();
			$.get( window.location.href, { peepso_hide_getting_started: 1 } );
		})
	});
}, 100 );
</script>