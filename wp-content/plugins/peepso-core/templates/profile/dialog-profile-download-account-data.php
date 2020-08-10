<div class="ps-dialog-wrapper">
	<div class="ps-dialog-container">
		<div class="ps-dialog">
			<div class="ps-dialog-header">
				<span><?php echo __('Download Archive', 'peepso-core'); ?></span>
				<a href="#" class="ps-dialog-close ps-js-cancel"><span class="ps-icon-remove"></span></a>
			</div>
			<div class="ps-dialog-body">
				<form id="form_profile_download_account_data" class="ps-form--profile-download-account-data" onsubmit="return false;">
					<div class="ps-form__row">
						<label class="ps-form__label" for="password-confirmation">
							<?php echo __('Password', 'peepso-core'); ?>
						</label>
						<div class="ps-form__field">
							<input type="password" class="ps-input" id="password-confirmation"
								name="password-confirmation" value="" />
							<span class="ps-text--danger ps-form__helper ps-js-error" style="display:none"></span>
						</div>
					</div>
				</form>
			</div>
			<div class="ps-dialog-footer">
				<div>
					<button type="button" class="ps-btn ps-btn-small ps-button-cancel ps-js-cancel">
						<?php echo __('Cancel', 'peepso-core'); ?>
					</button>
					<button type="button" class="ps-btn ps-btn-small ps-button-action ps-js-submit">
						<img src="<?php echo PeepSo::get_asset('images/ajax-loader.gif'); ?>"
							class="ps-js-loading" alt="loading" style="margin-right:5px;display:none" />
						<?php echo __('Download Archive', 'peepso-core'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
