<?php

class PeepSoConfigSectionNotifications extends PeepSoConfigSectionAbstract
{
	public static $css_overrides = array(
		'appearance-avatars-circle',
	);

	// Builds the groups array
	public function register_config_groups()
	{
        $this->context='left';

        $this->notification_previews();

        $this->context='full';

        $this->_group_emails();
	}

    private function notification_previews()
    {

        //$this->args('descript', __('By default the full cover displays only in the header of the "Stream" section'));
        $this->args('default',0);
        $this->set_field(
            'notification_previews',
            __('Enabled', 'peepso-core'),
            'yesno_switch'
        );

        $options = array();
        for($i=5;$i<=500;$i+=5) {
            $options[$i] = $i;// .' '. __('characters','peepso-core');
        }

        //$options[0] = __('Disabled', 'peepso-core');

        $this->args('default', 50);
        $this->args('options', $options);
        $this->args('descript', __('Notification previews will be trimmed to this length.  To avoid cutting words in the middle, the actual length of a preview might be shorter.','peepso-core'));
        $this->set_field(
            'notification_preview_length',
            __('Preview length', 'peepso-core'),
            'select'
        );

        $this->args('descript', __('If a notification is over the limit, the ellipsis will be attached to the end. The length of the potential ellipsis counts into a total notification preview length.','peepso-core'));
        $this->args('default', '...');
        $this->args('maxlength', 10);
        $this->args('size', 10);
        $this->set_field(
            'notification_preview_ellipsis',
            __('Ellipsis', 'peepso-core'),
            'text'
        );

        // Build Group
        $this->set_group(
            'notification_previews',
            __('On-site notification previews (BETA)', 'peepso-core')
        );
    }

    private function _group_emails()
    {
        // # Email Sender
        $this->args('validation', array('validate'));
        $this->args('data', array(
            'rule-min-length' => 1,
            'rule-max-length' => 64,
            'rule-message'    => __('Should be between 1 and 64 characters long.', 'peepso-core')
        ));


        $this->set_field(
            'site_emails_sender',
            __('E-mail sender', 'peepso-core'),
            'text'
        );

        // # Admin Email
        $this->args('validation', array('validate'));
        $this->args('data', array(
            'rule-min-length' => 1,
            'rule-max-length' => 64,
            'rule-message'    => __('Should be between 1 and 64 characters long.', 'peepso-core')
        ));

        $this->set_field(
            'site_emails_admin_email',
            __('Admin E-mail', 'peepso-core'),
            'text'
        );


        // # Disable MailQueue
        $this->args('descript', __('This should be only enabled if you are planning to use an external cron job to process the PeepSo mail queue.<br/>External cron job is recommended for bigger communities.<br/>Please refer to <a href="http://peep.so/mailqueue/" target="_blank">the documentation</a>.', 'peepso-core'));
        $this->set_field(
            'disable_mailqueue',
            __('External mail queue cron job', 'peepso-core'),
            'yesno_switch'
        );

        // # Don't subscribe new members to emails
        $this->args('descript', __('All new members will have their e-mail notifications disabled by default', 'peepso-core'));
        $this->set_field(
            'new_member_disable_all_email_notifications',
            __('Don\'t subscribe new members to any e-mail notifications', 'peepso-core'),
            'yesno_switch'
        );

        $this->set_field(
            'emails_override_full_separator',
            __('Customize the entire e-mail layout', 'peepso-core'),
            'separator'
        );

        $this->set_field(
            'emails_override_full_msg',
            __('Text, HTML and inline CSS only (no PHP or shortcodes). Leave empty for the default layout.','peepso-core')
            . '<br/><br/>'
            . sprintf(__('<a href="%s" target="_blank">Click here</a> after saving to test your changes.','peepso-core'), admin_url('admin-ajax.php?action=peepso_preview_email'))
            .'<br/><br/>'.
            __('Available variables: <br/>{email_contents} - e-mail contents <font color="red">*</font><br/>{unsubscribeurl} - URL of the user notification preferences <font color="red">*</font><br/>{currentuserfullname} - full name of the recipient<br>{useremail} - e-mail of the recipient<br/>{sitename} - the name of your site<br/>{siteurl} - the URL of your site<br/><br/><font color="red">*</font> required variable', 'peepso-core'),
            'message'
        );

        // # Full HTML
        $this->args('raw', TRUE);
        $this->args('validation', array('custom'));
        $this->args('validation_options',
            array(
                'error_message' => __('Missing variable {emails_contents} or {unsubscribeurl}', 'peepso-core'),
                'function' => array($this, 'check_emails_layout')
            )
        );

        $this->set_field(
            'emails_override_entire_html',
            __('Override entire HTML', 'peepso-core'),
            'textarea'
        );

        // Build Group
        $this->set_group(
            'emails',
            __('E-mails', 'peepso-core'),
            __('This section controls the settings and layout of all e-mails sent by PeepSo: notifications, registration, forgot password etc.','peepso-core')
        );
    }

    public function check_emails_layout($value)
    {
        if (!empty($value)) {
            if (strpos($value, 'email_contents') === false || strpos($value, 'unsubscribeurl') === false) {
                return FALSE;
            }
        }

        return TRUE;
    }

}