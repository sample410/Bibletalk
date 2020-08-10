<?php
$PeepSoForm = PeepSoForm::get_instance();
$PeepSoRegister = PeepSoRegister::get_instance();
?>

<!-- PEEPSO WRAPPER -->
<div class="peepso">
	<!-- REGISTER WRAPPER -->
	<div class="ps-register">
		<?php if (!empty($error)) : ?>
		<div class="ps-alert ps-alert-danger"><?php echo __('Error: ', 'peepso-core'); echo $error; ?></div>
		<?php endif; ?>

		<?php do_action('peepso_before_registration_form');?>

		<!-- REGISTER FORM -->
		<div class="ps-register__form">
			<?php $PeepSoForm->render($PeepSoRegister->register_form()); ?>
		</div><!-- end: REGISTER FORM -->

		<?php do_action('peepso_after_registration_form'); ?>
	</div><!-- end: REGISTER WRAPPER -->
</div><!-- end: PEEPSO WRAPPER -->

<script>

// show terms and condition dialog
function show_terms() {
    var inst = pswindow.show('<?php echo __('Terms and Conditions', 'peepso-core'); ?>', peepsoregister.terms ),
        elem = inst.$container.find('.ps-dialog');

    elem.addClass('ps-dialog-full');
    peepso.observer.addFilter('pswindow_close', function() {
        elem.removeClass('ps-dialog-full');
    }, 10, 1 );
}

function show_privacy() {
    var inst = pswindow.show('<?php echo __('Privacy Policy', 'peepso-core'); ?>', peepsoregister.privacy ),
        elem = inst.$container.find('.ps-dialog');

    elem.addClass('ps-dialog-full');
    peepso.observer.addFilter('pswindow_close', function() {
        elem.removeClass('ps-dialog-full');
    }, 10, 1 );
}

</script>
