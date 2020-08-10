<?php
$PeepSoProfile = PeepSoProfile::get_instance();
$PeepSoUser = $PeepSoProfile->user;

    foreach ($links as $id=>$link) {
        $url = $PeepSoUser->get_profileurl() . $link['href'];

            if('http' == substr($link['href'],0, 4)) {
                $url = $link['href'];
            }

            ?><a class="ps-focus__menu-item <?php if ($current == $id) { echo ' current '; } ?>" href="<?php echo $url;?>">
                    <i class="<?php echo $link['icon'];?>"></i>
                    <span><?php echo $link['label'];?></span>
            </a><?php
    }

?>
<a href="#" class="ps-focus__menu-item ps-js-focus-link-more" style="display:none">
    <i class="ps-icon-caret-down"></i>
    <span>
        <span><?php echo __('More', 'peepso-core'); ?></span>
        <span class="ps-icon-caret-down"></span>
    </span>
</a>
<div class="ps-focus__menu-more">
    <div class="ps-dropdown__menu ps-js-focus-link-dropdown" style="left:auto; right:0"></div>
</div>
