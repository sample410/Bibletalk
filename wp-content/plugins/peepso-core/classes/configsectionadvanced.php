<?php

class PeepSoConfigSectionAdvanced extends PeepSoConfigSectionAbstract
{
	public static $css_overrides = array(
		'appearance-avatars-circle',
	);

	// Builds the groups array
	public function register_config_groups()
	{

        $stats = isset($_GET['stats']) ? TRUE : FALSE;
        $filesystem = isset($_GET['filesystem']) ? TRUE : FALSE;

		$this->context='full';


        if(!$stats) {
            $this->_group_filesystem();
        }

        if(  !$filesystem && !$stats ) {
            $this->_group_uninstall();
            $this->_group_profiles();

            $this->context = 'left';
            $this->_group_opengraph();
            $this->_group_socialsharing();
            $this->_group_gdpr();
        }

        $this->context = 'right';

        if(!$filesystem) {
            $this->stats();
        }

        if(  !$filesystem && !$stats ) {
            $this->_group_performance();

            $this->_group_storage();
            $this->_group_security();
            $this->_group_debug();
        }

	}

    private function stats() {

        $this->args('descript', 'Help us improve PeepSo by sending some important statistical information we can use to understand our users better. <br/><br/>When enabled, our servers will receive and store your environment information (PHP version, WordPress version, locale used), some basic information about your set-up (how many users there are, what plugins and themes are used) and a few key PeepSo configuration options.<br/><br/>This data will help us focus our efforts better based on real world scenarios.');
        $this->set_field(
            'optin_stats',
            __('Enable usage tracking', 'peepso-core'),
            'yesno_switch'
        );

        $this->set_group(
            'stats',
            __('Help us improve PeepSo!', 'peepso-core')
        );
    }

    private function _group_gdpr() {
        $section = 'gdpr_';


        $message = __('The EU General Data Protection Regulation (GDPR, or EUGDPR for short) is a regulation in European Union law on data protection and privacy for all individuals within the European Union. All businesses and websites processing personal information of EU citizens must abide by this law, including the right to be forgotten (data deletion), the right to full data download (export) etc. You can read more about it ', 'peepso-core');
        $message .= '<a href="http://peep.so/gdpr" target="_blank">';
        $message .= __('here', 'peepso');
        $message .= '</a>';

        $this->set_field(
                $section, $message, 'message'
        );

        $this->set_field(
            $section . 'enable',
            __('Enable GDPR Compliance', 'peepso-core'),
            'yesno_switch'
        );

        $args = array(
            'descript' => sprintf(__("It's advised to switch this setting on and setup a server-side cron job. You can use this command: wget %s It can easily run every five minutes.", 'peepso-core'), get_bloginfo('url') . '/?peepso_gdpr_export_data_event'),
            'int' => TRUE,
            'default' => 0,
            'field_wrapper_class' => 'controls col-sm-8',
            'field_label_class' => 'control-label col-sm-4',
        );
        $this->args = $args;
        $this->set_field(
                'gdpr_external_cron', __('External Export Cron Job', 'peepso-core'), 'yesno_switch'
        );

        // # Full HTML
        // # Move to stage 2
        // $this->args('raw', TRUE);
        // $this->args('validation', array('custom'));
        // $this->args('validation_options',
        //     array(
        //     'error_message' => __('Missing variable {data_contents} or {data_title} or {data_name} or {data_sidebar}', 'peepso-core'),
        //     'function' => array($this, 'check_gdpr_template_layout')
        //     )
        // );

        // $this->set_field(
        //     $section . 'personal_data_template_html',
        //     __('Override entire HTML Template', 'peepso-core'),
        //     'textarea'
        // );

        // Build Group
        $this->set_group(
                'gdpr', __('GDPR Compliance (BETA)', 'peepso-core')
        );
    }

