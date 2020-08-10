<?php
require_once(PeepSo::get_plugin_dir() . 'lib' . DIRECTORY_SEPARATOR . 'install.php');
/*
 * Performs installation process
 * @package PeepSo
 * @author PeepSo
 */
class PeepSoActivate extends PeepSoInstall
{
	const DBVERSION_OPTION_NAME = 'peepso_database_version';
	const DBVERSION = '3';
	
	// these items are stored under the mail 'peepso_config' option
	protected $default_config = array(
		'install_date' => NULL, 					// defined when peepso is first installed

		'system_enable_logging' => 0,				// default logging to OFF
		'system_show_peepso_link' => 0,				// default Powered by PeepSo link to OFF
		'avatars_peepso_only' => 0,
		'system_display_name_style' => 'real_name',
		'system_allow_username_changes' => 0,
		'system_override_name' => 0,
		'appearance-avatars-circle' => 1,

		'site_show_notification_on_navigation_bar' => 0, // default Show notification icons on WP Toolbar
		'site_reporting_enable' => 1,
		'site_reporting_num_unpublish_post' => '0',
		'site_reporting_types' => "Spamming\nAdvertisement\nProfanity\nInappropriate Content/Abusive",

		'site_activity_comments' => 2,
		'activity_comments_batch' => 5,
		'site_activity_readmore' => 1000,
		'site_activity_readmore_single' => 2000,
		'site_activity_open_links_in_new_tab' => 1,
		'site_activity_hide_stream_from_guest' => 0,

		'site_dashboard_reportperiod' => 168,

		'site_advsearch_allowguest' => 1,
		'site_advsearch_email' => '0',

		'site_registration_disabled' => 1,
		'site_registration_enableverification' => 0,
		'site_registration_enable_ssl' => 0,
		'site_registration_enableterms' => 0,
		'site_registration_terms' => '',
		'site_registration_enablerecaptcha' => 0,
		'site_registration_recaptchasecure' => 0,
		'site_registration_recaptchapublic' => '',
		'site_registration_recaptchaprivate' => '',
		'site_registration_allowdelete' => 0,
		'site_registration_recaptchatheme' => 'red',
		'site_registration_recaptchalanguage' => 'English',
		'site_registration_alloweddomains' => '',
		'site_registration_denieddomains' => '',
		'site_registration_header' => 'Get Connected!',
		'site_registration_callout' => 'Come and join our community. Expand your network and get to know new people!',
		'site_registration_buttontext' => 'Join us now, it\'s free!',

		'site_activity_privacy' => 1,
		'site_activity_linknewtab' => 0,
		'site_activity_everyonecomment' => 1,
		'site_activity_hide_stream_from_guest' => 0,

		'site_likes_profile' => 1,

		'site_frontpage_title' => 'PeepSo',
		'site_frontpage_redirectlogin' => 0, // change to page ID
//		'site_frontpage_redirectlogout' => 'frontpage',

		'site_socialsharing_enable' => 1,
		'site_repost_enable' => 0,
//		'site_socialsharing_shareemail' => 1,

		'site_messaging_enable' => 1,

		'site_walls_editcomment' => 1,
		'site_walls_friendswrite' => 1,
		'site_walls_videofriendscomment' => 1,
		'site_walls_photofriendscomment' => 1,
		'site_walls_groupsmemberswrite' => 1,
		'site_walls_eventsresponderswrite' => 1,
		'site_walls_autorefresh' => 1,
		'site_walls_refreshinterval' => 30000,

		'site_timezone_dstoffset' => 0,

		'site_emails_sender' => '{sitename} Community',
		'site_emails_admin_email' => 'no-reply@peepso.com',

		'site_status_limit' => 4000,

		'site_profiles_enablemultiple' => 1,

		'site_filtering_alpha' => 1,
			
		'delete_on_deactivate' => 0,
		'delete_post_data' => 0,
		
		'opengraph_enable' => 1,
		'opengraph_title' => '{sitename}',
		'opengraph_description' => 'Come and join our community. Expand your network and get to know new people!',
		'opengraph_image' => '',

		'gdpr_enable' => 1,
		'moods_enable' => 1,
		'tags_enable' => 1,
		'location_enable' => 0,

		'login_nonce_enable' => 1,

		'brute_force_max_retries' => 3,
		'brute_force_max_lockout' => 5,
		'brute_force_lockout_time' => 15,
		'brute_force_extend_lockout' => 24,
		'brute_force_reset_retries' => 24,
		'brute_force_email_notification' => 0,

		'hovercards_enable' => 1,
		'allow_embed' => 1
// 		'gdpr_personal_data_template_html' => '<html>
// <head>
//   <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
//   <base href="" />
//   <style type="text/css">/* Copyright 2018-present PeepSo. All Rights Reserved. */
// body {
//   color: black;
//   font-family: Arial, sans-serif;
//   font-size: 11pt;
//   margin: 15px auto;
//   width: 860px;
// }

// .nav {
//   float: left;
//   width: 200px;
// }

// .nav ul {
//   margin: 10px 0 0 0;
//   padding: 0;
// }

// .nav li {
//   display: block;
//   font-size: 15px;
//   list-style: none;
//   padding: 5px 10px;
//   width: 160px;
// }

// .nav .selected {
//   background: #EEE;
//   font-weight: bold;
// }

// h1 {
//   border-bottom: 1px solid #CCC;
//   padding-bottom: 10px;
// }

// .contents {
//   padding-left: 210px;
// }

// a, a:visited {
//   color: #10208C;
//   text-decoration: none;
// }

// .meta {
//   color: #888;
//   font-size: 13px;
// }

// .block {
//   margin: 20px 0;
// }

// .warning {
//   background: #FEE;
//   border: 1px solid #A00;
// }

// .user {
//   color: #10208C;
//   margin-right: 4px;
// }

// .comment {
//   background: #EEE;
//   margin: 5px 0;
//   padding: 10px;
// }

// .thread {
//   border: 1px solid #CCC;
//   margin: 0 0 20px 0;
//   padding: 10px;
// }

// .message_header {
//   border-top: 1px solid #CCC;
//   margin: 10px 0 0 0;
//   padding: 10px 0 0 0;
// }

// .message_header .meta {
//   float: right;
// }

// ul {
//   list-style: none;
//   padding: 0;
// }

// th {
//   font-weight: normal;
//   padding: 5px 5px;
//   text-align: left;
//   vertical-align: top;
//   width: 150px;
// }

// td {
//   padding: 5px 5px;
// }

// .footer {
//   clear: both;
//   color: #888;
//   font-size: 13px;
//   margin-top: 10px;
//   text-align: center;
// }

// .warning {
//   color: red;
// }
// </style>
// <title>{data_title}</title>
// </head>
// <body>
//   <div class="nav">
//     <img src="{data_photo}" />
//     {data_sidebar}
//   </div>
//   <div class="contents">
//     <h1>{data_name}</h1>
//     {data_contents}
//   </div>
// </body>
// </html>'
	);

