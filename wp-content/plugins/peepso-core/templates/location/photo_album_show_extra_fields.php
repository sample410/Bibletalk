<div class="ps-album__description ps-js-album-location">
	<div class="ps-album__description-title">
		<?php echo __('Album location', 'peepso-core'); ?>
		<?php if ($can_edit) { ?>
		<a href="#" class="ps-js-album-location-edit" onclick="return false;">
			<i class="ps-icon-edit"></i>
		</a>
		<a href="#" class="ps-js-album-location-remove" onclick="return false;"
			<?php echo $loc ? '' : 'style="display:none"'; ?>
				data-post-id="<?php echo esc_attr($post_id); ?>">
			<i class="ps-icon-trash"></i>
		</a>
		<?php } ?>
	</div>
	<div>
		<div class="ps-js-album-location-empty" <?php echo $loc ? 'style="display:none"' : ''; ?>>
			<i class="ps-icon-map-marker"></i>
			<span><em><?php echo __('No location', 'peepso-core'); ?></em></span>
		</div>
		<div class="ps-js-album-location-text" <?php echo $loc ? '' : 'style="display:none"'; ?>>
			<a href="#" title="<?php echo $loc ? esc_attr($loc['name']) : ''; ?>"
					onclick="pslocation.show_map(<?php echo $loc ? $loc['latitude'] : ''; ?>, <?php echo $loc ? $loc['longitude'] : ''; ?>, '<?php echo $loc ? esc_attr($loc['name']) : ''; ?>'); return false;">
				<i class="ps-icon-map-marker"></i>
				<span><?php echo $loc ? $loc['name'] : ''; ?></span>
			</a>
		</div>
	</div>
	<div class="ps-js-album-location-editor" style="display:none">
		<input type="text" class="ps-input" value="<?php echo $loc ? esc_attr($loc['name']) : ''; ?>"
			data-location="<?php echo $loc ? esc_attr($loc['name']) : ''; ?>"
			data-latitude="<?php echo $loc ? esc_attr($loc['latitude']) : ''; ?>"
			data-latitude="<?php echo $loc ? esc_attr($loc['longitude']) : ''; ?>"
			data-post-id="<?php echo esc_attr($post_id); ?>" />
		<?php wp_nonce_field('set-album-location', '_wpnonce_set_album_location'); ?>
		<div style="text-align:right">
			<button type="button" class="ps-btn ps-btn-small ps-button-cancel ps-js-cancel"><?php echo __('Cancel', 'peepso-core'); ?></button>
			<button type="button" class="ps-btn ps-btn-small ps-button-action ps-js-submit">
				<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" class="ps-js-loading" alt="loading" style="margin-right:5px;display:none" />
				<?php echo __('Save location', 'peepso-core'); ?>
			</button>
		</div>
	</div>
</div>
