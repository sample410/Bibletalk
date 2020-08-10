<?php

    $recaptchaEnabled = PeepSo::get_option('site_registration_recaptcha_enable', 0);
    $recaptchaClass = $recaptchaEnabled ? ' ps-js-recaptcha' : '';

?>
<!-- PEEPSO WRAPPER -->
<div class="peepso">
	<!-- REGISTER WRAPPER -->
	<div class="ps-register ps-register--resend">
		<div class="ps-register__header">
			<h3 class="ps-register__header-title"><?php echo __('Resend Activation Code', 'peepso-core'); ?></h3>
			<p><?php echo __('Please enter your registered e-mail address here so that we can resend you the activation link.', 'peepso-core'); ?></p>
		</div>

		<?php
		if (isset($error)) {
			PeepSoGeneral::get_instance()->show_error($error);
		}
		?>

		<!-- REGISTER FORM -->
		<div class="ps-register__form">
			<form class="ps-form ps-form--register-resend" name="resend-activation" action="<?php PeepSo::get_page('register'); ?>?resend" method="post">
				<input type="hidden" name="task" value="-resend-activation" />
				<input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('resent-activation-form'); ?>" />
				<div class="ps-form__container">
					<div class="ps-form__row">
						<label for="email" class="ps-form__label"><?php echo __('Email Address', 'peepso-core'); ?>
							<span class="required-sign">&nbsp;*<span></span></span>
						</label>
						<div class="ps-form__field">
							<input class="ps-input" type="email" name="email" id="email" placeholder="<?php echo __('Email address', 'peepso-core'); ?>" />
						</div>
					</div>
					<div class="ps-form__row submitel">
						<div class="ps-form__field">
							<input type="submit" name="submit-resend" 
								class="ps-btn ps-btn-primary<?php echo $recaptchaClass; ?>" 
								value="<?php echo __('Submit', 'peepso-core'); ?>" />
						</div>
					</div>
				</div>
			</form>
		</div><!-- end: REGISTER FORM -->

		<div class="ps-register__footer">
			<a href="<?php echo get_bloginfo('wpurl'); ?>"><?php echo __('Back to Home', 'peepso-core'); ?></a>
		</div>
	</div><!-- end: REGISTER WRAPPER -->
</div><!-- end: PEEPSO WRAPPER -->