	// these items are stored individually
	protected $extended_config = array(
		'site_registration_terms' => '',
		'site_registration_welcome' => '',
		'site_registration_confirm' => '',
	);

	private $invalid_usernames = array('admin', 'edit', 'sysop', 'owner');

	/*
	 * called on plugin activation; performs all installation tasks
	 */
	public function plugin_activation( $is_core = TRUE )
	{
		$activated = parent::plugin_activation($is_core);

		if ($activated) {
			// Create peepso_users record for each user on the site
			global $wpdb;
			// exclude existing peepso users, in case of update
			$wp_peepso_user_query = "SELECT `usr_id` FROM `{$wpdb->prefix}" . PeepSoUser::TABLE . "` `peepsousers`";
			$peepso_users = $wpdb->get_col($wp_peepso_user_query);

			$args = array('fields' => 'ID');

			if (count($peepso_users) > 0) {
                $args['exclude'] = $peepso_users;
            }

			$user_query = new WP_User_Query($args);
			if (!empty($user_query->results)) {
				foreach ($user_query->results as $user_id) {
					$data = array(
						'usr_id' => $user_id,
						'usr_profile_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_first_name_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_last_name_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_description_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_user_url_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_gender_acc' => PeepSo::ACCESS_PUBLIC,
						'usr_birthdate_acc' => PeepSo::ACCESS_PUBLIC,
					);
					$wpdb->insert($wpdb->prefix . PeepSoUser::TABLE, $data);
				}
			}

			register_post_type('peepso_user_field');

			// install profile fields
			require_once(dirname(__FILE__).'/../classes/profilefields.php');
			PeepSoProfileFields::install();

            // @TODO: #203 promote WP admins to peepso admins
            $user_query = new WP_User_Query( array( 'fields'=>'ID', 'role' => 'Administrator' ) );
            $results = $user_query->results;

            if(count($results)) {
                foreach($results as $user_id){
                    $user = PeepSoUser::get_instance($user_id);
                    $user->set_user_role('admin');
                }
            }


			// TODO: need to use the WP_Filesystem API
			// copy the .htaccess file from the plugins/peepso/ directory to wp-content/peepso/
			#copy(PeepSo::get_plugin_dir() . '.htaccess', PeepSo::get_peepso_dir() . '.htaccess');

			// Update the current user's first name to their login name if and only if the name is blank or not filled out.
			$current_user = get_user_meta(get_current_user_id());
			$first_name = $current_user['first_name'][0];
			$last_name = $current_user['last_name'][0];
			if (empty($first_name) && empty($last_name)) {
				$user = wp_get_current_user();
				update_user_meta(get_current_user_id(), 'first_name', $user->user_login);
			}

			self::reactions_install();

            $defaults = array(
                'blogposts_activity_enable'					=>  0,
                'blogposts_activity_privacy' 				=> 10,
                'blogposts_activity_type_post'				=>	1,
                'blogposts_activity_type_post_text_default'	=> 'wrote a new post',
                'blogposts_activity_type_page_text'			=> 'published a new page',
                'blogposts_activity_type_attachment_text'	=> 'created a new attachment',
                'blogposts_activity_type_revision_text' 	=> 'created a new revision',
                'blogposts_activity_type_nav_menu_item_text'=> 'created a new menu item',
                'blogposts_comments_header_call_to_action'  => __('Get involved!', 'peepso-core'),
                'blogposts_comments_header_comments'        => __('Comments', 'peepso-core'),
                'blogposts_comments_header_reactions'       => __('Reactions', 'peepso-core'),
                'blogposts_comments_header_likes'           => __('Likes', 'peepso-core'),
                'blogposts_authorbox_author_name_pre_text'  => __('About the author:', 'peepso-core'),
            );
            // Set some default settings
            $settings = PeepSoConfigSettings::get_instance();

            foreach($defaults as $key=>$value) {
                if(in_array(PeepSo::get_option($key, NULL), array(NULL,''))) {
                    $settings->set_option($key, $value);
                }
            }
		}

		return ($activated);
	}

