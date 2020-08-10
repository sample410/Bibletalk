<?php

// USP & USP PRO

if(PeepSo::usp_enabled() && PeepSo::get_option('blogposts_submissions_enable_usp')) {
?>
    <div class="peepso ps-page-profile">
        <?php PeepSoTemplate::exec_template('general', 'navbar'); ?>

        <?php PeepSoTemplate::exec_template('profile', 'focus', array('current'=>'blogposts')); ?>

        <section id="mainbody" class="ps-page-unstyled">
            <section id="component" role="article" class="ps-clearfix">

                <?php PeepSoTemplate::exec_template('blogposts', 'blogposts_tabs', array('create_tab'=>TRUE)); ?>

                <?php
                if(PeepSo::usp_pro_enabled()) {
                    echo do_shortcode(PeepSo::get_option('blogposts_submissions_usp_pro_shortcode','[user-submitted-posts]'));
                } else {
                    echo do_shortcode('[user-submitted-posts]');
                }


                ?>

            </section><!--end component-->
        </section><!--end mainbody-->
    </div><!--end row-->
<?php

// CMINDS SUBMITTED POSTS

}elseif(PeepSo::get_option('blogposts_submissions_enable') && class_exists( 'CMUserSubmittedPosts' )) {


    if(!function_exists('peepso_blogposts_enqueue_cm_scripts')) {
        function peepso_blogposts_enqueue_cm_scripts()
        {
            // grab and backup the global post object
            global $post;
            $post_backup = $post;

            // force CM shortcode in the content for their checks to succeed
            $post->post_content = '[add_post_form]';

            // enqueue CM scripts
            try {
                CMUserSubmittedPostsFrontend::instance()->enqueue_scripts();
            } catch (Exception $e) {
                new PeepSoError('Unable to enqueue CM scripts', 'error', 'blogposts');
            }

            // restore post object
            $post = $post_backup;
        }
    }

    peepso_blogposts_enqueue_cm_scripts();
?>
<div class="peepso ps-page-profile">
    <?php PeepSoTemplate::exec_template('general', 'navbar'); ?>

    <?php PeepSoTemplate::exec_template('profile', 'focus', array('current'=>'blogposts')); ?>

    <section id="mainbody" class="ps-page-unstyled">
        <section id="component" role="article" class="ps-clearfix">

                <?php PeepSoTemplate::exec_template('blogposts', 'blogposts_tabs', array('create_tab'=>TRUE)); ?>

                <?php echo do_shortcode('[add_post_form]'); ?>

        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
<?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>

<?php } else {
    PeepSo::redirect(PeepSoUser::get_instance()->get_profileurl().'blogposts');
}?>