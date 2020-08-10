<?php

$small_thumbnail = PeepSo::get_option('small_url_preview_thumbnail', 0);

?><div class="url-preview <?php echo $small_thumbnail ? '' : 'ps-stream-container-narrow' ?>">
	<div class="close"><a href="#" class="remove-preview"><i class="ps-icon-remove"></i></a></div>
	<?php PeepSoTemplate::exec_template('activity', 'content-media', $media); ?>
</div>