	public static function reactions_install() {
        // Reactions
        $defaults = array(

            0 => array(
                'post_title' 		=> __('Like','peepso-core'),
                'post_content' 		=> __('liked','peepso-core'),
                'post_excerpt'		=> 'like_blue.svg',
                'post_status'		=> 'publish',
            ),

            1 => array(
                'post_title'		=> __('Love','peepso-core'),
                'post_content'		=> __('loved','peepso-core'),
                'post_excerpt'		=> 'heart_2764.svg',
                'post_status'		=> 'publish',

            ),

            2 => array(
                'post_title'		=> __('Haha','peepso-core'),
                'post_content'		=> __('laughed at','peepso-core'),
                'post_excerpt'		=> 'face_1f606.svg',
                'post_status'		=> 'publish',

            ),

            3 => array(
                'post_title'		=> __('Wink','peepso-core'),
                'post_content'		=> __('winked at','peepso-core'),
                'post_excerpt'		=> 'face_1f609.svg',
                'post_status'		=> 'publish',
            ),

            4 => array(
                'post_title'		=> __('Wow','peepso-core'),
                'post_content'		=> __('gasped at','peepso-core'),
                'post_excerpt'		=> 'face_1f62e.svg',
                'post_status'		=> 'publish',
            ),

            5 => array(
                'post_title'		=> __('Sad','peepso-core'),
                'post_content'		=> __('is sad about','peepso-core'),
                'post_excerpt'		=> 'face_1f62d.svg',
                'post_status'		=> 'publish',
            ),

            6 => array(
                'post_title'		=> __('Angry','peepso-core'),
                'post_content'		=> __('is angry about','peepso-core'),
                'post_excerpt'		=> 'face_1f620.svg',
                'post_status'		=> 'publish',
            ),

            // Since 2.0.0
            7 => array(
                'post_title'		=> __('Crazy','peepso-core'),
                'post_content'		=> __('feels crazy about','peepso-core'),
                'post_excerpt'		=> 'face_1f60b.svg',
                'post_status'		=> 'publish',
            ),

            8 => array(
                'post_title'		=> __('Speechless','peepso-core'),
                'post_content'		=> __('is speechless about','peepso-core'),
                'post_excerpt'		=> 'face_1f636.svg',
                'post_status'		=> 'publish',
            ),

            9 => array(
                'post_title'		=> __('Grateful','peepso-core'),
                'post_content'		=> __('is grateful for','peepso-core'),
                'post_excerpt'		=> 'rest_1f64f.svg',
                'post_status'		=> 'publish',
            ),

            10 => array(
                'post_title'		=> __('Celebrate','peepso-core'),
                'post_content'		=> __('celebrates','peepso-core'),
                'post_excerpt'		=> 'occa_1f389.svg',
                'post_status'		=> 'publish',
            ),
        );

        $default_args = array(
            'post_type' => 'peepso_reaction',
        );

        $order = 0;

        foreach($defaults as $id=>$args) {

            $search = array_merge($default_args,array('post_parent' => $id));

            $posts = new WP_Query($search);

            if(!count($posts->posts)) {
                $args = array_merge($args, $search);
                $args['menu_order'] = $order;
                $order += 1;
                wp_insert_post($args);
            }
        }
    }

