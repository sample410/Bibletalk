<div class="psa-starter__page psa-starter__page--split psa-starter__page--customize">
	<div class="psa-starter__column">
		<div class="psa-starter__welcome">
			<?php echo __('The following settings were chosen for the Getting Started to give you a glimpse of PeepSo\'s customization possibilities.','peepso-core');?>
			<br/>
			<?php echo sprintf(__('They can always be adjusted further in %s.','peepso-core'), '<a target="_blank" href="'.admin_url('admin.php?page=peepso_config&tab=appearance').'">'.__('PeepSo &raquo; Configuration &raquo; Appearance','peepso-core').' <i class="fa fa-external-link"></i></a>');?>
		</div>
	<?php

	class PeepSoGettingStartedPeepSoStep2 {
		static function f($key, $label, $desc, $type='checkbox', $args=array()) {
			$value = PeepSo::get_option($key);

			if('separator' == $type) { ?>

				<div class="psa-starter__header psa-starter__header--customize">
					<h1 class="psa-starter__header-title"><?php echo $label;?></h1>
					<p><?php echo $desc;?></p>
				</div>

			<?php } else { ?>
				<div class="psa-starter__header psa-starter__header--option">
					<h3 class="psa-starter__header-subtitle"><?php echo $label;?></h3>

					<?php if('checkbox' == $type) { ?>
						<input class="ace ace-switch ace-switch-2" type="checkbox" name="<?php echo $key;?>" <?php if($value) { echo 'checked="checked"'; }?>>
						<label class="lbl" for="<?php echo $key;?>"></label>
					<?php } ?>

					<?php if('text' == $type) { ?>

						<input type="text" name="<?php echo $key;?>" value="<?php echo $value;?>" size="100"/>
						<button class="button ps-js-btn ps-js-cancel" style="display:none"><?php echo __('Cancel', 'peepso-core'); ?></button>
						<button class="button button-primary ps-js-btn ps-js-save" style="display:none"><?php echo __('Save', 'peepso-core'); ?></button>

					<?php } ?>


					<?php if('select' == $type) { ?>

						<select name="<?php echo $key;?>">
							<?php foreach($args['options'] as $k=>$v) { ?>
								<option value="<?php echo $k;?>" <?php if($k==$value) { echo 'selected="selected"'; }?>>
									<?php echo $v;?>
								</option>
							<?php } ?>
						</select>

					<?php } ?>


					<?php if('image' == $type) { ?>
						<?php
							wp_enqueue_media();

							$is_default = FALSE;
							if (!$value) {
								$value = $args['default'];
								$is_default = TRUE;
							}
						?>
						<input type="hidden" data-type="image" name="<?php echo $key;?>" value="<?php echo $value;?>"/>
						<button class="button button-primary ps-js-btn ps-js-select"><?php echo __('Select Image', 'peepso-core'); ?></button>
						<button class="button button-link-delete ps-js-btn ps-js-remove" <?php echo $is_default ? 'style="display:none"' : '' ?>><?php echo __('Remove Image', 'peepso-core'); ?></button>
						<span style="line-height:26px; display:none"><img src="images/loading.gif" /></span>
						<i class="ace-icon fa fa-check bigger-110" style="color:green; line-height:26px; display:none"></i>
						<span class="ps-js-notice" style="line-height:26px; <?php echo $is_default ? '' : 'display:none' ?>"><?php echo __('Default image selected', 'peepso-core'); ?></span>
						<img class="img-responsive img-landing-page-preview ps-js-img" src="<?php echo $value;?>"
							data-defaultsrc="<?php echo $args['default'];?>"
							style="margin-top:10px" />
					<?php } ?>

					<?php if('placeholder' != $type && 'image' != $type) { ?>
						<span style="display:none"><img src="images/loading.gif" /></span>
						<i class="ace-icon fa fa-check bigger-110" style="color:green; display:none"></i>
					<?php } ?>

					<?php if(strlen($desc)) { ?>
						<p><?php echo $desc; ?></p>
					<?php } ?>
				</div>
			<?php
			}
		}
	}

		$gs=new PeepSoGettingStartedPeepSoStep2();

		$gs::f(
			'',
			__('Appearance','peepso-core'),
			__('PeepSo general appearance options.','peepso-core'),
			'separator'
		);

		// COLOR SCHEME
		$options = array(
			'' => __('Light', 'peepso-core'),
		);

		$dir =  plugin_dir_path(__FILE__).'/../css';

		$dir = scandir($dir);
		$from_key   = array( 'template-', '.css' );
		$to_key     = array( '' );

		$from_name  = array( '_', '-' );
		$to_name    = array( ' ',' ' );

		foreach($dir as $file){
			if('template-' == substr($file, 0, 9) && !strpos($file, 'rtl') && !strpos($file, 'round')) {

				$key=str_replace($from_key, $to_key, $file);
				$name=str_replace($from_name, $to_name, $key);
				$options[$key]=ucwords($name);
			}
		}

		$gs::f(
				'site_css_template',
				__('Color scheme', 'peepso-core'),
				sprintf(
					__('Pick a color from the list that suits your site best. If the list doesn’t contain the color you’re looking for you can always use %s.', 'peepso-core'),
					'<a target="_blank" href="https://peep.so/docs_css_overrides">'.__('CSS overrides','peepso-core').' <i class="fa fa-external-link"></i></a>'
				),
			'select',
				array('options'=>$options)
		);

		// ROUNDED CORNERS
		$options = array(
			0 => __('Square','peepso-core'),
			1 => __('Rounded','peepso-core'),
		);
		$gs::f(
			'site_css_rounded',
			__('Corner shape', 'peepso-core'),
			__('Whether PeepSo will have a square or rounded cornered design.','peepso-core'),
			'select',
			array('options'=>$options)
		);


		// Profiles separator
		$gs::f(
			'',
			__('Profiles','peepso-core'),
			__('Options related to user profiles','peepso-core'),
			'separator'
		);

		// Display name style
		$options = array(
			'real_name' => __('Real names', 'peepso-core'),
			'username' => __('Usernames', 'peepso-core'),
		);

		$gs::f(
			'system_display_name_style',
			__('Display name style', 'peepso-core'),
			__('Do you want your community to use real names or usernames?', 'peepso-core'),
			'select',
			array('options'=>$options)
		);

		// Use Square Avatars
		$options = array(
			0 => __('Round','peepso-core'),
			1 => __('Square','peepso-core'),
		);

		$gs::f(
			'appearance-avatars-circle',
			__('Avatar shape', 'peepso-core'),
			__('How would you like the avatars to be displays throughout your community?','peepso-core'),
			'select',
			array('options'=>$options)
		);

		$options = array(
			0 => __('No', 'peepso-core'),
			1 => __('Yes', 'peepso-core'),
		);

		$gs::f(
			'site_registration_disabled',
			__('Disable registration', 'peepso-core'),
			__('Enabled: registration through PeepSo becomes impossible and is not shown anywhere in the front-end. Use only if your site is a closed community or registrations are coming in through another plugin.','peepso-core'),
			'select',
			array('options'=>$options)
		);

		// Registration separator
		$gs::f(
				'',
				__('Landing page','peepso-core'),
				__('Encourage people to join your community with the following options. These are shown as a part of the landing page - [peepso_activity] shortcode.<br/>Please note, the landing page is visible only to users who are not logged in. You can take a look at it in incognito mode in your browser.','peepso-core'),
				'separator'
		);


		// Image

//        $gs::f(
//            'landing_page_image_header',
//            __('Landing page image', 'peepso-core'),
//            __('Suggested size is: 1140px x 469px.', 'peepso-core'),
//            'placeholder'
//        );
//
//        $gs::f(
//            'landing_page_image',
//            '',
//            '',
//            'text'
//        );
//
//        $gs::f(
//            'landing_page_image_default',
//            '',
//            '',
//            'text'
//        );

		// Callout header
		$gs::f(
			'site_registration_header',
			__('Callout header', 'peepso-core'),
			'',
			'text'
		);


		// Callout text
		$gs::f(
			'site_registration_callout',
			__('Callout text', 'peepso-core'),
			'',
			'text'
		);


		// Button text
		$gs::f(
			'site_registration_buttontext',
			__('Button text', 'peepso-core'),
			'',
			'text'
		);

		// Landing page image
		$gs::f(
			'landing_page_image',
			__('Landing page image', 'peepso-core'),
			'',
			'image',
			array('default'=>PeepSo::get_asset('images/landing/register-bg.jpg'))
		);
	?>
	</div>