	private function _group_profiles() {

	    $tabs = apply_filters('peepso_navigation_profile', array());
	    $tablist='';
	    foreach($tabs as $id=>$tab) {
	        if(in_array($tab,array('stream','about'))) {
                $tablist.="<br>$id";
	            continue;
            }

            $tablist.="<br><strong>$id</strong>";
        }

        $this->args('raw', TRUE);
        $this->args('descript', sprintf(__('One tab name per line. "Stream" and "About" will always be first. Current order: %s', 'peepso-core'), $tablist));

        $this->set_field(
            'profile_tabs_order',
            __('Profile tabs order (beta)', 'peepso-core'),
            'textarea'
        );


        $this->set_group(
            'profiles',
            __('Profiles', 'peepso-core')
        );
    }

	private function _group_filesystem()
	{

		// Message Filesystem
		$this->set_field(
			'system_filesystem_warning',
			__('This setting is to be changed upon very first PeepSo activation or in case of site migration. If changed in any other case it will result in missing content including user avatars, covers, photos etc. (error 404).', 'peepso-core'),
			'warning'
		);

		// Message Filesystem
		$this->set_field(
			'system_filesystem_description',
			sprintf(__('PeepSo allows users to upload images that are stored on your server. Enter a location where these files are to be stored.<br/>This must be a directory that is writable by your web server and and is accessible via the web. If the directory specified does not exist, it will be created.<br/>When empty, PeepSo uses following directory: <b>%s</b>', 'peepso-core'), WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'peepso'),
			'message'
        );

		$this->args('class','col-xs-12');
		$this->args('field_wrapper_class','controls col-sm-10');
		$this->args('field_label_class', 'control-label col-sm-2');

		$this->args('validation', array('custom'));
		$this->args('validation_options',
			array(
			'error_message' => __('Can not write to directory', 'peepso-core'),
			'function' => array($this, 'check_wp_filesystem')
			)
		);
		// Uploads
		$this->set_field(
			'site_peepso_dir',
			__('Uploads Directory', 'peepso-core'),
			'text'
		);

		$this->set_group(
			'filesystem',
			__('File System Override', 'peepso-core')
		);
	}

    private function _group_debug()
    {
        // Logging
        $this->args('descript', __('Enabled: various debug information is written to a log file.','peepso-core').'<br/>'.__('This can impact website speed and should ONLY be enabled when someone is debugging PeepSo.', 'peepso-core'));
        $this->set_field(
            'system_enable_logging',
            __('Enable Logging', 'peepso-core'),
            'yesno_switch'
        );

        // FSTVL
        $this->args('descript', __('Strict Version Lock makes sure that it\'s impossible to upgrade PeepSo before all of the child plugins have been updated.','peepso-core').'<br/>'.__('Please DO NOT enable this unless you are having issues with updating PeepSo.', 'peepso-core'));
        $this->set_field(
            'override_fstvl',
            __('Override Strict Version Lock', 'peepso-core'),
            'yesno_switch'
        );

        $this->set_group(
            'advanced_debug',
            __('Maintenance & debugging', 'peepso-core')
        );
    }
    
    private function _group_performance()
    {
        // Infinite scroll
        $options = array(
            0 => __('No', 'peepso-core'),
            1 => __('Everywhere', 'peepso-core'),
            2 => __('Mobile only', 'peepso-core'),
            3 => __('Desktop only', 'peepso-core'),
        );

        $this->args('options', $options);
        $this->args('descript', __('Disables infinite loading of activity stream posts, member listings etc. To load more content users have to press "load more" button.', 'peepso-core'));
        $this->set_field(
            'loadmore_enable',
            __('Enable "load more:"', 'peepso-core'),
            'select'
        );

        // Repeat "load more" button?
        $this->args('default', 0);

        $options=array();
        for($i = 0; $i<=50; $i+=2){
            $options[$i]= sprintf(__('Every %d items', 'peepso-core'), $i);
            if($i == 0) {
                $options[$i] = __('No', 'peepso-core');
            }
        }

        $this->args('options', $options);

        $this->args('descript', __('By default all posts load in an "infinite scroll".','peepso-core').'<br>'.__('You can choose to have a specified batch of posts to loade before showing the "load button" again.','peepso-core'));
        $this->set_field(
            'loadmore_repeat',
            __('Repeat "load more" button?', 'peepso-core'),
            'select'
        );


        // # Disable Maintenance
        $this->args('descript', __('This should be only enabled if you are planning to use an external cron job to process the PeepSo Maintenance scripts.<br/>External cron job is recommended for bigger communities.<br/>Please refer to <a href="http://peep.so/maintenance/" target="_blank">the documentation</a>.', 'peepso-core'));
        $this->set_field(
            'disable_maintenance',
            __('External Maintenance Cron Job', 'peepso-core'),
            'yesno_switch'
        );

        // Experimental faster queries
        $this->args('descript', __('Uses an experimental GROUP and SORT statements to speed up Activity loading on very large websites.', 'peepso-core'));
        $this->set_field(
            'slow_query_fix',
            __('Experimental fast queries (BETA)', 'peepso-core'),
            'yesno_switch'
        );

        // Build Group
        $this->set_group(
            'performance',
            __('Performance', 'peepso-core')
        );
    }