	/*
	 * return default email templates
	 */
	public function get_email_contents()
	{
		$emails = array(
			'email_new_user' => "Hello {userfullname}

Welcome to {sitename}!

Click on this link to verify your email.
{activatelink}
Once approved you will be notified and then be able to login and participate.

Thank you.",
			'email_new_user_no_approval' => "Hello {userfullname}

Welcome to {sitename} community!

Click on this link to verify your email and login to your account.
{activatelink}

Thank you.",
			'email_activity_notice' => "Hello {userfirstname},

The user {fromfirstname} likes what you have to say.

You can see this post here: {permalink}

Thank you.",
			'email_like_post' => "Hello {userfirstname},

The user {fromfirstname} likes your post!

You can see the post here: {permalink}

Thank you.",
			'email_user_comment' => "Hello {userfirstname},

{fromfirstname} had something to say about your post!

You can see the post here:
{permalink}

Thank you.",
			'email_share' => "Hello {userfirstname},

{fromfirstname} had shared your post!

You can see the post here:
{permalink}

Thank you.",
			'email_user_reply_comment' => "Hello {userfirstname},

{fromfirstname} replied to your comment!

You can see it here:
{permalink}

Thank you.",
			'email_like_comment' => "Hello {userfirstname},

The user {fromfirstname} likes your comment!

You can see the post here:
{permalink}

Thank you.",
			'email_wall_post' => "Hello {userfirstname},

{fromfirstname} wrote on your profile!

You can visit your profile here:
{profileurl}
or view the post directly here:
{permalink}

Thank you.",
			'email_password_recover' => "Someone requested that the password be reset for the following account:

Username: {userlogin}

At {siteurl}

If this was a mistake, just ignore this email and nothing will happen.

To reset your password, visit the following address:

{recover_url}

Thank you.",

			'email_password_changed' => "Hello {userfirstname},

You have successfully changed your password.

You can login with the new credentials here: {activityurl}

Thank you.",

			'email_user_approved' => "Your account has been approved. You may now login at {activityurl}",
			'email_like_profile' => "Hello {userfirstname},

{fromfirstname} likes your profile!

You can see all of your notifications here:
{permalink}

Thank you.",
            'email_new_user_registration' => "Hello Administrator,

A new user has signed up! Please welcome {userfullname} with the login name of &rsquo;{userlogin}&rsquo; to the site!

You can activate the user&rsquo;s account here: {permalink}

Thank you.",
            'email_reported_content' => "Hello Administrator,

{userfullname} ({userlogin}) has reported content for review. 

You can see the reported content here: {activityurl} 

You can view all reports here: {permalink}

Thank you.",
			'email_tagged' => "Hello {userfirstname},

{fromfirstname} mentioned you in a post!

You can view the post here:
{permalink}

Thank you.",
			'email_tagged_comment' => "Hello {userfirstname},

{fromfirstname} mentioned you in a comment!

You can view the comment here:
{permalink}

Thank you.",
			'email_export_data_complete' => "Hello {userfirstname},

Your personal data is ready to download. This contains a copy of personal information you've shared on this site. To protect your info, we'll ask you to re-enter your password to confirm that this is your account.

You can download the archive here: {permalink}

Thank you.",
			'email_failed_login_attempts' => "Hello {userfirstname},

{attempts_count} failed login attempts and {attempts_lockout} lockout(s) from IP {attempts_ip}

Last Login Attempt : {attempts_time}
Last User Attempt : {attempts_username}
IP has been blocked until : {attempts_lockout_until}


Thank you.",
		);
		
		return ($emails);
	}

