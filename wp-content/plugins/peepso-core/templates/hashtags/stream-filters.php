<?php
/** SEARCH POSTS **/
$hashtag = FALSE;
$PeepSoUrlSegments = PeepSoUrlSegments::get_instance();

if('hashtag' == $PeepSoUrlSegments->get(1)) {
    $hashtag = $PeepSoUrlSegments->get(2);
}

?>
<input type="hidden" id="peepso_search_hashtag" value="<?php echo $hashtag; ?>" />
<span class="ps-dropdown ps-dropdown--right ps-dropdown--stream-filter ps-js-dropdown ps-js-activitystream-filter" data-id="peepso_search_hashtag">
	<a class="ps-btn ps-btn--small ps-js-dropdown-toggle" aria-haspopup="true">
		<span data-empty="<?php echo __('#', 'peepso-core'); ?>"
			data-keyword="<?php echo __('#', 'peepso-core'); ?>"
		><i class="ps-icon-hashtag"></i></span>
	</a>
	<div role="menu" class="ps-dropdown__menu ps-js-dropdown-menu">
		<div class="ps-dropdown__actions">
			<i class="ps-icon-hashtag"></i><input maxlength="<?php echo PeepSo::get_option('hashtags_max_length',16);?>" type="text" class="ps-input ps-input--small ps-full"
				placeholder="<?php echo __('Type to search', 'peepso-core'); ?>" value="<?php echo $hashtag;?>" />
        </div>
        <div class="ps-dropdown__desc">
        	<i class="ps-icon-info-circled"></i>
            <?php
                echo sprintf(
                        __('Letters and numbers only, minimum %d and maximum %d character(s)','peepso-core'),
                        PeepSo::get_option('hashtags_min_length',3),
                        PeepSo::get_option('hashtags_max_length',16)
                );?>
		</div>
		<div class="ps-dropdown__actions">
			<button class="ps-btn ps-btn--small ps-js-cancel"><?php echo __('Cancel', 'peepso-core'); ?></button>
			<button class="ps-btn ps-btn--small ps-btn-primary ps-js-search-hashtag"><?php echo __('Apply', 'peepso-core'); ?></button>
		</div>
	</div>
</span>