    private function _group_storage()
    {
        // Avatar size
        $default = 250;

        $this->args('default', $default);

        $options=array();
        for($i = 100; $i<=500; $i+=50){
            $options[$i]= sprintf(__('%d pixels', 'peepso-core'), $i);
            if($i == $default) {
                $options[$i] .= ' ('.__('default', 'peepso-core').')';
            }
        }

        $this->args('options', $options);

        $this->args('descript', __('Bigger images use more storage, but will look better - especially on high resolution screens.','peepso-core'));
        $this->set_field(
            'avatar_size',
            __('Avatar size', 'peepso-core'),
            'select'
        );

        // Avatar quality
        $default = 85;
        $this->args('default', $default);

        $options=array();
        for($i = 50; $i<=100; $i+=5){
            $options[$i]= sprintf(__('%d%%', 'peepso-core'), $i);
            if($i == $default) {
                $options[$i] .= ' ('.__('default', 'peepso-core').')';
            }
        }

        $this->args('options', $options);

        $this->args('descript', __('Higher quality will use more storage, but the images will look better','peepso-core'));
        $this->set_field(
            'avatar_quality',
            __('Avatar quality', 'peepso-core'),
            'select'
        );

        // Cover width
        $default = 3000;

        $this->args('default', $default);

        $options=array();
        for($i = 1000; $i<=5000; $i+=500){
            $options[$i]= sprintf(__('%d pixels', 'peepso-core'), $i);
            if($i == $default) {
                $options[$i] .= ' ('.__('default', 'peepso-core').')';
            }
        }

        $this->args('options', $options);

        $this->args('descript', __('Bigger images use more storage, but will look better - especially on high resolution screens.','peepso-core'));
        $this->set_field(
            'cover_width',
            __('Cover width', 'peepso-core'),
            'select'
        );


        // Cover quality
        $default = 85;
        $this->args('default', $default);

        $options=array();
        for($i = 50; $i<=100; $i+=5){
            $options[$i]= sprintf(__('%d%%', 'peepso-core'), $i);
            if($i == $default) {
                $options[$i] .= ' ('.__('default', 'peepso-core').')';
            }
        }

        $this->args('options', $options);

        $this->args('descript', __('Higher quality will use more storage, but the images will look better','peepso-core'));
        $this->set_field(
            'cover_quality',
            __('Cover quality', 'peepso-core'),
            'select'
        );

        // Build Group
        $this->set_group(
            'storage',
            __('Storage', 'peepso-core'),
            __('These settings control the dimensions and compression levels, and will only be applied to newly uploaded images.', 'peepso-core')
        );
    }

