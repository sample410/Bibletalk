<?php if(TRUE === apply_filters('peepso_permissions_post_create', is_user_logged_in())) {
$PeepSoPostbox = PeepSoPostbox::get_instance();
$PeepSoGeneral = PeepSoGeneral::get_instance();
?>

<?php if (is_user_logged_in() && FALSE === PeepSoActivityShortcode::get_instance()->is_permalink_page()) { ?>
<div id="postbox-main" class="ps-postbox ps-clearfix" style="">
	<?php $PeepSoPostbox->before_postbox(); ?>
	<div id="ps-postbox-status" class="ps-postbox-content">
		<div class="ps-postbox-tabs">
			<?php $PeepSoPostbox ->postbox_tabs(); ?>
		</div>
		<?php PeepSoTemplate::exec_template('general', 'postbox-status'); ?>
	</div>

	<div class="ps-postbox-tab ps-postbox-tab-root ps-clearfix" style="display:none">
		<div class="ps-postbox__menu ps-postbox__menu--tabs">
			<?php $PeepSoGeneral->post_types(array('is_current_user' => isset($is_current_user) ? $is_current_user : NULL)); ?>
		</div>
	</div>

	<nav class="ps-postbox-tab selected interactions">
		<div class="ps-postbox__menu ps-postbox__menu--interactions">
			<?php $PeepSoPostbox->post_interactions(array('is_current_user' => isset($is_current_user) ? $is_current_user : NULL)); ?>
		</div>
		<div class="ps-postbox__action ps-postbox-action">
			<button type="button" class="ps-btn ps-btn--postbox ps-button-cancel" style="display:none"><?php echo __('Cancel', 'peepso-core'); ?></button>
			<button type="button" class="ps-btn ps-btn--postbox ps-button-action postbox-submit" style="display:none"><?php echo __('Post', 'peepso-core'); ?></button>
		</div>
		<div class="ps-postbox-loading" style="display: none;">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>">
			<div> </div>
		</div>
	</nav>
<?php $PeepSoPostbox->after_postbox(); ?>
</div>
<?php } // is_user_logged_in() ?>
<?php } else { PeepSoTemplate::exec_template('general','postbox-permission-denied'); }// peepso_permissions_post_create ?>
