<?php if ( ! is_user_logged_in()) {
$activated = FALSE;

if(isset($_COOKIE['peepso_last_visited_page']) && stristr($_COOKIE['peepso_last_visited_page'], 'community_activate')) {
    $activated = TRUE;
}

// since 1.11.3 - fallback for peepso_activate renamed into community_activate #3180
if(isset($_COOKIE['peepso_last_visited_page']) && stristr($_COOKIE['peepso_last_visited_page'], 'peepso_activate')) {
    $activated = TRUE;
}

?>


<div class="ps-landing">
    <?php
    $default = PeepSo::get_option('landing_page_image', PeepSo::get_asset('images/landing/register-bg.jpg'));
    $disable_registration = intval(PeepSo::get_option('site_registration_disabled', 1));
    $landing_page = !empty($default) ? $default : PeepSo::get_asset('images/landing/register-bg.jpg');
    ?>

    <?php if(!isset($no_cover)) { ?>
    <div class="ps-landing__cover" style="background-image:url('<?php echo $landing_page;?>')">
        <div class="ps-landing__content">
            <div class="ps-landing__text">
                <?php if($activated) : ?>
                    <h2><?php echo __('Thank you', 'peepso-core');?></h2>
                    <p><?php echo __('Your e-mail address was confirmed. You can now log in.','peepso-core');?></p>
                <?php else : ?>
                    <h2><?php echo PeepSo::get_option('site_registration_header', __('Get Connected!', 'peepso-core')); ?></h2>
                    <p><?php echo PeepSo::get_option('site_registration_callout', __('Come and join our community. Expand your network and get to know new people!', 'peepso-core')); ?></p>
                <?php endif; ?>
            </div>

            <?php if(!$activated && 0 === $disable_registration) { ?>
                <div class="ps-landing__actions">
                    <a class="ps-btn ps-btn-join" href="<?php echo PeepSo::get_page('register'); ?>">
                        <?php echo PeepSo::get_option('site_registration_buttontext', __('Join us now, it\'s free!', 'peepso-core')); ?></a>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

    <?php PeepSoTemplate::exec_template('general', 'login');?>
</div>

<?php
} // is_user_logged_in() ?>