    private function _group_security()
    {


        // external link warning
        $this->args('descript', __('When enabled, users will be shown a warning page when clicking an external link inside any PeepSo page. The warning page is the one containing peepso_external_link_warning shortcode.','peepso-core'));
        $this->set_field(
            'external_link_warning',
            __('Enable "external link warning" page', 'peepso-core'),
            'yesno_switch'
        );

        // external link warning
        $this->args('descript', __('Enable to force the warning page even for configured social sharing providers.','peepso-core'));
        $this->set_field(
            'external_link_warning_social_sharing',
            __('Include social sharing links', 'peepso-core'),
            'yesno_switch'
        );

        // external link whitelist
        $this->args('raw', TRUE);
        $this->args('descript', __('Domains that do not require a warning page, without "www" or "http(s). One domain name per line. Your website is excluded by default. ','peepso-core').'<br/>'.__('Example domains:','peepso-core').'<br/>google.com<br/>yahoo.com');

        $this->set_field(
            'external_link_whitelist',
            __('Excluded domains', 'peepso-core'),
            'textarea'
        );

        /** PASSWORD RESET **/
        // # Separator Brute Force
        $this->set_field(
            'separator_password_reset',
            __('Resetting passwords', 'peepso-core'),
            'separator'
        );

        $options=array();

        for($i=1;$i<=4;$i++) {
            $options[$i]=gmdate("H:i", $i*60);
        }

        for($i=5;$i<=30;$i+=5) {
            $options[$i]=gmdate("H:i", $i*60);
        }

        $this->args('validation', array('numeric'));
        $this->args('int', TRUE);
        $this->args('default', 15);
        $this->args('options', $options);
        $this->args('descript', __('hours:minutes - time required between password reset attempts','peepso-core'));
        $this->set_field(
            'brute_force_password_reset_delay',
            __('Password reset delay', 'peepso-core'),
            'select'
        );


        /** LOGIN SECURITY **/
        // # Separator Brute Force
        $this->set_field(
            'separator_brute_force',
            __('Login security', 'peepso-core'),
            'separator'
        );

        $this->set_field(
            'msg_login_security',
            __('It\'s recommended to enable at least one of these options: "nonce check" and/or "brute force protection" and to keep passwords at least 10 characters long.','peepso-core'),
            'message'
        );

        // Max failed attempts
        $options=array();

        for($i=5;$i<=20;$i+=1) {
            $options[$i]=$i . ' ' . __('characters','peepso-core');
        }

        $this->args('default', 10);
        $this->args('options', $options);
        $this->args('descript', __('Applies only to new passwords.','peepso-core'));
        $this->set_field(
            'minimum_password_length',
            __('Minimum password length', 'peepso-core'),
            'select'
        );

        $this->args('default', 1);
        $this->args('descript', __('Recommended. Disable only if you are experiencing caching issues.','peepso-core'));
        $this->set_field(
            'login_nonce_enable',
            __('Login nonce check', 'peepso-core'),
            'yesno_switch'
        );

        $this->args('descript', __('Recommended. Maximum failed attempts allowed.','peepso-core'));
        $this->set_field(
            'brute_force_enable',
            __('Enable login brute force protection', 'peepso-core'),
            'yesno_switch'
        );


        // Max failed attempts
        $options=array();

        for($i=3;$i<=15;$i+=1) {
            $options[$i]=$i . ' ' . __('failed attempts','peepso-core');
        }

        $this->args('default', 3);
        $this->args('options', $options);
        $this->args('descript', __('Maximum failed attempts allowed.','peepso-core'));
        $this->set_field(
            'brute_force_max_retries',
            __('Block login after', 'peepso-core'),
            'select'
        );

        // Block time
        $options=array();

        for($i=15;$i<=120;$i+=15) {
            $options[$i]=gmdate("H:i", $i*60);
        }

        $this->args('validation', array('numeric'));
        $this->args('int', TRUE);
        $this->args('default', 15);
        $this->args('options', $options);
        $this->args('descript', __('hours:minutes - how long to block login attempts after the above limit is reached.','peepso-core'));
        $this->set_field(
            'brute_force_lockout_time',
            __('Block for', 'peepso-core'),
            'select'
        );

        // Email
        $keys = array(0,1,2,3,4,5,6,7,8,9,10);
        $options=array();
        foreach($keys as $i) {

            if(0==$i) {
                $options[$i] = __('Disabled','peepso-core');
            } else {
                $options[$i]=sprintf(_n('After %s block','After %s blocks', $i,'peepso-core'), $i);
            }
        }
        $this->args('options', $options);
        $this->args('descript', __('Send an e-mail notification to the user, warning them about failed login attempts.','peepso-core'));
        $this->args('default', 0);
        $this->set_field(
            'brute_force_email_notification',
            __('Email Notification', 'peepso-core'),
            'select'
        );

        // Max blocks
        $options=array();

        for($i=1;$i<=10;$i+=1) {
            $options[$i]=$i . ' ' . __('login blocks','peepso-core');
        }

        $this->args('options', $options);
        $this->args('default', 5);
        $this->args('descript', __('Additional security when users block themselves repeatedly.','peepso-core'));
        $this->set_field(
            'brute_force_max_lockout',
            __('Enable additional block after', 'peepso-core'),
            'select'
        );

        // Extend block
        $keys = array(6,12,24,48,72);
        $options=array();
        foreach($keys as $i) {
            $options[$i]=$i . ' ' . __('hours', 'peepso-core');
        }
        $this->args('options', $options);

        $this->args('descript', __('How long to block login attempts when additional security is triggered.','peepso-core'));
        $this->args('default', 24);


        $this->set_field(
            'brute_force_extend_lockout',
            __('Additional block length', 'peepso-core'),
            'select'
        );

        // Reset retries
        $keys = array(24,48,72);
        $options=array();
        foreach($keys as $i) {
            $options[$i]=$i . ' ' . __('hours', 'peepso-core');
        }
        $this->args('options', $options);

        $this->args('descript', __('How long it takes for the system to "forget" about a failed login attempt.','peepso-core'));
        $this->args('default', 24);
        $this->set_field(
            'brute_force_reset_retries',
            __('Reset retries after', 'peepso-core'),
            'select'
        );

        // IP whitelist
        $this->args('raw', TRUE);
        $this->args('descript', __('One per line. ','peepso-core').__('Example IP:','peepso-core').'<br/>8.8.8.8<br/>4.4.4.4');

        $this->set_field(
            'brute_force_whitelist_ip',
            __('IP whitelist', 'peepso-core'),
            'textarea'
        );


        // Build Group
        $this->set_group(
            'security',
            __('Security', 'peepso-core')
        );
    }



