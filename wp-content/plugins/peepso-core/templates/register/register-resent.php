<!-- PEEPSO WRAPPER -->
<div class="peepso">
	<!-- REGISTER WRAPPER -->
	<div class="ps-register ps-register--resent">
		<div class="ps-register__header">
			<h3 class="ps-register__header-title"><?php echo __('Resend Activation Code', 'peepso-core'); ?></h3>
			<p><?php echo __('Your activation code has been sent to your email.', 'peepso-core'); ?></p>
			<p>
				<?php
					$link = PeepSo::get_page('register') . '?community_activate';
					echo sprintf(__('Follow the link in the email you received, or you can enter the activation code on the <a href="%1$s"><u>activation</u></a> page.</a>', 'peepso-core'), $link);
				?>
			</p>
		</div>

		<div class="ps-register__footer">
			<a href="<?php echo get_bloginfo('wpurl'); ?>"><?php echo __('Back to Home', 'peepso-core'); ?></a>
		</div>
	</div><!-- end: REGISTER WRAPPER -->
</div><!-- end: PEEPSO WRAPPER -->