	/*
	 * return default page names information
	 */
	protected function get_page_data()
	{
		// default page names/locations
		$aRet = array(
			'home' => array(
				'title' => __('Home', 'peepso-core'),
				'slug' => '',
				'content' => NULL,
			),
			'activity' => array(							//
				'title' => __('Recent Activity', 'peepso-core'),
				'slug' => 'activity',
				'content' => '[peepso_activity]',
			),

			'profile' => array(								//
				'title' => __('User Profile', 'peepso-core'),
				'slug' => 'profile',
				'content' => '[peepso_profile]'
			),
			'register' => array(							//
				'title' => __('Site Registration', 'peepso-core'),
				'slug' => 'register',
				'content' => '[peepso_register]'
			),
			'recover' => array(								//
				'title' => __('Forgot Password', 'peepso-core'),
				'slug' => 'password-recover',
				'content' => '[peepso_recover]',
			),
			'reset' => array(								//
				'title' => __('Reset Password', 'peepso-core'),
				'slug' => 'password-reset',
				'content' => '[peepso_reset]',
			),
			'members' => array(
				'title' => __('Members', 'peepso-core'),
				'slug' => 'members',
				'content' => '[peepso_members]',
			),
            'external_link_warning' => array(
                'title' => __('You are about to be redirected', 'peepso-core'),
                'slug'  => 'external-link',
                'content' => '[peepso_external_link_warning]',
            )
//			'members-latest',
//			'members-online'
		);
		return ($aRet);
	}