	private function _group_uninstall()
	{
		// # Delete Posts and Comments
		$this->args('field_wrapper_class', 'controls col-sm-8 danger');

		$this->set_field(
			'delete_post_data',
			__('Delete Post and Comment data', 'peepso-core'),
			'yesno_switch'
		);

		// # Delete All Data And Settings
		$this->args('field_wrapper_class', 'controls col-sm-8 danger');

		$this->set_field(
			'delete_on_deactivate',
			__('Delete all data and settings', 'peepso-core'),
			'yesno_switch'
		);

		// Build Group
		$summary= __('When set to "YES", all <em>PeepSo</em> data will be deleted upon plugin Uninstall (but not Deactivation).<br/>Once deleted, <u>all data is lost</u> and cannot be recovered.', 'peepso-core');
		$this->args('summary', $summary);

		$this->set_group(
			'peepso_uninstall',
			__('PeepSo Uninstall', 'peepso-core'),
			__('Control behavior of PeepSo when uninstalling / deactivating', 'peepso-core')
		);
	}

	private function _group_opengraph()
	{
		$this->set_field(
			'opengraph_enable',
			__('Enable Open Graph', 'peepso-core'),
			'yesno_switch'
		);

		// Open Graph Title
		$this->set_field(
			'opengraph_title',
			__('Title (og:title)', 'peepso-core'),
			'text'
		);

		// Open Graph Title
		$this->set_field(
			'opengraph_description',
			__('Description (og:description)', 'peepso-core'),
			'textarea'
		);

		// Open Graph Image
		$this->set_field(
			'opengraph_image',
			__('Image (og:image)', 'peepso-core'),
			'text'
		);


        // # Separator
        $this->set_field(
            'separator_advanced_seo',
            __('Advanced SEO', 'peepso-core'),
            'separator'
        );

        // Disable "?" in Profile / Group / Activity URLs
        $this->args('descript', __('This feature is currently in BETA and should be considered experimental. It will remove "?" from certain PeepSo URLs, such as "profile/?username/about".', 'peepso-core'));
        $this->set_field(
            'disable_questionmark_urls',
            __('Enable SEO Friendly links', 'peepso-core'),
            'yesno_switch'
        );

        $frontpage = get_post(get_option('page_on_front'));

        if (1 == PeepSo::get_option('disable_questionmark_urls', 0) && 'page' == get_option( 'show_on_front' ) && has_shortcode($frontpage->post_content, 'peepso_activity')) {
            $this->set_field(
                'activity_homepage_warning',
                __('You are currently using [peepso_activity] as your home page. Because of that, single activity URLs will have to contain "?" no matter what the above setting is.', 'peepso-core'),
                'message'
            );
        }


        // PeepSo::reset_query()
        $this->args('descript', __('This advanced feature causes PeepSo pages to override the global WP_Query for better SEO.','peepso-core').'<br>'.__('This can interfere with SEO plugins, so use with caution.', 'peepso-core'));
        $this->set_field(
            'force_reset_query',
            __('PeepSo can reset WP_Query', 'peepso-core'),
            'yesno_switch'
        );


		$this->set_group(
			'opengraph',
			__('SEO & Open Graph', 'peepso-core'),
			__("The Open Graph protocol enables sites shared for example to Facebook carry information that render shared URLs in a great way. Having a photo, title and description. You can learn more about it in our documentation. Just search for 'Open Graph'.", 'peepso-core')
		);
	}

