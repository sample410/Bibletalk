<?php
$PeepSoActivity = PeepSoActivity::get_instance();

if( isset($force_oembed) && true == $force_oembed && isset($content)) {
	// Add specific class for WP_Embed content.
	$css_wpembed = '';
	if ( strpos($content, 'wp-embedded-content') && strpos($content, 'ps-media-iframe') ) {
		if ( FALSE === strpos($content, 'ps-media-iframe--wpembed') ) {
			$content = str_replace( 'ps-media-iframe', 'ps-media-iframe ps-media-iframe--wpembed', $content);
		}
	}

	echo $content;
	if (strpos($content, 'blockquote') !== FALSE) {
		return;
	}
	unset($content);
}

if(!isset($oembed_type) || (isset($oembed_type) && in_array($oembed_type, array('video', 'rich'))) ) {
?>
<div class="ps-media-video">
	<?php if (isset($content) && !empty($content)) { ?>
	<div class="ps-media-thumbnail video-avatar">
		<div class="<?php $PeepSoActivity->content_media_class('media-object'); ?>">
			<?php echo ($content); ?>
		</div>
	</div>
	<?php } ?>
	<div class="ps-media-body video-description">
		<!-- video description -->
		<div class="ps-media-title">
			<a href="<?php echo $url; ?>" rel="nofollow" <?php echo $target; ?>><?php echo ($title); ?></a>
			<small>
				<a href="<?php echo $url; ?>" rel="nofollow" <?php echo $target; ?>><?php echo ($host); ?></a>
			</small>
		</div>
		<div class="ps-media-desc"><?php
			if (isset($description)) {
				echo wp_trim_words($description, 55);
			}
		?></div>
	</div>
</div>
<?php }
