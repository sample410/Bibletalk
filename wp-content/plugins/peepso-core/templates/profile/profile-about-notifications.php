<?php
$user = PeepSoUser::get_instance(PeepSoProfileShortcode::get_instance()->get_view_user_id());

$can_edit = FALSE;
if($user->get_id() == get_current_user_id() || current_user_can('edit_users')) {
    $can_edit = TRUE;
}

if(!$can_edit) {
    PeepSo::redirect(PeepSo::get_page('activity'));
} else {

    $PeepSoProfile = PeepSoProfile::get_instance();

    if(isset($_GET['test'])) {
        $PeepSoNotificationsQueue= PeepSoNotificationsQueue::get_instance();
        $PeepSoNotificationsQueue->debug();
    }
    ?>

    <div class="peepso ps-page-profile ps-page--preferences" xmlns="http://www.w3.org/1999/html">
        <?php PeepSoTemplate::exec_template('general', 'navbar'); ?>

        <?php PeepSoTemplate::exec_template('profile', 'focus', array('current'=>'about')); ?>

        <section id="mainbody" class="ps-page-unstyled">
            <section id="component" role="article" class="ps-clearfix">

                <?php if($can_edit) { PeepSoTemplate::exec_template('profile', 'profile-about-tabs', array('tabs' => $tabs, 'current_tab'=>'notifications'));} ?>

                <div class="ps-preferences__notifications-actions">

                    <?php /*
                    <div id="peepso_email_intensity_container">

                        <h3 class="ps-page-title"><?php echo __('E-mail notification intensity','peepso-core');?></h3>
                        <?php

                        $levels = PeepSoNotificationsIntensity::email_notifications_intensity_levels();

                        $email_preference = PeepSoNotificationsIntensity::user_email_notifications_intensity();

                        $options = array();
                        $descriptions = array();
                        ?>

                        <select name="email_intensity" id="peepso-email-intensity">
                            <?php foreach($levels as $key => $level) { ?>
                                <option <?php if($key == $email_preference) { echo 'selected';}?> value="<?php echo $key;?>"><?php echo $level['label']; ?></option>
                            <?php } ?>
                        </select>

                        <span class="ps-js-loading">
                        <img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif') ?>" style="display:none" />
                        <i class="ps-icon-ok" style="color:green;display:none"></i>
                    </span>

                        <div id="peepso_email_intensity_descriptions" style="margin:10px 0px 50px 10px;">

                            <?php foreach($levels as $key => $level) { ?>
                            <div id="peepso_email_intensity_<?php echo $key;?>" <?php if($key!=$email_preference) { echo 'style="display:none;"';}?>/><?php echo $level['desc'];?></div>
                    <?php } ?>

                        <?php if(class_exists('PeepSoEmailDigest')) {
                            echo __('This setting does not affect Email Digest','peepso-core');
                        }
                        ?>
                    </div>
                */ ?>

                <h3 class="ps-page-title"><?php echo __('Shortcuts','peepso-core');?></h3>
                <p><?php echo __('Quickly manage all your preferences at once.', 'peepso-core');?>:</p>

                <?php
                    $hide_email = false;
                    if (isset($email_preference)) {
                        $hide_email = (int) $email_preference < 100;
                    }
                ?>

                <div class="ps-preferences-notifications__menu" role="menu"  style="margin:0px 0px 50px 0px;">
                    <a class="ps-preferences-notifications__menu-item" role="menuitem"
                       href="<?php echo admin_url('admin-ajax.php?action=peepso_user_subscribe_emails&redirect') ?>"
                       data-action="enable" data-context="<?php echo isset($context) ? isset($context) : '';?>" data-type="email"
                       style="<?php echo $hide_email ? 'display:none' : '' ?>">
                        <?php echo __('Enable all notifications', 'peepso-core');?>
                    </a>
                    <a class="ps-preferences-notifications__menu-item" role="menuitem" href="<?php echo admin_url('admin-ajax.php?action=peepso_user_unsubscribe_onsite&redirect') ?>" data-action="disable">
                        <?php echo __('Disable all notifications', 'peepso-core');?>
                    </a>
                    <a class="ps-preferences-notifications__menu-item" role="menuitem"
                       href="<?php echo admin_url('admin-ajax.php?action=peepso_user_unsubscribe_emails&redirect')?>"
                       data-action="disable" data-type="email"
                       style="<?php echo $hide_email ? 'display:none' : '' ?>">
                        <?php echo __('Disable all e-mail notifications', 'peepso-core');?>
                    </a>
                    <a class="ps-preferences-notifications__menu-item" role="menuitem" href="<?php echo admin_url('admin-ajax.php?action=peepso_user_subscribe_onsite&redirect') ?>" data-action="enable" data-context="<?php echo isset($context) ? isset($context) : '';?>">
                        <?php echo __('Enable all on-site notifications', 'peepso-core');?>
                    </a>
                </div>
    </div>

    <h3 class="ps-page-title"><?php echo __('All notifications','peepso-core');?></h3>
    <div class="ps-list--column cfield-list creset-list ps-js-profile-list">
        <p><?php echo __('E-mail notifications require an on-site notification enabled.', 'peepso-core');?>:</p>
        <div class="cfield-list creset-list">
            <?php $PeepSoProfile->preferences_form_fields('notifications', TRUE); ?>
            <?php
                /**
                 * @deprecated
                 *
                 * This action hook was used to add the notification settings for groups.
                 * We are now using `peepso_profile_alerts` filter hook to make it consistent with other plugins.
                 */
                do_action('peepso_render_profile_about_notifications_after');
            ?>
        </div>
    </div>
    </section><!--end component-->
    </section><!--end mainbody-->

    <div id="ps-dialogs" style="display:none">
        <?php PeepSoActivity::get_instance()->dialogs(); // give add-ons a chance to output some HTML ?>
        <?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>
    </div>
    </div><!--end row-->
<?php }