<?php
$PeepSoActivityShortcode = PeepSoActivityShortcode::get_instance();
global $post;


$user_stream_filters = PeepSoUser::get_stream_filters();
$stream_id_list = apply_filters('peepso_stream_id_list', array());
$small_thumbnail = PeepSo::get_option('small_url_preview_thumbnail', 0);

?>
<div class="peepso ps-page--activity-post">
    <section id="mainbody" class="ps-wrapper ps-clearfix">
        <section id="component" role="article" class="ps-clearfix">
            <?php PeepSoTemplate::exec_template('general', 'navbar'); ?>
            <?php if (FALSE === $PeepSoActivityShortcode->is_permalink_page()) { PeepSoTemplate::exec_template('general', 'register-panel'); } ?>

            <?php /*override header*/ do_action('peepso_activity_single_override_header'); ?>

            <?php if (! get_current_user_id() && $PeepSoActivityShortcode->is_permalink_page()) { PeepSoTemplate::exec_template('general','login-profile-tab'); } ?>

            <div class="ps-body">
                <!--<div class="ps-sidebar"></div>-->
                <div class="ps-main ps-main-full">
                    <?php PeepSoTemplate::exec_template('general', 'postbox-legacy'); ?>

                    <?php if(get_current_user_id() && FALSE === $PeepSoActivityShortcode->is_permalink_page()) { ?>

                        <input type="hidden" id="peepso_context" value="stream" />

                        <?php if(NULL != $user_stream_filters ) { ?>

                        <?php PeepSoTemplate::exec_template('activity', 'activity-stream-filters', array('user_stream_filters'=>$user_stream_filters,'stream_id_list'=>$stream_id_list )); ?>

                        <?php } ?>

                    <?php } elseif($post->post_type == 'peepso-post') { ?>

                        <input type="hidden" id="peepso_post_id" value="<?php global $post; echo $post->ID; ?>" />
                        <input type="hidden" id="peepso_context" value="single" />

                    <?php } ?>



                    <!-- stream activity -->
                    <div class="ps-stream-wrapper">
                        <div id="ps-activitystream-recent" class="ps-stream-container <?php echo $small_thumbnail ? '' : 'ps-stream-container-narrow' ?>" style="display:none"></div>
                        <div id="ps-activitystream" class="ps-stream-container <?php echo $small_thumbnail ? '' : 'ps-stream-container-narrow' ?>" style="display:none"></div>

                        <div id="ps-activitystream-loading">
                            <?php PeepSoTemplate::exec_template('activity', 'activity-placeholder'); ?>
                        </div>

                        <div id="ps-no-posts" class="ps-alert" style="display:none"><?php echo __('No posts found.', 'peepso-core'); ?></div>
                        <div id="ps-no-posts-match" class="ps-alert" style="display:none"><?php echo __('No posts found.', 'peepso-core'); ?></div>
                        <div id="ps-no-more-posts" class="ps-alert" style="display:none"><?php echo __('Nothing more to show.', 'peepso-core'); ?></div>

                        <?php PeepSoTemplate::exec_template('activity', 'dialogs'); ?>
                    </div>
                </div>
            </div>
        </section><!--end component-->
    </section><!--end mainbody-->
</div><!--end row-->
