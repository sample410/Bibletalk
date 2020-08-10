<div class="ps-location-wrapper ps-js-location-wrapper">
	<div class="ps-location ps-js-location ps-clearfix">
		<div class="ps-location-placeholder ps-js-location-placeholder"><?php echo __('Enter location name...', 'peepso-core'); ?></div>
		<div class="ps-location-loading ps-js-location-loading">
			<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>" alt="" />
		</div>
		<div class="ps-location-result ps-js-location-result">
			<div class="ps-location-header">
				<span class="ps-location-close ps-icon-remove ps-js-close"></span>
				<span><?php echo __('Select location', 'peepso-core'); ?></span>
			</div>
			<div class="ps-location-map ps-js-location-map" style="display:none"></div>
			<div class="ps-location-list ps-js-location-list"></div>
			<a href="#" class="ps-btn ps-btn-small ps-btn-primary ps-js-select"><?php echo __('Select', 'peepso-core'); ?></a>
			<a href="#" class="ps-btn ps-btn-small ps-btn-danger ps-js-remove"><?php echo __('Remove', 'peepso-core'); ?></a>
		</div>
	</div>
	<div class="ps-location-fragment ps-js-location-fragment" style="display:none">
		<a href="#" class="ps-location-listitem ps-js-location-listitem" data-place-id="{place_id}" onclick="return false;">
			<strong class="ps-js-location-listitem-name">{name}</strong><br />
			<small>{description}</small>
		</a>
	</div>
</div>