	/**
	 * Returns definitions for plugin tables.
	 * @return array
	 */
	public static function get_table_data()
	{
		$aRet = array(
			'activities' => "
				CREATE TABLE activities (
					act_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					act_owner_id BIGINT(20) UNSIGNED NOT NULL,
					act_external_id INT(11) UNSIGNED DEFAULT '0',
					act_module_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
					act_ip VARCHAR(64) NOT NULL DEFAULT '',
					act_access TINYINT(3) UNSIGNED NOT NULL,
					act_has_replies TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					act_location_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
					act_repost_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
					act_link VARCHAR(100) NULL,
					act_link_title VARCHAR(100) NULL,
					act_link_image_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
					act_description TEXT NULL DEFAULT NULL,
					act_comment_object_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
					act_comment_module_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (act_id),
					INDEX act_owner_index (act_owner_id),
					INDEX act_external_index (act_external_id),
					INDEX act_module_index (act_module_id),
					INDEX act_comment_object_index (act_comment_object_id),
					INDEX act_comment_module_index (act_comment_module_id)
				) ENGINE=InnoDB",
			'activity_hide' => "
				CREATE TABLE activity_hide (
					hide_activity_id BIGINT(11) UNSIGNED NOT NULL,
					hide_user_id BIGINT(11) UNSIGNED NOT NULL,
					UNIQUE INDEX hide_activity_unique_index (hide_activity_id, hide_user_id)
				) ENGINE=InnoDB",
			'blocks' => "
				CREATE TABLE blocks (
					blk_user_id BIGINT(20) UNSIGNED NOT NULL,
					blk_blocked_id BIGINT(20) UNSIGNED NOT NULL,
					UNIQUE INDEX block_unique_index (blk_user_id, blk_blocked_id)
				) ENGINE=InnoDB",
			'likes' => "
				CREATE TABLE likes (
					like_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					like_user_id BIGINT(20) UNSIGNED NOT NULL,
					like_external_id BIGINT(20) UNSIGNED NOT NULL,
					like_module_id SMALLINT(5) UNSIGNED NOT NULL,
					like_type SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
					like_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (like_id),
					INDEX like_external_index (like_external_id),
					UNIQUE INDEX like_module_index (like_user_id, like_module_id, like_external_id),
					INDEX like_user_index (like_user_id)
				) ENGINE=InnoDB",
			'mail_queue' => "
				CREATE TABLE mail_queue (
					mail_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					mail_user_id BIGINT(20) UNSIGNED NULL DEFAULT '0',
					mail_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					mail_recipient VARCHAR(128) NOT NULL,
					mail_subject VARCHAR(200) NOT NULL,
					mail_message TEXT NOT NULL,
					mail_status TINYINT(1) NOT NULL DEFAULT '0',
					mail_attempts TINYINT(1) NOT NULL DEFAULT '0',
					mail_module_id SMALLINT(5) UNSIGNED NULL,
					mail_message_id SMALLINT(5) UNSIGNED NULL,
					mail_error_log TEXT,
					PRIMARY KEY (mail_id),
					INDEX mail_user_index (mail_user_id),
					INDEX mail_status_index (mail_status),
					INDEX mail_module_index (mail_module_id)
				) ENGINE=InnoDB",
			'notifications' => "
				CREATE TABLE notifications (
					not_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					not_user_id BIGINT(20) UNSIGNED NOT NULL,
					not_from_user_id BIGINT(20) UNSIGNED NOT NULL,
					not_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					not_module_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
					not_external_id BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
					not_type VARCHAR(128) NOT NULL,
					not_message VARCHAR(200) NOT NULL,
					not_read TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (not_id),
					INDEX not_user_index (not_user_id),
					INDEX not_from_user_index (not_from_user_id),
					INDEX not_module_index (not_module_id),
					INDEX not_timestamp_index (not_timestamp),
					INDEX not_external_index (not_external_id),
					INDEX not_read_index (not_read)
				) ENGINE=InnoDB",
			'notifications_queue_log' => "
			    CREATE TABLE notifications_queue_log (
			        id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			        user_id BIGINT(20) UNSIGNED NOT NULL,
			        sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			        most_recent_not_id  BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
			        PRIMARY KEY (id)
			    ) ENGINE=InnoDB",
			'report' => "
				CREATE TABLE report (
					rep_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					rep_user_id BIGINT(20) UNSIGNED NOT NULL,
					rep_external_id BIGINT(20) UNSIGNED NOT NULL,
					rep_timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					rep_reason VARCHAR(128) NULL DEFAULT NULL,
					rep_module_id SMALLINT(5) UNSIGNED NOT NULL DEFAULT '1',
					rep_status TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (rep_id),
					INDEX rep_user_index (rep_user_id),
					INDEX rep_external_index (rep_external_id),
					INDEX rep_timestamp_index (rep_timestamp),
					INDEX rep_module_index (rep_module_id),
					INDEX rep_status_index (rep_status)
				) ENGINE=InnoDB",
            'revisions' => "
            CREATE TABLE revisions (
                  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                  post_id int(11) UNSIGNED NOT NULL,
                  user_id bigint(20) UNSIGNED NOT NULL,
                  timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  content_before text NOT NULL,
                  content_after text NOT NULL,
                  PRIMARY KEY (id)
                ) ENGINE=InnoDB",
            'api_rate_limit' => "
            CREATE TABLE api_rate_limit (
                id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                api_name varchar(64) NOT NULL,
                time_group varchar(64) NOT NULL,
                count int(11) NOT NULL,
                attempt_count int(11) NOT NULL,
                last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
                )
                ENGINE=InnoDB",
            'saved_posts' => "
            CREATE TABLE saved_posts (
                  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                  user_id int(11) NOT NULL,
                  post_id int(11) NOT NULL,
                  timestamp datetime NULL,
                  PRIMARY KEY (id),
                  INDEX user_id (user_id,post_id)
            ) ENGINE=InnoDB",
            /*
             * admin - manage everything
             *
             * moderator - manage content in groups / forums (in the future)
             *
             * ban - no access to the site, can't login
             *
             * register  - after registration, not confirmed yet
             *      auto activation: member
             *      admin activation: verified
             *
             * user - wordpress user that's not a PeepSo member
             *
             */
			'users' => "
				CREATE TABLE users (
					usr_id BIGINT(20) UNSIGNED NOT NULL,
					usr_last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					usr_views INT(11) UNSIGNED NOT NULL DEFAULT '0',
					usr_likes INT(11) UNSIGNED NOT NULL DEFAULT '0',
					usr_role ENUM('user', 'member', 'moderator', 'admin', 'ban', 'register', 'verified') DEFAULT 'member',
					usr_send_emails TINYINT(1) NOT NULL DEFAULT '1',
					usr_cover_photo VARCHAR(255) NULL DEFAULT NULL,
					usr_avatar_custom TINYINT(1) NOT NULL DEFAULT '0',
					usr_profile_acc TINYINT(1) NOT NULL DEFAULT '10',
					usr_first_name_acc TINYINT(1) NOT NULL DEFAULT '10',
					usr_last_name_acc TINYINT(1) NOT NULL DEFAULT '10',
					usr_description_acc TINYINT(1) NOT NULL DEFAULT '10',
					usr_user_url_acc TINYINT(1) NOT NULL DEFAULT '10',
					usr_gender CHAR(1) DEFAULT 'u',
					usr_gender_acc TINYINT(1) NOT NULL DEFAULT '10',
					usr_birthdate DATE NULL,
					usr_birthdate_acc TINYINT(1) NOT NULL DEFAULT '10',
					PRIMARY KEY (usr_id),
					INDEX usr_last_activity_index (usr_last_activity),
					INDEX usr_role_index (usr_role),
					INDEX user_avatar_custom_index (usr_avatar_custom)
				) ENGINE=InnoDB",
            'ranking' => "
				CREATE TABLE activity_ranking (
					rank_id int(11) NOT NULL AUTO_INCREMENT,
					rank_act_id int(11) NOT NULL,
					rank_act_date datetime NOT NULL,
					rank_act_comments int(11) NOT NULL,
					rank_act_likes int(11) NOT NULL,
					rank_act_shares int(11) NOT NULL,
					rank_act_views int(11) NOT NULL,
					rank_act_score int(11) NOT NULL,
					PRIMARY KEY (rank_id),
					INDEX act_id (rank_act_id),
					INDEX act_score (rank_act_score)
				  ) ENGINE=InnoDB;",
			'gdpr_request_data' => "
				CREATE TABLE gdpr_request_data (
					request_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					request_user_id BIGINT(20) UNSIGNED NULL DEFAULT '0',
					request_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					request_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					request_file_path VARCHAR(255) NULL DEFAULT NULL,
					request_file_url VARCHAR(255) NULL DEFAULT NULL,
					request_status TINYINT(1) NOT NULL DEFAULT '0',
					request_attempts TINYINT(1) NOT NULL DEFAULT '0',
					request_error_log TEXT,
					PRIMARY KEY (request_id),
					INDEX request_user_index (request_user_id),
					INDEX request_status_index (request_status)
				) ENGINE=InnoDB",
			'brute_force_attempts_logs' => "
				CREATE TABLE login_failed_attempts_logs (
					attempts_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					attempts_username BIGINT(20) UNSIGNED NULL DEFAULT '0',
					attempts_time INT(10) NOT NULL DEFAULT '0',
					attempts_count INT(10) NOT NULL DEFAULT '0',
					attempts_lockout INT(10) NOT NULL DEFAULT '0',
					attempts_ip VARCHAR(100) NOT NULL DEFAULT '',
					attempts_url VARCHAR(255) NOT NULL DEFAULT '',
					attempts_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					attempts_type TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
					PRIMARY KEY (attempts_id),
					UNIQUE KEY ip (attempts_ip, attempts_type)
				) ENGINE=InnoDB",
            'reactions' => "
				CREATE TABLE reactions (
					reaction_id int(11) unsigned NOT NULL AUTO_INCREMENT,
					  reaction_user_id bigint(20) unsigned NOT NULL,
					  reaction_act_id bigint(20) unsigned NOT NULL,
					  reaction_type bigint(20) unsigned NOT NULL DEFAULT '1',
					  reaction_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  PRIMARY KEY (reaction_id),
					  UNIQUE KEY reaction_module (reaction_user_id,reaction_act_id),
					  KEY reaction_external_index (reaction_act_id),
					  KEY reaction_user_index (reaction_user_id)
				) ENGINE=InnoDB",
            'hashtags' => "
				CREATE TABLE hashtags (
					ht_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					ht_name VARCHAR(128),
					ht_count BIGINT(20) UNSIGNED NULL DEFAULT '0',
					ht_last_count DATETIME NULL,
					PRIMARY KEY (ht_id),
					UNIQUE INDEX ht_name (ht_name),
					INDEX ht_count (ht_count)
				) ENGINE=InnoDB",
		);

		return ($aRet);
	}

