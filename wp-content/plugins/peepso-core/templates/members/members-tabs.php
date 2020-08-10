<?php 

if(get_current_user_id()) { 

	$blocked_member_url = PeepSo::get_page('members');
	if(0 == PeepSo::get_option('disable_questionmark_urls', 0)) {
	    $blocked_member_url .= '?';
	}
	$blocked_member_url .= 'blocked/';

?>

<div class="ps-tabs__wrapper">
    <div class="ps-tabs ps-tabs--arrows">
        <div class="ps-tabs__item <?php if (!isset($tab)) echo "current"; ?>"><a
                    href="<?php echo PeepSo::get_page('members'); ?>"><?php echo __('Members', 'peepso-core'); ?></a>
        </div>
        <div class="ps-tabs__item <?php if (isset($tab) && 'blocked' == $tab) echo "current"; ?>"><a
                    href="<?php echo $blocked_member_url; ?>"><?php echo __('Blocked', 'peepso-core'); ?></a>
        </div>
    </div>
</div>

<?php }