<div class="peepso ps-page-profile">
	<?php PeepSoTemplate::exec_template('general', 'navbar'); ?>

	<?php PeepSoTemplate::exec_template('profile', 'focus', array('current'=>'blogposts')); ?>

	<section id="mainbody" class="ps-page-unstyled">
		<section id="component" role="article" class="ps-clearfix">

            <?php
            $submissions = FALSE;

            if(class_exists( 'CMUserSubmittedPosts' ) && PeepSo::get_option('blogposts_submissions_enable')) { $submissions = TRUE; }
            if(PeepSo::usp_enabled() && PeepSo::get_option('blogposts_submissions_enable_usp'))                { $submissions = TRUE; }

            if($submissions) {
                PeepSoTemplate::exec_template('blogposts', 'blogposts_tabs', array('create_tab'=>FALSE));
            }
            ?>

			<div class="ps-page-filters">
				<select class="ps-select ps-full ps-js-blogposts-sortby ps-js-blogposts-sortby--<?php echo apply_filters('peepso_user_profile_id', 0); ?>">
					<option value="desc"><?php echo __('Newest first', 'peepso-core');?></option>
					<option value="asc"><?php echo __('Oldest first', 'peepso-core');?></option>
				</select>
			</div>

			<div class="ps-clearfix mb-20"></div>
			<div class="ps-blogposts <?php echo PeepSo::get_option('blogposts_profile_two_column_enable', 0) ? 'ps-blogposts--half': '' ?>
					ps-js-blogposts ps-js-blogposts--<?php echo apply_filters('peepso_user_profile_id', 0); ?>"
					style="margin-bottom:10px"></div>
			<div class="ps-scroll ps-clearfix ps-js-blogposts-triggerscroll">
				<img class="post-ajax-loader ps-js-blogposts-loading" src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" style="display:none" />
			</div>

		</section><!--end component-->
	</section><!--end mainbody-->
</div><!--end row-->
<?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>