	protected function migrate_database_tables()
	{
		global $wpdb;
		$wpdb->query('START TRANSACTION');
		$rollback = FALSE;

		$current = intval(get_option(self::DBVERSION_OPTION_NAME, -1));
		if (-1 === $current) {
			$current = 0;
			add_option(self::DBVERSION_OPTION_NAME, $current, NULL, 'no');
		}


		if(0 == $current) {
			$sql = "ALTER TABLE {$wpdb->prefix}peepso_activities CHANGE act_id act_id BIGINT(20) NOT NULL AUTO_INCREMENT";
			$wpdb->query($sql);

			$sql = "UPDATE {$wpdb->prefix}peepso_activities SET act_external_id = act_id WHERE act_external_id = 0";
			$wpdb->query($sql);
		}

		// @since 1.7.2 - #1639 bigger not_type
		if(2 == $current) {
				$sql = "ALTER TABLE {$wpdb->prefix}peepso_notifications CHANGE not_type not_type VARCHAR(128) NOT NULL";
				$wpdb->query($sql);
		}

		if ($rollback) {
			$wpdb->query('ROLLBACK');
		} else {
			$wpdb->query('COMMIT');
		}

		// set the dbversion in the option so we don't keep migrating
		update_option(self::DBVERSION_OPTION_NAME, self::DBVERSION);
	}

