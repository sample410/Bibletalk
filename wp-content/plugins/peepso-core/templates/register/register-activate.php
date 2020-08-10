<!-- PEEPSO WRAPPER -->
<div class="peepso">
	<!-- REGISTER WRAPPER -->
	<div class="ps-register ps-register--activate">
		<div class="ps-register__header">
			<h3 class="ps-register__header-title"><?php echo __('Account Activation', 'peepso-core'); ?></h3>
			<p><?php echo __('Please enter your activation code below to enable your account.', 'peepso-core'); ?></p>
		</div>

		<?php
		if (isset($error)) {
			PeepSoGeneral::get_instance()->show_error($error);
		}
		?>

		<!-- REGISTER FORM -->
		<div class="ps-register__form">
			<form class="ps-form ps-form--register-activate" name="resend-activation" action="<?php PeepSo::get_page('register'); ?>?activate" method="post">
				<div class="ps-form__container">
					<div class="ps-form__row">
						<label for="activation" class="ps-form__label"><?php echo __('Activation Code:', 'peepso-core'); ?>
							<span class="required-sign">&nbsp;*<span></span></span>
						</label>
						<div class="ps-form__field">
							<?php
								$input = new PeepSoInput();
								$value = $input->value('community_activation_code', $input->value('peepso_activation_code', '', FALSE), FALSE); // Fallback activation code - see #3142
							?>
							<input type="text" name="community_activation_code" class="ps-input" value="<?php echo $value; ?>" placeholder="<?php echo __('Activation code', 'peepso-core'); ?>" />
						</div>
					</div>
					<div class="ps-form__row submitel">
						<div class="ps-form__field">
							<input type="submit" name="submit-activate" class="ps-btn ps-btn-primary" value="<?php echo __('Submit', 'peepso-core'); ?>" />
						</div>
					</div>
				</ul>
			</form>
		</div><!-- end: REGISTER FORM -->

		<div class="ps-register__footer">
			<a href="<?php echo get_bloginfo('wpurl'); ?>"><?php echo __('Back to Home', 'peepso-core'); ?></a>
		</div>
	</div><!-- end: REGISTER WRAPPER -->
</div><!-- end: PEEPSO WRAPPER -->
