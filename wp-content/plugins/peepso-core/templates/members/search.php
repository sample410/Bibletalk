<div class="peepso">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
	<?php PeepSoTemplate::exec_template('general', 'register-panel'); ?>
	<?php if(get_current_user_id() > 0 || (get_current_user_id() == 0 && $allow_guest_access)) { ?>
	<section id="mainbody" class="ps-page-unstyled">
		<section id="component" role="article" class="ps-clearfix">
            <?php PeepSoTemplate::exec_template('general','wsi'); ?>
            <?php PeepSoTemplate::exec_template('members','members-tabs');?>

            <?php
            $PeepSoUser = PeepSoUser::get_instance(0);
			$profile_fields = new PeepSoProfileFields($PeepSoUser);
			$args = array(
				'post_name__in'=>array('gender')
			);
			$fields = $profile_fields->load_fields($args);
			if (isset($fields) && isset($fields[PeepSoField::USER_META_FIELD_KEY . 'gender'])) {
				$fieldGender = $fields[PeepSoField::USER_META_FIELD_KEY . 'gender'];
			}

            ?>

			<form class="ps-form ps-form-search" role="form" name="form-peepso-search" onsubmit="return false;">
				<div class="ps-form-row">
					<input placeholder="<?php echo __('Start typing to search...', 'peepso-core');?>" type="text" class="ps-input full ps-js-members-query" name="query" value="" />
				</div>
				<a href="#" class="ps-form-search-opt" onclick="return false;">
					<span class="ps-icon-cog"></span>
				</a>
			</form>
			<div class="ps-js-page-filters">
				<div class="ps-filters">
					<?php if (isset($fieldGender) && ($fieldGender->published == 1)){ ?>
					<div class="ps-filters__item">
						<label class="ps-filters__item-label"><?php echo __($fieldGender->title, 'peepso-core'); ?></label>
						<select class="ps-select ps-js-members-gender">
							<option value=""><?php echo __('Any', 'peepso-core'); ?></option>
							<?php
							if (!empty($genders) && is_array($genders)) {
								foreach ($genders as $key => $value) {
									?>
									<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
									<?php
								}
							}
							?>
						</select>
					</div>
					<?php } ?>

					<?php $default_sorting = PeepSo::get_option('site_memberspage_default_sorting',''); ?>
					<div class="ps-filters__item">
						<label class="ps-filters__item-label"><?php echo __('Sort', 'peepso-core'); ?></label>
						<select class="ps-select ps-js-members-sortby">
							<option value=""><?php echo __('Alphabetical', 'peepso-core'); ?></option>
							<option <?php echo ('peepso_last_activity' == $default_sorting) ? ' selected="selected" ' : '';?> value="peepso_last_activity|asc"><?php echo __('Recently online', 'peepso-core'); ?></option>
							<option <?php echo ('registered' == $default_sorting) ? ' selected="selected" ' : '';?>value="registered|desc"><?php echo __('Latest members', 'peepso-core'); ?></option>
							<?php if (PeepSo::get_option('site_likes_profile', TRUE)) : ?>
							<option <?php echo ('most_liked' == $default_sorting) ? ' selected="selected" ' : '';?>value="most_liked|desc"><?php echo __('Most liked', 'peepso-core'); ?></option>
							<?php endif; ?>
						</select>
					</div>

					<?php if(class_exists('PeepSoFriendsPlugin')) { ?>
					<div class="ps-filters__item">
                        <label class="ps-filters__item-label"><?php echo __('Following', 'peepso-core');?></label>
                        <select class="ps-select ps-js-members-following">
                            <option value="-1"><?php echo __('All members', 'peepso-core'); ?></option>
                            <option value="1"><?php echo __('Members I follow', 'peepso-core'); ?></option>
                            <option value="0"><?php echo __('Members I don\'t follow', 'peepso-core'); ?></option>
                        </select>
					</div>
					<?php } else { ?>
					<input type="hidden" id="only-following" name="followed" value="01" class="ps-js-members-following" />
					<?php } ?>

					<div class="ps-filters__item">
                        <label class="ps-filters__item-label"><?php echo __('Avatars', 'peepso-core');?></label>
                        <div class="ps-checkbox">
                            <input type="checkbox" id="only-avatars" name="avatar" value="1" class="ps-js-members-avatar" />
                            <label for="only-avatars"><?php echo __('Only users with avatars', 'peepso-core'); ?></label>
                        </div>
					</div>

					<?php do_action('peepso_action_render_member_search_fields'); ?>
				</div>
			</div>

			<div class="ps-clearfix mb-20"></div>
			<div class="ps-members ps-clearfix ps-js-members"></div>
			<div class="ps-scroll ps-clearfix ps-js-members-triggerscroll">
				<img class="post-ajax-loader ps-js-members-loading" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" style="display:none" />
			</div>
		</section>
	</section>
	<?php } ?>
</div><!--end row-->

<?php

PeepSoTemplate::exec_template('activity', 'dialogs');

wp_enqueue_style('peepso-datepicker');