	/**
	 * Adds PeepSo specific roles to Wordpress
	 */
	protected function create_roles()
	{
//		$cap = array('read');
//		$res = add_role('peepso_verified', __('PeepSo Verified', 'peepso-core'), $cap);
//		$res = add_role('peepso_member', __('PeepSo Member', 'peepso-core'), $cap);
//		$res = add_role('peepso_moderator', __('PeepSo Moderator', 'peepso-core'), $cap);
//		$res = add_role('peepso_admin', __('PeepSo Administrator', 'peepso-core'), $cap);
//		$res = add_role('peepso_ban', __('PeepSo Banned', 'peepso-core'), $cap);
//		$res = add_role('peepso_register', __('PeepSo Registered', 'peepso-core'), $cap);
	}

	/**
	 * Adds site options to the peepso_config option, which will return an arrya of values.
	 */
	protected function create_options( $is_core = TRUE)
	{
		parent::create_options($is_core);
	}

	/*
	 * Create all of the scheduled events
	 */
	protected function create_scheduled_events()
	{
        wp_schedule_event(current_time('timestamp'), 'five_minutes', PeepSo::CRON_MAILQUEUE);

        if(0==PeepSo::get_option('disable_maintenance')) {
            wp_schedule_event(current_time('timestamp'), 'five_minutes', PeepSo::CRON_MAINTENANCE_EVENT);
        }

        wp_schedule_event(current_time('timestamp'), 'five_minutes', PeepSo::CRON_GDPR_EXPORT_DATA);
	}	
}

// EOF
