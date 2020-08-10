<?php
$PeepSoActivity = PeepSoActivity::get_instance();
$PeepSoUser = PeepSoUser::get_instance($post_author);
$fullName = $PeepSoUser->get_fullname();
?>
<div id="comment-item-<?php echo $ID; ?>" class="ps-comment-item cstream-comment stream-comment" data-comment-id="<?php echo $ID; ?>">
	<div class="ps-avatar-comment">
		<a class="cstream-avatar cstream-author" href="<?php echo $PeepSoUser->get_profileurl(); ?>">
			<img data-author="<?php echo $post_author; ?>" src="<?php echo PeepSoUser::get_instance($post_author)->get_avatar(); ?>" alt="<?php echo __($fullName); ?> avatar" />
		</a>
	</div>

	<div class="ps-comment-body cstream-content">
        <?php if(!strlen($human_friendly) || !strlen(get_transient('peepso_cache_hf_'.$ID))) { ?>
		<input type="hidden" name="peepso_set_human_friendly"
			data-author="<?php echo $post_author; ?>"
			value="<?php echo $ID;?>" />
            <?php
            set_transient('peepso_cache_hf_' . $ID, 1, 600);
        }
        ?>

		<div class="ps-comment-message stream-comment-content">
			@peepso_user_<?php echo $post_author; ?>(<?php echo $fullName; ?>)
			<span class="ps-comment__content" data-type="stream-comment-content"><?php $PeepSoActivity->content(); ?></span>
		</div>

		<div data-type="stream-more" class="cstream-more" data-commentmore="true"></div>

		<div class="ps-comment-media cstream-attachments"><?php $PeepSoActivity->comment_attachment(); ?></div>

		<div class="ps-comment-time ps-shar-meta-date">

      <small class="activity-post-age activity-post-age-text-only" data-timestamp="<?php $PeepSoActivity->post_timestamp(); ?>">
              <?php $PeepSoActivity->post_age(); ?>
      </small>

			<small class="activity-post-age activity-post-age-link" data-timestamp="<?php $PeepSoActivity->post_timestamp(); ?>">
				<a href="<?php $PeepSoActivity->comment_link(); ?>">
				<?php $PeepSoActivity->post_age(); ?>
				</a>

                <a href="<?php $PeepSoActivity->comment_link(); ?>">
                <?php $PeepSoActivity->post_permalink(); ?>
                </a>

			</small>

            <?php
            $PeepSoActivity->post_edit_notice();
            ?>

			<?php if($likes = $PeepSoActivity->has_likes($act_id)){ ?>
			<div id="act-like-<?php echo $act_id; ?>" class="ps-comment-links cstream-likes ps-js-act-like--<?php echo $act_id; ?>" data-count="<?php echo $likes ?>">
				<?php $PeepSoActivity->show_like_count($likes); ?>
			</div>
			<?php } else { ?>
			<div id="act-like-<?php echo $act_id; ?>" class="ps-comment-links cstream-likes ps-js-act-like--<?php echo $act_id; ?>" data-count="0" style="display:none"></div>
			<?php } ?>

			<div class="ps-comment-links stream-actions" data-type="stream-action">
				<span class="ps-stream-status-action ps-stream-status-action">
					<?php $PeepSoActivity->comment_actions(); ?>
				</span>
			</div>
		</div>
	</div>
</div>

<?php
	$PeepSoActivity2 = new PeepSoActivity();
	$PeepSoActivity2->show_replycomment(get_current_user_id(), $ID, $act_id);
?>
