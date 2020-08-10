<div class="psa-starter__page psa-starter__page--welcome psa-starter__page--split">
    <div class="psa-starter__column">
        <div class="psa-starter__welcome">
            <i class="fa fa-magic" aria-hidden="true"></i>
            <?php echo __('Welcome to PeepSo! This wizard will take you through the necessary basics.','peepso-core');?>
        </div>
        <hr>
        <div class="psa-starter__header">
            <h1 class="psa-starter__header-title"><?php echo  __('PeepSo pages and shortcodes','peepso-core');?></h1>

            <p>
                <?php echo  __('PeepSo navigation is dependent on WordPress Pages containing PeepSo Shortcodes.','peepso-core');?>
            </p>

            <p>
                <?php echo  __('Each shortcode should be used only once.','peepso-core');?>
                <?php echo __('Pages containing PeepSo shortcodes should not be deleted.','peepso-core');?>
                <?php echo  __('You can change the titles and slugs, though.','peepso-core');?>
            </p>

            <p>
                <?php echo sprintf(__('If you have many pages with the same shortcode, you can configure the primary page in the %s Configuration.', 'peepso-core'), '<a target="_blank" href="'.admin_url('admin.php?page=peepso_config&tab=navigation').'">'.__('Navigation','peepso-core').' <i class="fa fa-external-link"></i></a>');?>
            </p>


            <p>
                <?php echo sprintf(__('For your convenience PeepSo already created the necessary %s.','peepso-core'),'<a target="_blank" href="'.admin_url('edit.php?s=%5Bpeepso_&post_status=all&post_type=page').'">'.__('pages','peepso-core').' <i class="fa fa-external-link"></i></a>');?>
                <?php echo  __('Depending on your WordPress / theme configuration you might need to include these pages in the menus.','peepso-core');?>
            </p>

            <p>
                <?php echo __('Below you can see the list of pages and their corresponding shortcodes.','peepso-core');?>
            </p>

            <p>
                <?php echo sprintf(__('You can find more information %s.','peepso-core'),'<a target="_blank" href="https://peep.so/docs_shortcodes">'.__('in the shortcodes documentation','peepso-core').' <i class="fa fa-external-link"></i></a>');?>
            </p>
        </div>

        <div class="psa-starter__shortcode-list">
            <!-- COMMUNITY Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('activity');?>"><?php echo __('Community Home','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_activity]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoActivityShortcode::description();?></div>
                </div>
            </div>

            <!-- MEMBERS Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('members');?>"><?php echo __('Members','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_members]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoMembersShortcode::description();?></div>
                </div>
            </div>

            <!-- PROFILE Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('profile');?>"><?php echo __('Profiles','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_profile]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoProfileShortcode::description();?></div>
                </div>
            </div>

            <!-- REGISTER Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('register');?>"><?php echo __('Registration','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_register]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoRegisterShortcode::description();?>
                    </div>
                </div>
            </div>

            <!-- RECOVER Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('recover');?>"><?php echo __('Password recovery','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_recover]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoRecoverPasswordShortcode::description();?></div>
                </div>
            </div>

            <!-- RESET Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('reset');?>"><?php echo __('Password reset','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_reset]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoResetPasswordShortcode::description();?></div>
                </div>
            </div>

            <!-- EXTERNAL LINK Shortcode -->
            <div class="psa-starter__shortcode">
                <h2 class="psa-starter__shortcode-title">
                    <a target="_blank" href="<?php echo PeepSo::get_page('external_link_warning');?>"><?php echo __('External link warning','peepso-core');?> <i class="fa fa-external-link"></i></a>
                </h2>

                <div class="psa-starter__shortcode-body">
                    <div class="psa-starter__shortcode-code"><pre>[peepso_external_link_warning]</pre></div>
                    <div class="psa-starter__shortcode-desc"><?php echo PeepSoExternalLinkWarningShortcode::description();?></div>
                </div>
            </div>
        </div>
    </div>