    private function _group_socialsharing()
    {
        // Profile Sharing
        $this->args('descript',__('User profiles are shareable to social networks', 'peepso-core'));
        $this->set_field(
            'profile_sharing',
            __('Profiles Social Sharing', 'peepso-core'),
            'yesno_switch'
        );

        // Activity Social Sharing
        $this->args('descript', __('This feature is currently in BETA and should be considered experimental.', 'peepso-core'));
        $this->set_field(
            'activity_social_sharing_enable',
            __('Activity Social Sharing', 'peepso-core'),
            'yesno_switch'
        );

        $this->set_field(
            'separator_social_sharing_providers',
            __('Enabled social networks', 'peepso-core'),
            'separator'
        );

        $links = PeepSoShare::get_instance();
        $links = $links->get_links(TRUE);

        foreach($links as $key=>$link) {
            $this->args('default', 1);
            $this->set_field(
                'activity_social_sharing_provider_'.$key,
                $link['label'],
                'yesno_switch'
            );
        }


        $this->set_group(
            'socialsharing',
            __('Social sharing (to other networks)', 'peepso-core'),
            __("Allows your users easier ways of sharing your Community content in the \"mainstream\" social networks.", 'peepso-core')
        );
    }

	/**
	 * Checks if the directory has been created, if not use WP_Filesystem to create the directories.
	 * @param  string $value The peepso upload directory
	 * @return boolean
	 */
	public function check_wp_filesystem($value)
	{
		$form_fields = array('site_peepso_dir');
		$url = wp_nonce_url('admin.php?page=peepso_config&tab=advanced', 'peepso-config-nonce', 'peepso-config-nonce');

		if (FALSE === ($creds = request_filesystem_credentials($url, '', false, false, $form_fields))) {
			return FALSE;
		}

		// now we have some credentials, try to get the wp_filesystem running
		if (!WP_Filesystem($creds)) {
			// our credentials were no good, ask the user for them again
			request_filesystem_credentials($url, '', true, false, $form_fields);
			return FALSE;
		}

		global $wp_filesystem;

		if (!$wp_filesystem->is_dir($value) || !$wp_filesystem->is_dir($value . DIRECTORY_SEPARATOR . 'users')) {
			$wp_filesystem->mkdir($value);
			$wp_filesystem->mkdir($value . DIRECTORY_SEPARATOR . 'users');
			return TRUE;
		}

		return $wp_filesystem->is_writable($value);
	}

    public function check_gdpr_template_layout($value) 
    {
        if (!empty($value)) {
            if (strpos($value, 'data_contents') === false || strpos($value, 'data_sidebar') === false || strpos($value, 'data_name') === false || strpos($value, 'data_title') === false) {
                return FALSE;
            }
        }

        return TRUE;
    }

}