<?php

    $recaptchaEnabled = PeepSo::get_option('site_registration_recaptcha_enable', 0);
    $recaptchaClass = $recaptchaEnabled ? ' ps-js-recaptcha' : '';

?><div class="peepso">
    <section id="mainbody" class="ps-page">
        <section id="component" role="article" class="ps-clearfix">
            <div id="peepso" class="on-socialize ltr cRegister">
            	<?php
            	if(isset($error) && !in_array($error->get_error_code(), array('bad_form', 'expired_key', 'invalid_key'))) {
				?>
                <h4><?php echo __('Pick a New Password', 'peepso-core'); ?></h4>
                <?php } ?>

                <div class="ps-register-recover">

                    <?php
                    if (isset($error) && !empty($error)) {
                        PeepSoGeneral::get_instance()->show_error($error);
                    }

                    if(isset($error) && !in_array($error->get_error_code(), array('bad_form', 'expired_key', 'invalid_key'))) {
                    ?>
                    <form id="recoverpasswordform" name="recoverpasswordform" action="<?php PeepSo::get_page('recover'); ?>?submit" method="post" class="ps-form">
                    	<input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
        				<input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />
                        <input type="hidden" name="task" value="-reset-password" />
                        <input type="hidden" name="-form-id" value="<?php echo wp_create_nonce('peepso-reset-password-form'); ?>" />
                        <div class="ps-form__container">
                            <div class="ps-form__row">
                                <label for="email" class="ps-form__label"><?php echo __('New Password:', 'peepso-core'); ?>
                                    <span class="required-sign">&nbsp;*<span></span></span>
                                </label>
                                <div class="ps-form__field">
                                    <input class="ps-input" type="password" name="pass1" placeholder="<?php echo __('New Password', 'peepso-core'); ?>" required />
                                    <div class="ps-form__field-desc lbl-descript"><?php echo __('Enter your desired password', 'peepso-core'); ?></div>
                                    <ul class="ps-form__error" style="display:none"></ul>
                                </div>
                            </div>

                            <div class="ps-form__row">
                                <label for="email" class="ps-form__label"><?php echo __('Repeat new password:', 'peepso-core'); ?>
                                    <span class="required-sign">&nbsp;*<span></span></span>
                                </label>
                                <div class="ps-form__field">
                                    <input class="ps-input" type="password" name="pass2" placeholder="<?php echo __('Repeat new password', 'peepso-core'); ?>" required />
                                    <div class="ps-form__field-desc lbl-descript"><?php echo __('Please re-enter your password', 'peepso-core'); ?></div>
                                    <ul class="ps-form__error" style="display:none"></ul>
                                </div>
                            </div>

                            <div class="ps-form__row submitel">
                                <button type="submit" name="submit-recover"
                                    class="ps-btn ps-btn-primary<?php echo $recaptchaClass; ?>">
                                    <?php echo __('Submit', 'peepso-core'); ?>
                                    <img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt=""
                                        style="display:none" />
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="ps-gap"></div>

                    <p class="description"><?php echo sprintf(__('The password should be at least %d characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ %% ^ &amp; ).','peepso-core'), PeepSo::get_option('minimum_password_length', 10)); ?></p>
                    <?php
                    }
                    ?>

                    <div class="ps-gap"></div>
                    <a href="<?php echo get_bloginfo('wpurl'); ?>"><?php echo __('Back to Home', 'peepso-core'); ?></a>
                </div>
            </div><!--end peepso-->
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
