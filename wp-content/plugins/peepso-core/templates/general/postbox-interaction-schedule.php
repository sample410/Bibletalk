<?php

$random = rand();
$time_format = get_option('time_format');
$ampm = preg_match('/[gh]/', $time_format);

?><div class="ps-dropdown__menu ps-dropdown__menu--schedule ps-js-postbox-schedule" style="display:none">
    <a role="menuitem" class="ps-dropdown__group" data-option-value="now">
        <div class="ps-checkbox ps-dropdown__group-title">
            <input type="radio" name="peepso_postbox_schedule_<?php echo $random ?>" id="peepso_postbox_schedule_<?php echo $random ?>_now" value="now" checked>
            <label for="peepso_postbox_schedule_<?php echo $random ?>_now">
                <span><?php echo __('Post immediately', 'peepso-core') ?></span>
            </label>
        </div>
    </a>
    <a role="menuitem" class="ps-dropdown__group" data-option-value="future">
        <div class="ps-checkbox ps-dropdown__group-title">
            <input type="radio" name="peepso_postbox_schedule_<?php echo $random ?>" id="peepso_postbox_schedule_<?php echo $random ?>_future" value="future">
            <label for="peepso_postbox_schedule_<?php echo $random ?>_future">
                <span><?php echo __('Select date and time', 'peepso-core') ?></span>
            </label>
        </div>
        <div class="ps-postbox__schedule ps-js-datetime">
            <div class="ps-postbox__schedule-form">
                <div class="ps-postbox__schedule-date">
                    <span class="ps-postbox__schedule-label"><?php echo __('Date', 'peepso-core') ?></span>
                    <select class="ps-select ps-postbox__schedule-select ps-js-date-dd"></select>
                    <select class="ps-select ps-postbox__schedule-select ps-js-date-mm"></select>
                    <select class="ps-select ps-postbox__schedule-select ps-js-date-yy"></select>
                </div>
                <div class="ps-postbox__schedule-time">
                    <span class="ps-postbox__schedule-label"><?php echo __('Time', 'peepso-core') ?></span>
                    <select class="ps-select ps-postbox__schedule-select ps-js-time-hh"></select>
                    <select class="ps-select ps-postbox__schedule-select ps-js-time-mm"></select>
                    <?php if ($ampm) { ?>
                    <select class="ps-select ps-postbox__schedule-select ps-js-time-ampm"></select>
                    <?php } ?>
                </div>
                <div class="ps-postbox__schedule-actions">
                    <button class="ps-btn ps-btn-primary ps-js-done"><?php echo __('Done', 'peepso-core') ?></button>
                </div>
            </div>
        </div>
    </a>
</div>
