<?php
// $commentdata['comment_date_gmt'] = current_time('mysql', 1);

/*
 * Implementation of the Activty Stream
 */
class PeepSoActivity extends PeepSoAjaxCallback
{
    private $post_list = NULL;
    private $post_idx = 0;

    public $query_type = NULL;				// type of query, the CPT name. used in filter_post_clauses() to adjust WHERE
    public $post_query = NULL;				// WP_Query instance for post queries
    public $post_data = NULL;				// $posts value returned from latest show_post() call

	public $pinned = FALSE;					// TRUE = get only pinned posts
	public $stream_id = 1;					// MODULE_ID of the stream tab
    public $is_loading_stream = FALSE;      // TRUE - we are actually loading ites on the stream (not editing etc)

    public $comment_query = NULL;			// WP_Query instance for comment queries
    public $comment_data = NULL;			// $posts value returned from latest show_comment() call

    const TABLE_NAME = 'peepso_activities';
    const HIDE_TABLE_NAME = 'peepso_activity_hide';
    const BLOCK_TABLE_NAME = 'peepso_blocks';
    const SAVED_POSTS_TABLE_NAME = 'peepso_saved_posts';

    const MODULE_ID = 1;

    private $owner_id = 0;					// used in modifying query
    private $user_id = NULL;				// used to override the user_id in unit tests
//	private $current_user = 0;				// used in modifying query
    private $post_media = NULL;				// contains the array for the comment/post box media
    private $peepso_media = array();		// contains the array for the $post_media
    private $last_post_id = NULL; 			// used in filter_since_id() to get posts after this ID
    private $first_post_id = NULL; 			// used in filter_before_id() to get posts before this ID
    private $oembed_title = NULL;
    private $oembed_description = NULL;

    const ACTIVITY_LIMIT_PAGE_LOAD = 1;
    const ACTIVITY_LIMIT_BELOW_FOLD = 3;

    /**
     * Called from PeepSoAjaxHandler
     * Declare methods that don't need auth to run
     * @return array
     */
    public function ajax_auth_exceptions()
    {
        $list_exception = array();
        $hide_from_guest = PeepSo::get_option('site_activity_hide_stream_from_guest', 0);
        if(!$hide_from_guest ) {
            array_push($list_exception, 'show_posts_per_page');
        }

        return $list_exception;
    }

    public function __construct()
    {
        parent::__construct();

		add_action('peepso_activity_after_add_post', array(&$this, 'handle_embed_data'), 10, 2);
		add_filter('peepso_activity_post_content', array(&$this, 'activity_post_content'), 10, 1);
		add_filter('peepso_privacy_access_levels', array(&$this, 'privacy_access_levels'), 10, 1);
		add_filter('oembed_dataparse', array(&$this, 'set_media_properties'), 10, 2);
		// Run this last to give priority to addons
		add_filter('peepso_activity_get_post', array(&$this, 'activity_get_post'), 90, 4);

		// add Vine to the list of allowed oEmbed providers
		// fallback for functions that go straight to oEmbed
		wp_oembed_add_provider(
			'#https?://vine\.co/v/([a-z0-9]+)\/?#i', // URL format to match
			'https://vine.co/oembed.{format}', // oEmbed provider URL
			TRUE                               // regex URL format
		);

		/**
		 * @jaworskimatt at 22-07-2016 added singleton = TRUE to the constructor
		 * @jaworskimatt at 13-10-2016 trying to get rid of that flag
		 *
		 * since 1.6.0 the constructor is accessed directly in many places
		 * we need to avoid running the same actions many times
		 *
		 */

        // hide activity from groups if groupso deactivate #1760
        add_filter('peepso_action_activity_hide_before', array(&$this, 'activity_hide_before'), 10, 3);
	}

	public function init()
	{
		add_action('peepso_activity_post_attachment', array(&$this, 'content_attach_media'), 20);
		add_action('peepso_activity_comment_attachment', array(&$this, 'content_attach_media'), 10);
		add_action('peepso_activity_delete', array(&$this, 'delete_post_or_comment'));
	}

	/*
	 * Sets the user id to be used for queries
	 * @param int $user The user id that used for queries
	 */
    public function set_user_id($user)
    {
        $this->user_id = $user;
    }

    /*
     * Sets the user id considered as the owner for queries
     * @param int $owner The user id of the owner to be used for queries
     */
    public function set_owner_id($owner)
    {
        $this->owner_id = $owner;
    }

    /*
     * returns the named property value from the current result set entry
     * @param string $prop The name of the property to retrieve
     */
    public function get_prop($prop)
    {
        if (isset($this->post_data[$prop]))
            return ($this->post_data[$prop]);
        return ('');
    }

    /*
     * add a post to an activity stream
     * @param int $owner id of owner - who's Wall to place the post on
     * @param int $author id of author making the post
     * @param string $content the contents of the Activity Stream Post
     * @param array $extra additional data used to create the post
     * @return mixed The post id on success, FALSE on failure
     */
    public function add_post($owner, $author, $content, $extra = array())
    {
        // check owner's permissions
        if (PeepSo::check_permissions($owner, PeepSo::PERM_POST, $author) === FALSE) {
            return (FALSE);
        }

        // Cleaning here, because we cannot call htmlspecialchars while displaying the HTML since we'll be showing
        // some links, if there are any, on the post content.
        $content = htmlspecialchars($content);
        $content = trim(PeepSoSecurity::strip_content($content)); #4360 don't force-trim posts, validation should happen somewhere else
        //$content = substr(trim(PeepSoSecurity::strip_content($content)), 0, PeepSo::get_option('site_status_limit', 4000));

        $repost = NULL;

		$rank = new PeepSoActivityRanking();
        if (!empty($extra['repost']) && $this->get_post($extra['repost']))
		{
            $repost = $this->get_repost_root($extra['repost']);
			$orig_post = $this->get_activity_post($repost);
			$rank->add_share_count($orig_post->act_id);
		}

        // don't do anything if contents are empty
        if (empty($content) && NULL === $repost && !apply_filters('peepso_activity_allow_empty_content', FALSE))
            return (FALSE);

        // create post
        $aPostData = array(
            'post_title'	=> "{$owner}-{$author}-" . time(),
            'post_excerpt'  => $content,
            'post_content'  => $content,
            'post_status'   => 'publish',
//			'post_date'		=> gmdate('Y-m-d H:i:s'), // date('Y-m-d H:i:s'),
//			'post_date_gmt' => date('Y-m-d H:i:s'), // gmdate('Y-m-d H:i:s'),
            'post_author'   => $author,
            'post_type'		=> PeepSoActivityStream::CPT_POST
        );

        $aPostData = apply_filters('peepso_pre_write_content', array_merge($aPostData, $extra), self::MODULE_ID, __FUNCTION__);
        $content = $aPostData['post_content'];

        if(isset($extra['future'])) {

            if(is_numeric($extra['future'])) {
                $extra['future'] = $extra['future'] / 1000;
            } else {
                $extra['future'] = strtotime($extra['future']);
            }

            // Attempt to fix timezone offset
            $extra['future'] = $extra['future'] - 3600 * PeepSoUser::get_gmt_offset(get_current_user_id());

            $aPostData['post_status'] = 'future';

            $aPostData['post_date_gmt'] = gmdate('Y-m-d H:i:s', $extra['future']);
        }

        $id = wp_insert_post($aPostData);


        // add metadata to indicate whether or not to display link previews for this post
        add_post_meta($id, '_peepso_display_link_preview', (isset($extra['show_preview']) ? $extra['show_preview'] : 1), TRUE);

        // check $id for failure?
        if (0 === $id) {
            return (FALSE);
        }

        // add data to Activity Stream data table
        $privacy = (isset($extra['act_access']) ? $extra['act_access'] : PeepSoUser::get_instance($author)->get_profile_accessibility());
        $aActData = array(
            'act_owner_id' => $owner,
            'act_module_id' => (isset($extra['module_id']) ? $extra['module_id'] : self::MODULE_ID),
            'act_external_id' => (isset($extra['external_id']) ? $extra['external_id'] : $id),
            'act_access' => $privacy,
            'act_ip' => PeepSo::get_ip_address(),
            'act_repost_id' => intval($repost),
        );

        $aActData = apply_filters('peepso_activity_insert_data', $aActData);

        global $wpdb;
        $res = $wpdb->insert($wpdb->prefix . self::TABLE_NAME, $aActData);

        if(!is_int($res)) {
            new PeepSoError('Unable to create activities entry: '.$wpdb->last_error.' '.var_export($aActData, TRUE),'error','core-activity');
            wp_delete_post($id);
            return FALSE;
        }

        $act_id = $wpdb->insert_id;
		$rank->add_new($act_id);

        // #419 sticky post privacy - update only if writing on my own wall
        if($aActData['act_module_id'] == self::MODULE_ID && $owner == $author) {
            update_user_meta($author, 'peepso_last_used_post_privacy', $privacy);
        }

        /**
         * @param int The WP post ID
         * @param int The act_id
         */
        do_action('peepso_activity_after_add_post', $id, $act_id);

        add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
        // TODO: let's run the filter on the content before adding it via the wp_insert_post() above.
        // this will remove the need for the wp_update_post().
        // Art: Post ID is required so ID is not yet ready if we need to run this filter before adding via wp_insert_post()
        $filtered_content = apply_filters('peepso_activity_post_content', $content, $id);

        remove_filter('oembed_result', array(&$this, 'oembed_result'));

        wp_update_post(array('ID' => $id, 'post_content' => $filtered_content));

        $this->save_peepso_media($id);

        $note = new PeepSoNotifications();
        // send owner an email
        if ($author !== $owner) {
            $user_owner = PeepSoUser::get_instance($owner);	// get_user_by('id', $owner_id);
            $user_author = PeepSoUser::get_instance($author);	// get_user_by('id', $author_id);
            $orig_post = get_post($id);

            $data = array(
                'permalink' => PeepSo::get_page('activity_status') . $orig_post->post_title,
                'post_content' => $orig_post->post_content,
            );
            $data = array_merge($data, $user_author->get_template_fields('from'), $user_owner->get_template_fields('user'));

            PeepSoMailQueue::add_notification($owner, $data, __('Someone Posted on your profile', 'peepso-core'), 'wall_post', 'wall_post', PeepSoActivity::MODULE_ID);

            $note->add_notification($author, $owner, __('wrote on your profile', 'peepso-core'), 'wall_post', self::MODULE_ID, $id);
        }

        // Send original author a notification if content is shared
        if (NULL !== $repost) {
            $orig_post = $this->get_activity_post($repost);

            $user_owner = PeepSoUser::get_instance($orig_post->post_author);
            $user_author = PeepSoUser::get_instance($author);

            $data = array(
                'permalink' => PeepSo::get_page('activity_status') . $orig_post->post_title,
                'post_content' => $orig_post->post_content,
            );

            $data = array_merge($data, $user_author->get_template_fields('from'), $user_owner->get_template_fields('user'));

            $activity_type = array(
                'type' => 'share',
                'text' => __('post', 'peepso-core'));
            $activity_type = apply_filters('peepso_notifications_activity_type', $activity_type, $orig_post->ID, $act_id);

            PeepSoMailQueue::add_notification($orig_post->post_author, $data, sprintf(__('Someone shared your %s', 'peepso-core'), $activity_type['text']), $activity_type['type'], $activity_type['type'], PeepSoActivity::MODULE_ID);

            $note->add_notification($owner, $orig_post->post_author, __('shared', 'peepso-core'), 'share', self::MODULE_ID, $id);
        }

        return ($id);
    }


    /*
     * adds a comment to the specified post_id
     * @param int $post_id The post id to add the comment to
     * @param int $author_id The user_id of the author adding the comment
     * @param string $content The contents of the commetn to add
     * @param array $extra optional extra information for the comment
     * @return int The post id if comment is successfully added or 0 if not successful
     */
    public function add_comment($post_id, $author_id, $content, $extra = array())
    {
        $module_id = (isset($extra['module_id']) ? $extra['module_id'] : self::MODULE_ID);

        $act_data = $this->get_activity_data($post_id, $module_id);

        if (NULL === $act_data)
            return (FALSE);

        $orig_post = $this->get_activity_post($act_data->act_id);
		$owner_id = intval($act_data->act_owner_id);

        // check owner's permissions
        if (FALSE === PeepSo::check_permissions($owner_id, PeepSo::PERM_COMMENT, $author_id)) {
            return (FALSE);
        }

        $note = new PeepSoNotifications();

        // clean content
        $content = htmlspecialchars($content);
        $content = substr(PeepSoSecurity::strip_content($content), 0, PeepSo::get_option('site_status_limit', 4000));

        // create post
        $aPostData = array(
            'post_title'	=> "{$owner_id}-{$author_id}-" . time(),
            'post_content'  => $content,
            'post_excerpt'  => $content,
            'post_status'   => 'publish',
            'post_author'   => $author_id,
            'post_type'		=> PeepSoActivityStream::CPT_COMMENT
        );
        $aPostData = apply_filters('peepso_pre_write_content', array_merge($aPostData, $extra), self::MODULE_ID, __FUNCTION__);

        $id = wp_insert_post($aPostData);

        // add data to Activity Stream data table
        $external_id = (isset($extra['external_id']) ? $extra['external_id'] : $id);
        $aActData = array(
            'act_owner_id' => $owner_id,
            'act_module_id' => self::MODULE_ID, // comments always belong to the activity module
            'act_external_id' => $external_id,
            'act_access' => (isset($extra['access']) ? $extra['access'] : PeepSoUser::get_instance($author_id)->get_profile_accessibility()),
            'act_ip' => PeepSo::get_ip_address(),
            'act_comment_object_id' => $act_data->act_external_id,
            'act_comment_module_id' => $module_id
        );

        global $wpdb;
        $wpdb->insert($wpdb->prefix . self::TABLE_NAME, $aActData);
        $act_id = $wpdb->insert_id;

		if ((int) $act_data->act_comment_object_id === 0) {
			$post_data = $this->get_activity_data($act_data->act_external_id, $act_data->act_module_id);
		} else {
			$post_data = $this->get_activity_data($act_data->act_comment_object_id, $act_data->act_comment_module_id);
		}

		$rank = new PeepSoActivityRanking();
		$rank->add_comment_count($post_data->act_id);

        /**
         * @param int The WP post ID
         * @param int The act_id
         */
        do_action('peepso_activity_after_add_comment', $id, $act_id);

        add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
        $filtered_content = apply_filters('peepso_activity_post_content', $content, $id);
        remove_filter('oembed_result', array(&$this, 'oembed_result'));

        wp_update_post(array('ID' => $id, 'post_content' => $filtered_content));

        $this->handle_embed_data();
        $this->save_peepso_media($id);

        // update the post to reflect
        $wpdb->update($wpdb->prefix . self::TABLE_NAME, array('act_has_replies' => 1), array('act_external_id' => $external_id, 'act_module_id' => $module_id));

		if ($orig_post->post_type == PeepSoActivityStream::CPT_POST) {
			$orig_post = $this->get_activity_post($act_data->act_id);
			$owner_id = intval($orig_post->post_author);
		} else {
			$orig_post = $this->get_activity_post($act_data->act_id);
			$act_data = $this->get_activity_data($orig_post->ID, $module_id);
			$owner_id = intval($orig_post->post_author);
		}

        // send author an email
        if ($author_id !== $owner_id) {
            $user_owner = PeepSoUser::get_instance($owner_id);	// get_user_by('id', $owner_id);
            $user_author = PeepSoUser::get_instance($author_id);	// get_user_by('id', $author_id);
//            $orig_post = $this->get_activity_post($act_data->act_id);

//			$data = array(
//				'email' => $user_owner->get_email(),
//				'ownername' => $user_owner->get_username(),
//				'authorname' => $user_author->get_fullname(),
//				'username' => $user_author->get_username(),
//				'post_title' => $orig_post->post_title
//			);
            $data = array(
                'permalink' => PeepSo::get_page('activity_status'),
                'post_content' => $orig_post->post_content,
            );
            $data = array_merge($data, $user_author->get_template_fields('from'), $user_owner->get_template_fields('user'));
//			PeepSoMailQueue::add($owner_id, $data, __('Someone Commented on Your Post', 'peepso-core'), 'usercomment');

			if ($orig_post->post_type == PeepSoActivityStream::CPT_POST) {

                $data['permalink'] = $data['permalink'] . $orig_post->post_title . '#comment.' . $act_data->act_id . '.' . $id . '.' . $act_id;

                $activity_type = array(
                    'type' => 'user_comment',
                    'text' => __('post', 'peepso-core'));
                $activity_type = apply_filters('peepso_notifications_activity_type', $activity_type, $orig_post->ID, $act_id);

				PeepSoMailQueue::add_notification($owner_id, $data, sprintf(__('Someone Commented on your %s', 'peepso-core'), $activity_type['text']), $activity_type['type'], $activity_type['type'], PeepSoActivity::MODULE_ID);
				$note->add_notification($author_id, $owner_id, __('commented', 'peepso-core'), 'user_comment', self::MODULE_ID, $id);
			} else {
                $act_data_parent = $this->get_activity_data($act_data->act_comment_object_id, $act_data->act_comment_module_id);
                $act_post_parent = get_post($act_data->act_comment_object_id);

                $data['permalink'] = $data['permalink'] . $act_post_parent->post_title . '#comment.' . $act_data_parent->act_id . '.' . $orig_post->ID . '.' . $act_data->act_id . '.' . $external_id;

				PeepSoMailQueue::add_notification($owner_id, $data, __('Someone Replied to your Comment!', 'peepso-core'), 'user_reply_comment', 'stream_reply_comment', PeepSoActivity::MODULE_ID);
				$note->add_notification($author_id, $owner_id, __('replied to', 'peepso-core'), 'stream_reply_comment', self::MODULE_ID, $id);
			}

        }

        $users = $this->get_comment_users($post_id, $module_id);

        $skip = array($author_id, $owner_id);

        while ($users->have_posts()) {

            $users->next_post();

            if(in_array($users->post->post_author, $skip)) {
                continue;
            }

            $skip[] = $users->post->post_author;


            $note->add_notification($author_id, $users->post->post_author, __('commented', 'peepso-core'), 'user_comment', self::MODULE_ID, $id);
        }

        return ($id);
    }

    public static function store_revision($post_id, $content_after, $deleted = FALSE) {
        global $wpdb;

        $content_before = get_post($post_id);
        $content_before = $content_before->post_content;

        $wpdb->insert($wpdb->prefix.'peepso_revisions', array(
            'post_id'        => $post_id,
            'user_id'        => get_current_user_id(),
            'content_before' => $content_before,
            'content_after'  => $content_after,
        ));

        update_post_meta($post_id,'peepso_last_edit', current_time('timestamp', TRUE));
    }

    /*
     * Saves a comment after editing
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function savecomment(PeepSoAjaxResponse $resp)
    {
        $post_id = $this->_input->int('postid');
        $owner_id = $this->get_author_id($post_id);
        $user_id = $this->_input->int('uid');
        $post_content = $this->_input->value('post', '', FALSE); // SQL safe.

        global $post;
        $post = $this->get_comment($post_id);
        $post = $post->next_post();

        // don't allow empty comments
        if (empty($post_content) && !apply_filters('peepso_activity_allow_empty_comment', FALSE)) {
            $resp->success(0);
            $resp->notice(__('Comment is empty', 'peepso-core'));
            return;
        }

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $post_content = substr(PeepSoSecurity::strip_content($post_content), 0, PeepSo::get_option('site_status_limit', 4000));
            add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
            $filtered_content = apply_filters('peepso_activity_post_content', $post_content, $post_id);
            remove_filter('oembed_result', array(&$this, 'oembed_result'));

            $data = apply_filters('peepso_pre_write_content', array(
                'post_content' => $filtered_content,
                'post_excerpt' => $post_content,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', TRUE)
            ), self::MODULE_ID, __FUNCTION__);
            global $wpdb;
            $wpdb->update($wpdb->posts, $data, array('ID' => $post_id));
            $_post = $this->get_activity_data($post_id);

            PeepSoActivity::store_revision($post_id, $filtered_content);

            /**
             * @param int post_id
             * @param object activity
             */
            do_action('peepso_activity_after_save_comment', $post_id, $_post);

            if (empty($_post->act_repost_id)) {
                $this->handle_embed_data();
                $this->save_peepso_media($post_id);
            }

            $this->get_comment($post_id);
            $this->next_comment();

            $html = $this->content(NULL, FALSE);

            ob_start();
            $this->comment_attachment();
            $resp->success(1);
            $resp->set('html', $html);
            $resp->set('attachments', ob_get_clean());

            ob_start();
            $this->comment_actions();
            $resp->set('actions', ob_get_clean());
        }
    }

    /**
     * Allows user to edit a comment.
     * @param PeepSoAjaxResponse $resp The AJAX Response instance.
     */
    public function editcomment(PeepSoAjaxResponse $resp)
	{
        $post_id = $this->_input->int('postid');
        $user_id = $this->_input->int('uid');
        $owner_id = intval($this->get_author_id($post_id));

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);
        $wpq = $this->get_comment($post_id);
        $this->next_comment();
        global $post;

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $data = array('cont' => $post->post_excerpt, 'post_id' => $post_id);
            $html = PeepSoTemplate::exec_template('activity', 'comment-edit', $data, TRUE);

            $resp->set('html', $html);
            $resp->success(1);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso-core'));
        }
    }

    /*
     * add to the peepso_activity_hide table to mark a post as hidden
     * @param int $user_id user doing the hiding
     * @param int $post_id post id to hide
     */
    /*	public function hide_post($user_id, $post_id)
        {
            $aData = array(
                'hide_activity_id' => $post_id,
                'hide_user_id' => $user_id
            );

            global $wpdb;
            $wpdb->insert($wpdb->prefix . self::HIDE_TABLE_NAME, $aData);
        } */


    /*
     * deletes a post and all associated hides, likes, and child comments/posts
     * @param int $post_id the post identifier
     */
    public function delete_post($post_id)
    {
        global $wpdb;

        // find all comments/child posts of the specified post id
        $sql = "SELECT `ID`
				FROM `{$wpdb->posts}`
				WHERE `post_parent`=%d AND `post_type`=%s";
        $all_posts = $wpdb->get_col($wpdb->prepare($sql, $post_id, PeepSoActivityStream::CPT_COMMENT));

        $all_posts = array();

        $post = $this->get_post($post_id);
        if ($post->have_posts()) {
            $post->the_post();
            $this->post_data = get_object_vars($post->post);

            if ($this->has_comments())
                while ($this->next_comment())
                    $all_posts[] = $this->comment_data['ID'];
        }
        // add the specified post_id to the list
        $all_posts[] = intval($post_id);

        // delete all of the posts in the list
        foreach ($all_posts as $postid) {
            $post_query = $this->get_post($postid);

            // let any add-ons know about the delete
            do_action('peepso_delete_content', $postid);

            if (FALSE === $post_query->have_posts()) {
                $sql = "DELETE FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` WHERE `act_external_id`=%d AND `act_module_id`=%d";
                $wpdb->query($wpdb->prepare($sql, $postid, self::MODULE_ID));
            } else {
                $post = $post_query->post;

                $sql = "DELETE FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` WHERE (`act_external_id`=%d OR `act_repost_id`=%d) AND `act_module_id`=%d";
                $wpdb->query($wpdb->prepare($sql, $postid, $post->act_id, self::MODULE_ID));
            }

            $sql = "DELETE FROM `{$wpdb->prefix}peepso_activity_hide` WHERE `hide_activity_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, $postid));

            $sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoLike::TABLE . "` " .
                " WHERE `like_module_id`=%d AND `like_external_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, self::MODULE_ID, $postid));

            $sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoReport::TABLE . "` WHERE `rep_external_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, $postid));

            $sql = "DELETE FROM `{$wpdb->prefix}" . PeepSoNotifications::TABLE . "` WHERE `not_external_id`=%d ";
            $wpdb->query($wpdb->prepare($sql, $postid));

            wp_delete_post($postid, TRUE);
        }

    }


    /*
     * get the the peepso_activities data associated with a given post
     * @param int $act_external_id the act_external id of the record to retrieve
     * @return Object The data record on sucess or FALSE if not found.
     */
    public function get_activity_data($act_external_id, $module_id = self::MODULE_ID)
    {
        global $wpdb;

        $sql = 'SELECT * ' .
            " FROM `{$wpdb->prefix}" . self::TABLE_NAME . '` ' .
            ' WHERE `act_external_id`=%d AND `act_module_id`=%d' .
            ' ORDER BY `act_id` DESC LIMIT 1 ';
        // @todo: remove order by to prevent wrong activities for picso
        $ret = $wpdb->get_row($wpdb->prepare($sql, $act_external_id, $module_id));

        return ($ret);
    }

    /**
     * Return the WP_Post object that is related to an activity object, usually the parent post of a media object.
     * @param  int $act_id Activity's post Id to get
     * @return mixed Returns NULL if $act_id not found or no post; othewise returns WP_Post object
     */
    public function get_activity_post($act_id)
    {
        $act_data = $this->get_activity($act_id);

        // added check to get rid of "Trying to get property of non-object" errors
        if (!is_object($act_data))
            return (NULL);

        $id = apply_filters('peepso_activity_post_id', $act_data->act_external_id, $act_data);

        $this->owner_id = $this->user_id = get_current_user_id();

        # since PeepSo/PeepSo#3599 edit future post capability
        $args = array(
            'p' => $id,
            'post_status' => array('publish', 'pending', 'future'), # since PeepSo/peepso#1935 fix error notice for non admin user
            'post_type' => apply_filters('peepso_activity_post_types', array(PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT)),
            '_bypass_permalink' => TRUE,
            '_bypass_pinned' => TRUE
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $post = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        if ($post->have_posts())
            return ($post->post);
        return (NULL);
    }

    /*
     * Get the the peepso_activities data associated by ID
     * @param int $act_id the act_id of the record to retrieve
     * @return Object The data record on sucess or NULL if not found.
     */
    public function get_activity($act_id)
    {
        // TODO: this appears to be called four times while outputting a single post to the Activity Stream. Let's try to reduce that!
        // Art: I checked this and it only called once per $act_id
        global $wpdb;

        $sql = 'SELECT * ' .
            " FROM `{$wpdb->prefix}" . self::TABLE_NAME . '` ' .
            ' WHERE `act_id`=%d LIMIT 1 ';
        $ret = $wpdb->get_row($wpdb->prepare($sql, $act_id));

        // TODO: this is failing sometimes, returnning NULL. The code that calls this needs to check the result and recover, otherwise it throws "Trying to get property of non-object" errors
        // TODO: note: this is failing when the requesting user is not the same as the posting user on a reposted item.
        return ($ret);
    }

    /*
     * Retrieve a single Activity Stream post by id
     * @param int $id The post id of the Activity Stream post to retrieve
     * @param int $owner_id The user_id of the owner of the post to retrieve
     * @param int $user_id The user_id of the user retrieving the post
     * @param bool $bypass_permalink Whether or not to bypass the permalink queries in filter_post_clauses,
     *                               useful if we want to call this function without worrying about the link.
     * @return WP_Query instance of WP_Query that contains the post
     */
    public function get_post($id, $owner_id = NULL, $user_id = NULL, $bypass_permalink = FALSE, $bypass_hide_activity=FALSE)
    {
        $this->owner_id = $this->user_id = 0;

        if (NULL === $owner_id) {
            // use the current user's id
            $user = get_current_user_id();
        } else
            $user = $owner_id;
        $this->owner_id = $user;

        if (NULL === $user_id) {
            // use the current user'd id
            $user = get_current_user_id();
        } else
            $user = $user_id;
        $this->user_id = $user;

        $args = array(
            'p' => $id,
            'post_type' => PeepSoActivityStream::CPT_POST,
            'post_status' => array('publish', 'future', 'pending'),
//			'posts_per_page' => 1,
            '_bypass_permalink' => $bypass_permalink,
            '_bypass_hide_activity' => $bypass_hide_activity,
            '_bypass_pinned' => TRUE
        );

        if (NULL !== $owner_id) {
			$this->owner_id = $owner_id;
		}

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $this->post_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        return ($this->post_query);
    }


    /*
     * Return the number of ActivityStream posts created by this user
     * @param int $user_id The user id to count posts from
     * @return int The number of posts by that user
     */
    public function get_posts_by_user($user_id)
    {
        global $wpdb;

        $sql = 'SELECT COUNT(*) AS `count` ' .
            " FROM `{$wpdb->posts}` " .
            ' WHERE `post_author`=%d AND `post_type`=%s AND `post_status`=\'publish\' ';
        $res = $wpdb->get_var($wpdb->prepare($sql, $user_id, PeepSoActivityStream::CPT_POST));
        return ($res);
    }


    /*
     * return a WP_Query instance for a user's activity stream posts
     * @param int $offset The number of posts to offset the query by
     * @param int $owner_ud The user_id of of the owner of the posts to be queried. If NULL will get posts from all users.
     * @param int $user_id The user_id value of the Activity Stream items to view
     * @param int $paged The page to display if opting to paginate
     * @return WP_Query instance of queried Activity Stream data
     */
	public function get_posts($offset = NULL, $owner_id = NULL, $user_id = NULL, $paged = NULL, $pinned = FALSE, $limit = 1, $post_id = 0, $search = NULL, $search_mode = 'exact')
	{
		$this->owner_id = $this->user_id = 0;

		if (NULL === $owner_id) {
			// use the current user's id
			$user = get_current_user_id();
		} else {
			$user = $owner_id;
		}

		$this->owner_id = $user;

		if (NULL === $user_id) {
			// use the current user'd id
			$user = get_current_user_id();
		} else {
			$user = $user_id;
		}

		$this->user_id = $user;

		if( NULL == $limit ) {
			$limit = 1;
		}

		$args = array(
			'post_type' => $this->query_type = PeepSoActivityStream::CPT_POST,
			'orderby' => 'post_date_gmt',
			'order' => 'DESC',
			'posts_per_page' => $limit,
			'paged' => (NULL === $paged ? NULL : $paged),   // used to default to zero
			'offset' => (NULL === $offset ? NULL : $offset),// used to default to zero
		);

		if(TRUE == $pinned) {
            $args['pinned'] = TRUE;
		}

		if(0 < $post_id) {
		    $args['p'] = $post_id;
        }

		// Negative page number indicates we want all posts newer than a given ID
		if($paged < 0) {
			$args['posts_per_page'] = -1;
			unset($args['paged']);
			unset($args['offset']);
			$args['post__not_in'] = range(1, abs($paged));
		}

		if(0==$this->_input->int('stream_filter_show_my_posts', 1)) {
            $args['author__not_in'] = array(get_current_user_id());
        }

        // extends the clause so, other plugins like groups / page / event can filters the activity
        $args = apply_filters('peepso_activity_meta_query_args', $args, $this->stream_id);


		// perform the query, with a filter to add the peepso_activity table
		add_filter('posts_join', array(&$this, 'filter_act_join'));
		add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        if (PeepSo::get_option('slow_query_fix', 0) === 1) {
            $args['suppress_filters'] = FALSE;
            add_filter('posts_request', function ($query) use ($args) {
                return $this->filter_posts_request($query, $args);
            });
        }

        if('core_scheduled' == $this->stream_id) {
            $args['post_status'] = 'future';
            if(!PeepSo::is_admin()) {
                $args['author'] = get_current_user_id();
                unset($args['author__not_in']);
            }
        }

        // prevent duplicate activity on wordpress.com
        if (class_exists('Advanced_Post_Cache')) {
            wp_cache_flush();
        }

        $this->post_query = new WP_Query($args);

        if (PeepSo::get_option('slow_query_fix', 0) === 1) {
            remove_filter('posts_request', function(){});
        }
		remove_filter('posts_join', array(&$this, 'filter_act_join'));
		remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

		return ($this->post_query);
    }


    function filter_posts_request($query, $args)
    {
        $post_id = isset($args['p']) ? $args['p'] : 0;
        if ((strpos($query, PeepSoActivityStream::CPT_POST) === FALSE && strpos($query, PeepSoActivityStream::CPT_COMMENT) === FALSE) || $post_id > 0) {
            return $query;
        }
        global $wpdb;

        if (strpos($query, PeepSoActivityStream::CPT_COMMENT) !== FALSE) {
            $post_type = PeepSoActivityStream::CPT_COMMENT;
        } else {
            $post_type = PeepSoActivityStream::CPT_POST;
        }

        $query_parts = explode('LIMIT', $query);
        if ($post_type == PeepSoActivityStream::CPT_COMMENT && isset($query_parts[1])) {
            $query = $query_parts[0] . ' LIMIT ' . $query_parts[1];
        }  else if (strpos($query, 'INNER JOIN') === FALSE) {
            $query = $query_parts[0] . ' LIMIT 1';
        }

        if ($args['posts_per_page'] > 0 && $post_type == PeepSoActivityStream::CPT_POST) {
            $limit = 'LIMIT ' . ($args['paged'] - 1) . ',100';
        } else {
            $limit = '';
        }

        // @todo: improve original functions in plugin files so they can be used without adding more logic
        $where = [];
        $where[] = "$wpdb->posts.post_type = '" . $post_type . "' AND ( $wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'private' OR $wpdb->posts.post_status = 'future')";

        $group_id = $this->_input->int('group_id', 0);
        if ($group_id != 0 && $post_type != PeepSoActivityStream::CPT_COMMENT) {
            $where[] = $wpdb->prepare(" $wpdb->posts.ID IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key=%s AND meta_value=%d) ", array('peepso_group_id', $group_id));
        }

        // regular search
        $search = $this->_input->value('search', NULL, FALSE); // SQL safe. optional, string to search
        $search_mode = $this->_input->value('search_mode', 'exact', array('exact','any')); // optional, whether to use exact phrase or any word

        $search_qry = [];
        if(NULL !== $search && FALSE === strpos($query, PeepSoActivityStream::CPT_COMMENT )) {
            if('any' == $search_mode) {
                $search = explode(' ', $search);
                foreach ($search as $key) {
                    $search_qry[] = " (`{$wpdb->posts}`.`post_content` LIKE '%$key%') ";
                }
            } else {
                $search_qry[] = " (`{$wpdb->posts}`.`post_content` LIKE '%$search%') ";
            }

            $where[] = " (". implode(' OR ', $search_qry) .")";
        }

        // hashtag search
        $hashtag = $this->_input->value('search_hashtag', '', FALSE); // SQL safe.
        if (!empty($hashtag) && (FALSE === strpos($query, PeepSoActivityStream::CPT_COMMENT))) {
            $delimiters = array(
                ' ',
                "\n",
                '.',
                ',',
                '-',
                '\_', // escape to be treated literally
                '(',
                ')',
                '[',
                ']',
                '{',
                '}',
                '!',
                ':',
                ';',
                '#',
                '\%', // escape to be treated literally
                '*',
                '<',
            );

            if(PeepSo::get_option('hashtags_everything',0) === 1) {
                $delimiters = array (
                    " ",
                    "\n",
                    "\t",
                    '#',
                    '<',
                );
            }

            $where_hashtag = " ($wpdb->posts.post_content LIKE '%#$hashtag' "; // hashtag "glued to the end of post
            // hashtag ended by any of the legal delimiters (to avoid counting #hashtag and #hashtagofdoom together
            foreach($delimiters as $d) {
                $where_hashtag .= " OR $wpdb->posts.post_content LIKE '%#{$hashtag}{$d}%' ";
            }

            $where_hashtag .= ")";
            $where[] = $where_hashtag;
        }

        // merge where condition
        $where = implode(' AND ', $where);

        $query_replacement = " FROM (SELECT $wpdb->posts.* FROM $wpdb->posts" .
                             " WHERE  $where" .
                             " ORDER BY $wpdb->posts.post_date DESC $limit) $wpdb->posts";

        $query = trim($query);
        // $query = str_replace('SQL_CALC_FOUND_ROWS', '', $query);
        $query = str_replace('FROM ' . $wpdb->posts, $query_replacement, $query);

        return $query;
    }



    /*
     * Retrieves a single Activity Stream comment by id
     * @param int $post_id The comment id of the Activity Stream comment to retrieve
     * @return WP_Query instance of WP_Query that contains the comment
     */
    public function get_comment($post_id)
    {
        $this->owner_id = $this->user_id = 0;

        $args = array(
            'p' => $post_id,
            'post_type' => PeepSoActivityStream::CPT_COMMENT,
//			'post_parent' => $post_id,
//			'posts_per_page' => 1,
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $this->comment_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        return ($this->comment_query);
    }


    /*
     * return comments for a given post_id
     * @param int $post_id The post id to find comments for
     * @param int $offset The number of posts to offset the query by
     * @param int $module_id The module ID to match the $post_id belongs to
     * @return WP_Query instance of the queried Activity Stream Comment data
     */
    public function get_comments($post_id, $offset = NULL, $paged = 1, $limit = NULL, $module_id = self::MODULE_ID, $modal=false)
    {
//		$this->owner_id = $this->user_id = 0;
        if (NULL === $limit)
            $limit = intval(PeepSo::get_option('site_activity_comments'));

        $args = array(
            'post_type' => $this->query_type = PeepSoActivityStream::CPT_COMMENT,
            'order_by' => 'post_date_gmt',
            'order' => 'ASC',
            'posts_per_page' => $limit,
            'offset' => (NULL === $offset ? 0 : $offset),
            '_comment_object_id' => $post_id,
            '_comment_module_id' => $module_id,
            '_is_modal' => $modal,
        );

		if (0 !== $paged && is_int($paged))
            $args['paged'] = $paged;

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20, 2);
        $this->comment_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20);

        #echo "<pre>";var_dump($this->comment_query);echo "</pre>";
        return ($this->comment_query);
    }


    /*
     * Checks whether the comment query has any remaining posts
     * @return boolean TRUE if there are more comments in the query
     */
    public function has_comments()
    {
        if (NULL === $this->comment_query) {
            $post_id = isset($this->post_data['ID']) ? intval($this->post_data['ID']) : 0;
            $act_module_id = isset($this->post_data['act_module_id']) ? $this->post_data['act_module_id'] : '';
			$this->get_comments($post_id, NULL, 1, NULL, $act_module_id);
		}

		if ($this->comment_query->have_posts()) {
			return (TRUE);
		}

		// Need to reset comment query when no posts are found to ensure that the next run
        // updates $this->comment_query
        $this->comment_query = NULL;
        return (FALSE);
    }


    /*
     * sets up the next post from the result set to be used with the templating system
     * @return Boolean TRUE on success with a valid post ready; FALSE otherwise
     */
    public function next_comment()
    {
        if ($this->comment_query->have_posts()) {
            if ($this->comment_query->current_post >= $this->comment_query->post_count)
                return (FALSE);

            $this->comment_query->the_post();
            $this->comment_data = get_object_vars($this->comment_query->post);
            $this->comment_data['human_friendly'] = strlen($human_friendly = get_post_meta($this->comment_data['ID'], 'peepso_human_friendly', TRUE)) ? $human_friendly : FALSE;

            return (TRUE);
        }
        $this->comment_query = NULL;
        return (FALSE);
    }

    /**
     * Show number of recent comments based on "site_activity_comments" setting
     * @return void
     */
    public function show_recent_comments($modal=FALSE)
    {
        if (empty($this->post_data['act_module_id'])) {
            $this->post_data['act_module_id'] = PeepSoActivity::MODULE_ID;
        }

        $site_activity_comments = intval(PeepSo::get_option('site_activity_comments'));
        global $post;
        $this->post_data = get_object_vars($post);

        add_filter('posts_clauses_request', array(&$this, 'filter_last_rows'), 10, 2);
        $this->get_comments($this->post_data['ID'], NULL, 1, NULL, $this->post_data['act_module_id'], $modal);
        add_filter('posts_clauses_request', array(&$this, 'filter_last_rows'), 10, 2);
        if ($this->comment_query->max_num_pages > 1 || ($this->comment_query->found_posts > 0 && 0 == $site_activity_comments)) {
            PeepSoTemplate::exec_template('activity', 'comment-header', array('PeepSoActivity' => $this));
        }

        // TODO: in get_comments() there is: 'order' => 'ASC' - can we change this to 'DESC' and remove the array_reverse() or the filter above??
        // Reverse the array so we get them in ASC order, skips out on building some SQL
        if (isset($this->comment_query->posts)) {
            $this->comment_query->posts = array_reverse($this->comment_query->posts);
        }

        if ($site_activity_comments > 0) {
            while ($this->next_comment()) {
                $this->show_comment();
            }
        }

        // reset comment_query
        $this->comment_query = NULL; // Reset because this only takes the latest comments, some functions may require all comments

        PeepSoTemplate::exec_template('activity', 'comment-footer');
    }

    /**
     * Get the last rows of a WP_Query filter first
     * @param array $clauses array holding the clauses for the SQL being built
     * @param WP_Query $query Query instance
     * @return array The modified array of clauses
     */
    public function filter_last_rows($clauses, $query)
    {
        global $wpdb;

        $clauses['orderby'] = "`{$wpdb->posts}`.`post_date` DESC";

        return ($clauses);
    }

    /*
     * Obtain remaining comments for display in Activity Stream
     * @param PeepSoAjaxResponse $resp The AJAX response objects
     * @output JSON encoded data of the remainin comments for the post
     */
    public function show_previous_comments(PeepSoAjaxResponse $resp)
    {
		$all_comments = intval($this->_input->int('all', 0));
		if ($all_comments == 1) {
			$comments_batch = -1;
		} else {
			$comments_batch = intval(PeepSo::get_option('activity_comments_batch'));
		}

        $this->first_post_id = $this->_input->int('first', NULL);
        if (!empty($this->first_post_id)) {
            add_filter('posts_where', array(&$this, 'filter_before_id'));
        }

        $activity = $this->get_activity($this->_input->int('act_id'));
		$post = get_post($activity->act_external_id);

        add_filter('peepso_user_profile_id', array(&$this, 'ajax_get_profile_id'));

		if ($this->first_post_id !== NULL) {
			add_filter('posts_clauses_request', array(&$this, 'filter_last_rows'), 10, 2);
		}

		$this->get_comments($activity->act_external_id, NULL, 1, $comments_batch, $activity->act_module_id);

		remove_filter('posts_where', array(&$this, 'filter_before_id'));
		remove_filter('posts_clauses_request', array(&$this, 'filter_last_rows'));

		if ($this->first_post_id !== NULL) {
			// TODO: in get_comments() there is: 'order' => 'ASC' - can we change this to 'DESC' and remove the array_reverse() or the filter above??
			// Reverse the array so we get them in ASC order, skips out on building some SQL
			$this->comment_query->posts = array_reverse($this->comment_query->posts);
		}

        ob_start();

        while ($this->comment_query->have_posts()) {
            $this->next_comment();
            $this->show_comment();
        }

        $comments_html = ob_get_clean();

		$resp->success(1);
        $resp->set('html', $comments_html);

		if ($all_comments == 0) {
			$comment_count = intval($this->comment_query->found_posts) - $comments_batch;
			$comment_count = $comment_count > $comments_batch ? $comments_batch : $comment_count;

			$resp->set('comments_remain', $comment_count);
			$resp->set('comments_remain_caption', $this->get_comment_or_reply_label(__('Show %d more %s', 'peepso-core'), $post->post_type, $comment_count));
		}

    }

    /*
     * Display the 'Show More Posts' link
     */
    public function show_more_posts_link()
    {
    }

    /**
     * Get X number for more comments
     */
    public function get_number_of_more_comments()
    {
        // get max of comments to display
        $limit = intval(PeepSo::get_option('activity_comments_batch'));
        $site_activity_comments = intval(PeepSo::get_option('site_activity_comments'));

        // get found comments post
        $found = intval($this->comment_query->found_posts);
		$remain = intval($found - $site_activity_comments);

		if (($remain < $limit && $remain > 0) || $limit == 0) {
			return $remain;
		}

		return $limit;
    }

    /*
     * Display the 'Show All Comment' link
     */
    public function show_more_comments_link()
    {
        global $post;

        // this is only called when there are comments now; can remove has_comments() check
        //    echo __('Show All Comments', 'peepso-core');

        $site_activity_comments = intval(PeepSo::get_option('site_activity_comments'));

        if ($this->comment_query->max_num_pages > 1 || ($this->comment_query->found_posts > 0 && 0 == $site_activity_comments)) {
            $comment_count = $this->get_number_of_more_comments();
            $label = __('Show %d more %s', 'peepso-core');
            if ($this->comment_query->query['offset'] == 0) {
                $label = __('Show %d %s', 'peepso-core');
            }
            echo $this->get_comment_or_reply_label($label, $post->post_type, $comment_count);
        } else if (PeepSo::get_option('site_activity_comments') <= $this->comment_query->post_count) {
            if ($this->comment_query->post_count > 1) {
                $comment_count = $this->comment_query->post_count;
                echo $this->get_comment_or_reply_label(__('All %d %s displayed.', 'peepso-core'), $post->post_type, $comment_count);
            }
        }
    }

	/*
     * Display comment or reply label
     */
	public function get_comment_or_reply_label($text_to_display, $post_type, $count) {
        $label_single = __('comment', 'peepso-core');
        $label_plural = __('comments', 'peepso-core');

		return sprintf($text_to_display, $count, _n($label_single, $label_plural, $count, 'peepso-core'));
	}


    /*
     * outputs the contents of a single comment
     */
    public function show_comment()
    {
        PeepSoTemplate::exec_template('activity', 'comment', $this->comment_data);
    }

    /*
     * filter the SQL clauses; adding WHERE statements for comment ID
     * @param array $clauses array holding the clauses for the SQL being built
     * @param WP_Query $query Query instance
     * @return array The modified array of clauses
     */
    public function filter_post_clauses_comments($clauses, $query)
    {
        global $wpdb;

        if (!ps_isempty($query->query['_comment_object_id']) && !ps_isempty($query->query['_comment_module_id']))
            $clauses['where'] .= $wpdb->prepare(' AND `act`.`act_comment_object_id` = %d AND `act`.`act_comment_module_id` = %d', $query->query['_comment_object_id'], $query->query['_comment_module_id']);

        return ($clauses);
    }

    /**
     * Sets the JOIN for the activity table.
     * @param  string $join The JOIN clause
     * @return string
     */
    public function filter_act_join($join)
    {
        global $wpdb;

        $join .= " LEFT JOIN `{$wpdb->prefix}" . self::TABLE_NAME . "` `act` ON `act`.`act_external_id`=`{$wpdb->posts}`.`id` ";

        return ($join);
    }

    /*
     * filter the SQL clauses; adding our JOINs and other conditions
     * @param array $clauses array holding the clauses for the SQL being built
     * @param WP_Query $query Query instance
     * @return array The modified array of clauses
     */
    function filter_post_clauses($clauses, $query)
    {
        global $wpdb;

        // Add the default groupby clause anyway, to prevent duplicate records retrieved, one instance of this behavior is showing comments with the friends add-on enabled
        $clauses['groupby'] = "{$wpdb->posts}.`ID`";

        // determine if this is a "permalink" type request #57
        $is_permalink = PeepSoActivity::is_permalink_ajax();
        $bypass_hide_activity = FALSE;

        if (!isset($query->query['_bypass_pinned'])) {
            if (isset($query->query['pinned']) && $query->query['pinned'] === TRUE) {
                $clauses['join'] .= " INNER JOIN (SELECT `post_id`, `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key` = 'peepso_pinned') `mt1` ON " .
                " `mt1`.`post_id`=`$wpdb->posts`.`ID` ";
                $clauses['orderby'] = " `mt1`.`meta_value` DESC ";

            } else {
                $clauses['where'] .= " AND `$wpdb->posts`.`ID` NOT IN (SELECT `post_id` FROM `$wpdb->postmeta` WHERE `meta_key` = 'peepso_pinned') ";
            }
        }


        // add our columns to the query
        if (strpos(',', $clauses['fields']) === FALSE) {
            // SELECT wp_posts.*, act.*, author_id, author_name
            $clauses['fields'] .= ", `act`.*, `$wpdb->posts`.`post_author` AS `author_id`";
        }

        $owner = apply_filters('peepso_user_profile_id', 0);

        if (isset($query->query['_bypass_permalink']) && TRUE === $query->query['_bypass_permalink']) {
            $is_permalink = FALSE;
            $owner = 0;
        }

        if (isset($query->query['_bypass_hide_activity']) && TRUE === $query->query['_bypass_hide_activity']) {
            $bypass_hide_activity = TRUE;
        }

        // add JOIN clauses
        // $clauses['join'] .= " LEFT JOIN `{$wpdb->users}` `auth` ON `auth`.`ID`=`{$wpdb->posts}`.`post_author` ";
        if ($this->user_id > 0) {
            if (!$is_permalink && !$bypass_hide_activity) {// #57 && #1136
                $clauses['join'] .= " LEFT JOIN `{$wpdb->prefix}" . self::HIDE_TABLE_NAME . "` `hide` ON " .
                    " `hide`.`hide_activity_id`=`act`.`act_id` AND `hide`.`hide_user_id`='{$this->user_id}' ";
            }


            // and blocked / ignored users
            $clauses['join'] .= " LEFT JOIN `{$wpdb->prefix}" . self::BLOCK_TABLE_NAME  . "` `blk` ON " .
                " (`blk`.`blk_user_id` = `{$wpdb->posts}`.`post_author` AND `blk`.`blk_blocked_id`={$this->user_id}) " .
                " OR (`blk`.`blk_user_id` = {$this->user_id} AND `blk`.`blk_blocked_id`=`{$wpdb->posts}`.`post_author` ) ";

            // exclude blocked users
            $clauses['where'] .= ' AND `blk_blocked_id` IS NULL ';
        }

        // if it's a permalink request *and* the CPT is the post (not comments!), adjust the WHERE clause
        if ($is_permalink) {
//			$permalink = $wpdb->escape($permalink);
            # commented out in #2479 - it broke loading comments
           # $clauses['where'] = $wpdb->prepare(" AND `{$wpdb->posts}`.`ID`='%s' ", PeepSoActivity::is_permalink_ajax(TRUE));
        } else {
            // adjust the WHERE clause
            if (FALSE === strpos('hide_activity_id', $clauses['where'])) {
                if ($owner > 0)
                    $clauses['where'] = " AND `act_owner_id`='{$owner}' " . $clauses['where'];

                if ($this->user_id > 0) {
                    if(!$bypass_hide_activity) {
                        $clauses['where'] .= ' AND `hide_activity_id` IS NULL ';
                    }
                }

                // add checks for post's access
                if (is_user_logged_in()) {
                    // PRIVATE and owner by current user id  - OR -
                    // MEMBERS and user is logged in - OR -
                    // PUBLIC

                    if (!PeepSo::is_admin()) {
                        $access = ' ((`act_access`=' . PeepSo::ACCESS_PRIVATE . ' AND `act_owner_id`=' . get_current_user_id() . ') OR ' .
                            ' (`act_access`=' . PeepSo::ACCESS_MEMBERS . ') OR (`act_access`<=' . PeepSo::ACCESS_PUBLIC . ') ';

                        // Hooked methods must wrap the string within a paranthesis
                        $access = apply_filters('peepso_activity_post_filter_access', $access);

                        $access .= ') ';
                    }
                } else {
                    // PUBLIC
                    $access = ' (`act_access`<=' . PeepSo::ACCESS_PUBLIC . ' ) ';
                }

                if (!empty($access)) {
                    $clauses['where'] .= " AND {$access}";
                }
            }
        }

        // add ORDER BY clause
        if (!isset($clauses['orderby']))
            $clauses['orderby'] = " `{$wpdb->posts}`.`post_date_gmt` DESC ";

		// indicate if we are loading a sgream item or ie a post for edition
        // @todo we might not need that
		if($this->is_loading_stream) {
		    $clauses['peepso_is_stream'] = 1;
        }

        if (PeepSo::get_option('slow_query_fix', 0) === 0) {
            $search = $this->_input->value('search', NULL, FALSE); // SQL Safe. optional, string to search
            $search_mode = $this->_input->value('search_mode', 'exact', array('exact','any')); // optional, whether to use exact phrase or any word

            $search_qry = [];
            if(NULL !== $search && FALSE === strpos($clauses['where'], PeepSoActivityStream::CPT_COMMENT )) {

                // handling multiple terms
                #todo: find appropriate ways to search any terms
                #@reference: https://codex.wordpress.org/Class_Reference/WP_Query

                if('any' == $search_mode) {
                    $search = explode(' ', $search);
                    foreach ($search as $key) {
                        $search_qry[] = " (`{$wpdb->posts}`.`post_content` LIKE '%$key%') ";
                    }
                } else {
                    $search_qry[] = " (`{$wpdb->posts}`.`post_content` LIKE '%$search%') ";
                }

                $clauses['where'] .= " AND (". implode(' OR ', $search_qry) .")";
            }
        }

        $clauses = apply_filters('peepso_activity_post_clauses', $clauses, $this->user_id);

        // Special case - follower stream
        if (isset($_REQUEST['stream_id']) && 'core_following' === $_REQUEST['stream_id'] && FALSE === strpos($clauses['where'], PeepSoActivityStream::CPT_COMMENT )) {

            $following =  array(
                'core' => "{$wpdb->posts}.`post_author`=".get_current_user_id()
            );

            if(isset($_REQUEST['stream_filter_show_my_posts']) && 1!=$_REQUEST['stream_filter_show_my_posts']) {
                $following = array();
            }

            $following = apply_filters('peepso_activity_post_clauses_follow', $following);

            #echo "<pre>";var_dump($following);echo "</pre>";

            if (count($following)) {
                $clauses['where'] .= " AND ( " . implode(' OR ', $following) . " ) ";
            }
        }

        // Special case - saved post stream
        if(PeepSo::get_option('post_save_enable', 0)) {
            if (isset($_REQUEST['stream_id']) && 'core_saved' === $_REQUEST['stream_id'] && FALSE === strpos($clauses['where'], PeepSoActivityStream::CPT_COMMENT)) {

                $clauses['join'] .= " LEFT JOIN `{$wpdb->prefix}" . self::SAVED_POSTS_TABLE_NAME . "` `saved_posts` ON " .
                    " `saved_posts`.`post_id` = `act`.`act_id`";

                $clauses['where'] .= " AND `saved_posts`.`user_id`={$this->user_id} AND `saved_posts`.`post_id` IS NOT NULL ";
            }
        }

		return $clauses;

    }

    /**
     * Filters for posts newer than $this->last_post_id
     * @param  string $where The WP_Query where clause
     * @return string
     */
    public function filter_since_id($where = '')
    {
        global $wpdb;

        if (NULL !== $this->last_post_id)
            $where .= $wpdb->prepare(" AND `{$wpdb->posts}`.`ID` > %d ", $this->last_post_id);

        return ($where);
    }

    /**
     * Filters for posts older than $this->first_post_id
     * @param  string $where The WP_Query where clause
     * @return string
     */
    public function filter_before_id($where = '')
    {
        global $wpdb;

        if (NULL !== $this->first_post_id)
            $where .= $wpdb->prepare(" AND `{$wpdb->posts}`.`ID` < %d ", $this->first_post_id);

        return ($where);
    }

    /*
     * Get the owner of a specific post
     * @param int $post_id The id of the post
     * @return int The user id of the owner of that post
     */
    private function get_post_owner($post_id)
    {
        global $wpdb;
        $sql = "SELECT `act_owner_id` FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` " .
            " WHERE `act_id`=%d LIMIT 1";
        $owner = intval($wpdb->get_var($wpdb->prepare($sql, $post_id)));
        return ($owner);
    }


    /**
     * Get an activity stream item's owner ID
     * @param  int $post_id The post ID.
     * @return int The WP user ID.
     */
    public function get_owner_id($post_id, $module_id = self::MODULE_ID)
    {
        global $wpdb;

        $sql = "SELECT `act_owner_id` FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` " .
            ' WHERE `act_external_id`=%d AND `act_module_id` = %d';
        $owner = intval($wpdb->get_var($wpdb->prepare($sql, $post_id, $module_id)));

        return ($owner);
    }


    /**
     * Return the original author of a post.
     * @param  int $post_id The post ID to get the author from.
     * @return int User id of the author of the content
     */
    public function get_author_id($post_id)
    {
        global $wpdb;

        $sql = "SELECT `post_author` FROM `{$wpdb->posts}` " .
            ' WHERE `ID`=%d ';
        $owner = intval($wpdb->get_var($wpdb->prepare($sql, $post_id)));
        return ($owner);
    }

    //
    // the following are the AJAX callbacks
    //

    public function like(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id');
        $user_id = $this->_input->int('uid');
        if ($user_id != get_current_user_id()) {
            $resp->error(__('Invalid User id', 'peepso-core'));
            return;
        }

        $activity = $this->get_activity($act_id);
        $module_id = $activity->act_module_id;

        $act_post = $this->get_activity_post($act_id);
        $post_id = $act_post->ID;
        $owner_id = $this->get_author_id($post_id);

        $fSuccess = FALSE;
        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_LIKE, $user_id)) {
            $like = PeepSoLike::get_instance();
            $add_like = (FALSE === $like->user_liked($activity->act_external_id, $module_id, $user_id));

            if ($add_like)
			{
                $res = $like->add_like($activity->act_external_id, $module_id, $user_id, 1, $act_id);
			}
            else
			{
                $res = $like->remove_like($activity->act_external_id, $module_id, $user_id, 1, $act_id);
			}
            if (FALSE !== $res)
                $fSuccess = TRUE;
        }

        $resp->success($fSuccess);
        if ($fSuccess) {
            $count = $like->get_like_count($activity->act_external_id, $module_id);
            $resp->set('count', $count);
            ob_start();
            $this->show_like_count($count, $act_id);
            $resp->set('count_html', ob_get_clean());

            ob_start();
            $like = $this->get_like_status($activity->act_external_id, $module_id);

            $acts = array(
                'like' => array(
                    'href' => '#like',
                    'label' => $like['label'],
                    'class' => 'actaction-like' . ( $like['liked'] ? ' liked' : '' ),
                    'icon' => $like['icon'],
                    'click' => 'return activity.action_like(this, ' . $activity->act_id . ');',
                    'count' => $like['count'],
                ),
            );

            $this->_display_post_actions($post_id, $acts);

            $resp->set('like_html', ob_get_clean());

            // send owner an email
            if ($user_id !== $owner_id && $add_like) {
                $user_owner = PeepSoUser::get_instance($owner_id);
                $user = PeepSoUser::get_instance($user_id);
                $orig_post = get_post($post_id);
                $post_title = $orig_post->post_title;
                if(0 !== intval($act_post->act_comment_object_id)) {
                    $parent_post = get_post($act_post->act_comment_object_id);
                    $parent_post_act = $this->get_activity_data($parent_post->ID, $act_post->act_comment_module_id);

                    // reveal comment on single activity view which ids are defined in the url hash
                    // for example `#comment.00.11.22.33` will be translated as follow:
                    //   00 = post's act_id
                    //   22 = comment's post_id
                    //   11 = comment's act_id
                    //   33 = reply's act_id (optional, if you want to show reply)

                    // check is parent is comment, so it'll be nested comments
                    if($parent_post->post_type === PeepSoActivityStream::CPT_COMMENT) {
                        $parent_post_comment = get_post($parent_post_act->act_comment_object_id);
                        $parent_post_comment_act = $this->get_activity_data($parent_post_comment->ID, $parent_post_act->act_comment_module_id);

                        $post_title = $parent_post_comment->post_title . '#comment.' . $parent_post_comment_act->act_id . '.' . $parent_post->ID . '.' . $parent_post_act->act_id . '.' . $activity->act_external_id;
                    } else {
                        $post_title = $parent_post->post_title . '#comment.' . $parent_post_act->act_id . '.' . $orig_post->ID . '.' . $act_id;
                    }
                }

                $post_type = get_post_type($post_id);
                $post_type_object = get_post_type_object($post_type);

//				PeepSoMailQueue::add($owner_id, $data, __('Someone liked your post', 'peepso-core'), 'likepost');
                $data = array(
                    'permalink' => PeepSo::get_page('activity_status') . $post_title ,
                    'post_content' => $orig_post->post_content,
                );

                $data = array_merge($data, $user->get_template_fields('from'), $user_owner->get_template_fields('user'));

                $activity_type = str_replace('peepso-', '', $post_type_object->name);
                $activity_type = array(
                    'type' => $activity_type,
                    'text' => __($activity_type, 'peepso-core'));
                $activity_type = apply_filters('peepso_notifications_activity_type', $activity_type, $post_id, $act_id);

                PeepSoMailQueue::add_notification($owner_id, $data, sprintf(__('Someone liked your %s', 'peepso-core'), $activity_type['text']), 'like_' . $activity_type['type'], 'like_' . $activity_type['type'], PeepSoActivity::MODULE_ID);

                $note = new PeepSoNotifications();
                $notification_message = sprintf(__('liked your %s', 'peepso-core'), $activity_type['text']);

                $note->add_notification($user_id, $owner_id, $notification_message, 'like_post', $module_id, $post_id);
            }
        } else {
            $resp->error(__('Unable to process', 'peepso-core'));
        }
    }

    /*
     * Allows user to edit a post
     */
    public function editpost(PeepSoAjaxResponse $resp)
	{
        $post_id = $this->_input->int('postid');
        $user_id = $this->_input->int('uid');
        $owner_id = intval($this->get_author_id($post_id));

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);
        $wpq = $this->get_post($post_id, $user_id);
        $this->next_post();

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            global $post;
            $data = array('cont' => $post->post_excerpt, 'post_id' => $post_id, 'act_id' => $post->act_id);
            $data = apply_filters('peepso_activity_post_edit', $data);
			if (isset($data['cont'])) {
				$data['cont'] = html_entity_decode($data['cont']);
			}
            $html = PeepSoTemplate::exec_template('activity', 'post-edit', $data, TRUE);

            $post_time_user_offset = NULL;
            if ($post->post_status == 'future') {
                // config
                $post_date = get_post_time('U', TRUE, $post_id);

                // post time & time adjusted to user's timezone
                $post_timestamp_user_offset = $post_date + 3600 * PeepSoUser::get_gmt_offset(get_current_user_id());
                $post_time_user_offset = gmdate('Y-m-d H:i:s', $post_timestamp_user_offset);
            }

            $resp->set('html', $html);
            $resp->set('content', $data['cont']);
            $resp->set('act_id', $post->act_id);
            $resp->set('data', $data);
            $resp->set('future', $post_time_user_offset);
            $resp->success(1);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso-core'));
        }
    }

    /*
     * Allows user to edit a post
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function edit_description(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id', NULL);
        $user_id = $this->_input->int('uid');
        $type = $this->_input->value('type', NULL, FALSE); // SQL Safe
        $act_external_id = $this->_input->int('object_id', NULL);

        if (NULL === $act_id) {
            $resp->success(FALSE);
            $resp->error(__('Post not found.', 'peepso-core'));
            return;
        }

        $activity = $this->get_activity($act_id);
        $owner_id = intval($activity->act_owner_id);

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $cont = $activity->act_description;
            $objects = apply_filters('peepso_get_object_' . $type, array(), $act_external_id);

            // object caption should be the post content in case of single object
            if ( 1 === count($objects)) {
                $object = array_pop($objects);
                if(!isset($object['using_activity_desc'])) {
                    $cont = $object['post']->post_excerpt;
                }
            }

            $data = array('cont' => $cont, 'act_id' => $act_id, 'type' => $type, 'act_external_id' => $act_external_id);
            $html = PeepSoTemplate::exec_template('activity', 'description-edit', $data, TRUE);

            $resp->set('html', $html);
            $resp->set('act_id', $activity->act_id);
            $resp->success(1);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso-core'));
        }
    }

    /**
     * AJAX callback
     * Saves the description by using a custom query that allows NULL values
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function save_description(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id', NULL);
        $description = $this->_input->value('description', NULL, FALSE); // SQL Safe.
        $user_id = $this->_input->int('uid');
        $type = $this->_input->value('type', NULL, FALSE); // SQL Safe
        $act_external_id = $this->_input->int('object_id', NULL);
        $success = FALSE;

        if (NULL === $act_id) {
            $resp->success(FALSE);
            $resp->error(__('Post not found.', 'peepso-core'));
            return;
        }

        $activity = $this->get_activity($act_id);
        $owner_id = intval($activity->act_owner_id);

        $this->set_user_id($user_id);
        $this->set_owner_id($user_id);

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $objects = apply_filters('peepso_get_object_' . $type, array(), $act_external_id);

            // object caption should be the post content in case of single object
            $using_activity_desc = FALSE;
            if (1 === count($objects)) {
                $object = array_pop($objects);
                if(!isset($object['using_activity_desc'])) {
                    $using_activity_desc = TRUE;
                    $post_id = $object['post']->ID;

                    $description = substr(PeepSoSecurity::strip_content($description), 0, PeepSo::get_option('site_status_limit', 4000));
                    add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
                    $filtered_content = apply_filters('peepso_activity_post_content', $description, $post_id);
                    remove_filter('oembed_result', array(&$this, 'oembed_result'));

                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_content' => $filtered_content,
                        'post_excerpt' => $description
                    ), true);

                    if (is_wp_error($post_id)) {
                        $resp->error(__('Could not update the description.', 'peepso-core'));
                    } else {
                        $success = TRUE;

                        $activity = PeepSoActivity::get_instance();
                        $object['post']->post_content = $filtered_content;
                        $description = $activity->content($object['post'], FALSE);
                        $resp->set('html', $description);
                    }
                }
            }

            if (!$using_activity_desc) {

                global $wpdb;

                // Use custom query to update row, accepts NULL
                $query = "UPDATE `{$wpdb->prefix}" . self::TABLE_NAME . "` SET `act_description`=%s";

                if (empty($description))
                    $query = sprintf($query, 'NULL');
                else
                    $query = $wpdb->prepare($query, $description);

                $query .= $wpdb->prepare(' WHERE `act_id`=%d', $act_id);

                $success = $wpdb->query($query);
                $resp->success($success);

                // PeepSo/peepso#2588 do not execute shortcode on peepso activity or comment
                // $description = do_shortcode($description);
                $description = $description;
            }

            $resp->success($success);
            if (FALSE === $success)
                $resp->error(__('Could not update the description.', 'peepso-core'));
            else
                $resp->set('html', $description);
        } else {
            $resp->success(0);
            $resp->error(__('You do not have permission to edit this post.', 'peepso-core'));
        }
    }

    /*
     * Saves a post after editing
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function savepost(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id');
        $act_post = $this->get_activity_post($act_id);
        $post_id = $act_post->ID;
        $owner_id = $this->get_author_id($post_id);
        $user_id = $this->_input->int('uid');
        $post_content = $this->_input->value('post', '', FALSE); // SQL Safe.
        $future = $this->_input->value('future', NULL, FALSE); // SQL Safe

        // global $post is used by other plugins when checking permissions
        global $post;
        $post = get_post($post_id);
        // don't do anything if contents are empty
        if (empty($post_content) && !apply_filters('peepso_activity_allow_empty_content', FALSE)) {
            $resp->success(FALSE);
            $resp->error(__('Post is empty', 'peepso-core'));
        } else if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            $post_content = substr(PeepSoSecurity::strip_content($post_content), 0, PeepSo::get_option('site_status_limit', 4000));
            add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
            $filtered_content = apply_filters('peepso_activity_post_content', $post_content, $post_id);
            remove_filter('oembed_result', array(&$this, 'oembed_result'));

            $data = apply_filters('peepso_pre_write_content', array(
                'post_content' => $filtered_content,
                'post_excerpt' => $post_content,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', TRUE)
            ), self::MODULE_ID, __FUNCTION__);

            // update date future post
            if($future != NULL && !empty($future)) {

                if(is_numeric($future)) {
                    $future = $future / 1000;
                } else {
                    $future = strtotime($future);
                }

                // Attempt to fix timezone offset
                $post_date = $future - 3600 * PeepSoUser::get_gmt_offset(get_current_user_id());
                $data['post_status'] = 'future';
                $data['post_date'] = date('Y-m-d H:i:s', $post_date);
                $data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $post_date);

                $config_date_format = get_option('date_format'). ' ' . get_option('time_format');
                $timestamp = sprintf(__('Scheduled for %s','peepso-core'),date($config_date_format, $future));

                $resp->set('timestamp', $timestamp);
            }

            // if post as immediately and recent status is future
            if($future == NULL && $act_post->post_status == 'future') {
                $data['post_status'] = 'publish';
                $data['post_date'] = current_time('mysql');
                $data['post_date_gmt'] = current_time('mysql', TRUE);

                $curr_date = current_time('timestamp', TRUE);
                $timestamp = PeepSoTemplate::time_elapsed(mysql2date('U', $data['post_date_gmt'], FALSE), $curr_date);

                $resp->set('timestamp', $timestamp);
            }

            PeepSoActivity::store_revision($post_id, $filtered_content);

            global $wpdb;
            $edit = $wpdb->update($wpdb->posts, $data, array('ID' => $post_id));
            $_post = $this->get_activity_data($post_id);

            if (empty($_post->act_repost_id)) {
                $this->handle_embed_data();

                $this->save_peepso_media($post_id);

                $refresh = PeepSo::get_option('refresh_embeds');
                if($refresh > 0) {
                    set_transient('peepso_cache_media_' . $post_id, 1, $refresh);
                }
            }
            do_action('peepso_activity_after_save_post', $post_id);

            $note = new PeepSoNotifications();
            $users = $this->get_comment_users($post_id, $act_post->act_module_id);

            // notify post owner
            if ($owner_id !== $user_id) {
                $note->add_notification($user_id, $owner_id, __('updated a post', 'peepso-core'), 'wall_post', self::MODULE_ID, $post_id);
            }

            while ($users->have_posts()) {
                $users->next_post();

                if (intval($users->post->post_author) !== $owner_id && intval($users->post->post_author)  !== $user_id) {
                    $note->add_notification($user_id, $users->post->post_author, __('updated a post', 'peepso-core'), 'wall_post', self::MODULE_ID, $post_id);
                }
            }

            $this->get_post($post_id, $owner_id, 0);
            $this->next_post();

            $html = $this->content(NULL, FALSE);

            ob_start();
            $this->post_attachment();
            $resp->success(1);
            $resp->set('html', $html);
            $resp->set('attachments', ob_get_clean());

            ob_start();
            $this->post_actions();
            $resp->set('actions', ob_get_clean());

            $post_extras = apply_filters('peepso_post_extras', array());
            $post_extras = implode(' ', $post_extras);
            $resp->set('extras', $post_extras);
        }
    }


    /*
     * Add a post_id to a user's list of hidden posts
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function hidepost(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id');
        $act = $this->get_activity($act_id);
        $owner_id = $act->act_owner_id;
        $user_id = $this->_input->int('uid');

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_POST, $user_id)) {
            $hide = new PeepSoActivityHide();
            $hide->hide_post_from_user($act_id, $user_id);
            $resp->success(1);
        }
    }


    /*
     * Add a user to user's list of blocked users
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function blockuser(PeepSoAjaxResponse $resp)
    {
        $user_id = $this->_input->int('uid');
        $block_id = $this->_input->int('user_id');

        // don't allow users to block themselves
        if ($user_id === $block_id) {
            $resp->success(0);
            return;
        }

        // don't allow users to block admin
        $user = PeepSoUser::get_instance($block_id);
        $role = $user->get_user_role();
        $wprole = $user->get_role();
        if ($role === 'admin' || in_array('administrator', $wprole)) {
            $resp->success(0);
            $resp->error(__('You can\'t block admin account.', 'peepso-core'));
            return;
        }

        if (PeepSo::check_permissions($user_id, PeepSo::PERM_POST, $user_id)) {
            $block = new PeepSoBlockUsers();
            $block->block_user_from_user($block_id, $user_id);

            $blocked_member_url = PeepSo::get_page('members');
            if(0 == PeepSo::get_option('disable_questionmark_urls', 0)) {
                $blocked_member_url .= '?';
            }
            $blocked_member_url .= 'blocked/';

            $resp->success(1);
            $resp->set('redirect', $blocked_member_url);
        }
    }


    /*
     * Remove a user from user's list of blocked users
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function unblockuser(PeepSoAjaxResponse $resp)
    {
        $user_id = $this->_input->int('uid');
        $block_id = $this->_input->int('user_id');

        if (PeepSo::check_permissions($user_id, PeepSo::PERM_POST, $user_id)) {
            $block = new PeepSoBlockUsers();
            $block->delete_by_id(array($block_id), $user_id);
            $resp->success(1);
        }
    }

    public function set_ban_status(PeepSoAjaxResponse $resp)
    {
        // check admin access
        if( FALSE == PeePso::is_admin()) {
            $resp->success(0);
            $resp->error(__('You do not have permission to do that.', 'peepso-core'));
            return;
        }
        $user_id = $this->_input->int('user_id');
        $ban_status = $this->_input->int('ban_status', 1);

        // don't allow users to un/ban themselves
        if ( get_current_user_id() === $user_id ) {
            $resp->success(0);
            $resp->error(__('You cannot ban yourself', 'peepso-core'));
            return;
        }

        $user = PeepSoUser::get_instance($user_id);

        if( $user_id == 0 || NULL == $user->get_id() ) {
            $resp->success(0);
            $resp->error(__('Invalid user', 'peepso-core'));
            return;
        }

        $resp->set('header', __('Notice', 'peepso-core'));

        if( 0 === $ban_status ) {
            $user->set_user_role('member');
            $resp->set('message', sprintf(__('%s has been unbanned', 'peepso-core'), trim(strip_tags($user->get_fullname()))));
            $resp->success(1);
        } else {
            $ban_type = $this->_input->value('ban_type', 'ban_forever', array('ban-period', 'ban_forever')); // SQL Safe
            if($ban_type == 'ban_period') {
                $ban_period_date = $this->_input->value('ban_period_date', '', FALSE); // SQL Safe
                if(!empty($ban_period_date)) {
                    #$ban_period_date = explode('-', $ban_period_date);
                    #$ban_period_date = implode('/', $ban_period_date);
                    $ban_period_date = strtotime($ban_period_date . ' 23:59:59');

                    /*$gmt_offset = get_option('gmt_offset');
                    if (strpos('-', $gmt_offset) === FALSE) {
                        $gmt_offset = '-' . $gmt_offset;
                    } else {
                        $gmt_offset = '+' . abs($gmt_offset);
                    }

                    $schedule_time = strtotime($gmt_offset . ' hours', $ban_period_date);*/

                    $key = 'peepso_ban_user_date';

                    // save data ban until to usermeta
                    $ban_date = get_user_meta( $user_id, $key, true );
                    if(empty($ban_date)) {
                        add_user_meta( $user_id, $key, $ban_period_date, true);
                    } else {
                        update_user_meta($user_id, $key, $ban_period_date);
                    }

                    $user->set_user_role('ban');
                    $resp->set('message', sprintf(
                        __('%s has been banned until %s', 'peepso-core'),
                        trim(strip_tags($user->get_fullname())),
                        date_i18n(get_option('date_format'), $ban_period_date)
                    ));
                    $resp->success(1);
                }
                else
                {
                    $resp->success(0);
                    $resp->error(__('Missing ban period date', 'peepso-core'));
                    return;
                }

            } else {
                $user->set_user_role('ban');
                $resp->set('message', sprintf(__('%s has been banned', 'peepso-core'), trim(strip_tags($user->get_fullname()))));
                $resp->success(1);
            }
        }
    }


    /*
     * Called from AJAX handler to delete a post/comment
     * @param AjaxResponse $resp The AJAX response
     */
    public function delete(PeepSoAjaxResponse $resp)
    {
        $post_id = $this->_input->int('postid');
        $user_id = $this->_input->int('uid');

        $args = array(
            'p' => $post_id,
            'post_type' => array(PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT),
            'post_status' => array('publish', 'pending', 'future'),
            '_bypass_pinned' => TRUE
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $post_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        // global $post is used by other plugins when checking permissions
        global $post;
        $post = $post_query->post;
        // verify it's the current user AND they have ownership of the item
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, $user_id) ||
            PeepSo::check_permissions(intval($post->act_owner_id), PeepSo::PERM_POST_DELETE, $user_id)) {

			// if is a comment
			$rank = new PeepSoActivityRanking();

			if ($post->post_type === PeepSoActivityStream::CPT_COMMENT)
			{
				$act_data = $this->get_activity_data($post->act_comment_object_id);
                if(isset($act_data->act_id)) {
				    $rank->remove_comment_count($act_data->act_id);
                }
			}

			if (intval($post->act_repost_id) !== 0)
			{
				$orig_post = $this->get_activity_post($post->act_repost_id);
				$rank->remove_share_count($orig_post->act_id);
			}

            $this->delete_post($post_id);
            $resp->set('act_id', $post->act_id);
            $resp->success(TRUE);
        } else {
            $resp->success(FALSE);
            $resp->error(__('You do not have permission to do that.', 'peepso-core'));
        }
    }

    /*
     * Called from AJAX handler to remove link preview on a post/comment
     * @param AjaxResponse $resp The AJAX response
     */
    public function remove_link_preview(PeepSoAjaxResponse $resp)
    {
        $post_id = $this->_input->int('postid');
        $user_id = $this->_input->int('uid');

        $args = array(
            'p' => $post_id,
            'post_type' => array(PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT)
        );

        // perform the query, with a filter to add the peepso_activity table
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        $post_query = new WP_Query($args);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);

        $post = $post_query->post;
        // verify it's the current user AND they have ownership of the item
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, $user_id) ||
            PeepSo::check_permissions(intval($post->act_owner_id), PeepSo::PERM_POST_DELETE, $user_id)) {


            if ($post->post_type === PeepSoActivityStream::CPT_COMMENT)
            {
                #todo remove link_preview for comment
                #remove postmeta key peepso_media?
                delete_post_meta($post_id, 'peepso_media');
            }
            else
            {
                update_post_meta($post_id, '_peepso_display_link_preview', 0);
                delete_post_meta($post_id, 'peepso_media');
            }

            $resp->set('act_id', $post->act_id);
            $resp->success(TRUE);
        } else {
            $resp->success(FALSE);
            $resp->error(__('You do not have permission to do that.', 'peepso-core'));
        }
    }

    /**
     * `peepso_activity_delete` callback
     * Deletes a post or comment based on activity
     * @param  array $activity
     */
    public function delete_post_or_comment($activity)
    {
        if (self::MODULE_ID === intval($activity->act_module_id))
            if (PeepSo::check_permissions($this->get_author_id($activity->act_external_id), PeepSo::PERM_POST_DELETE, get_current_user_id()))
                $this->delete_post($activity->act_external_id);
    }

    /**
     * Calls delete_activity via ajax
     * @param PeepSoAjaxResponse $resp The AJAX Response instance
     */
    public function ajax_delete_activity(PeepSoAjaxResponse $resp)
    {
        // SQL safe, WP sanitizes it
        if (wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'activity-delete' )) {
            $act_id = $this->_input->int('act_id', NULL);
            $activity = $this->get_activity($act_id);

            // Allows other addons to send additional data.
            do_action('peepso_ajax_before_delete_activity', $resp, $activity);

            $delete = $this->delete_activity($act_id);

            do_action('peepso_ajax_after_delete_activity', $resp, $activity, $delete);

            if (is_wp_error($delete)) {
                $resp->success(FALSE);
                $resp->error($delete->get_error_message());
            } else {
                $resp->set('module_id', $activity->act_module_id);
                $resp->success(TRUE);
            }
        } else {
            $resp->success(FALSE);
            $resp->error(__('Could not verify nonce.', 'peepso-core'));
        }
    }

    /**
     * Deletes an activity and calls the `peepso_activity_delete` action
     * @param  int $act_id The activity to delete
     * @return bolean
     */
    public function delete_activity($act_id)
    {
        $activity = $this->get_activity($act_id);

        if (FALSE === PeepSo::check_permissions(intval($activity->act_owner_id), PeepSo::PERM_POST_DELETE, get_current_user_id()))
            return (new WP_Error('no_access', __('You do not have permission to do that.', 'peepso-core')));
        else {
            global $wpdb;

            do_action('peepso_activity_delete', $activity);
            $wpdb->delete($wpdb->prefix . self::TABLE_NAME, array('act_id' => $act_id));

            return (TRUE);
        }
    }

    /*
     * Report a post as inappropriate content
     * @param PeepSoAjaxResponse $resp The AJAX response object
     */
    public function report(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id');
        $act_data = $this->get_activity($act_id);
        $post_id = $act_data->act_external_id;
        $user_id = $this->_input->int('uid');
        $orig_post = get_post($post_id);

        if (PeepSo::check_permissions($this->get_post_owner($act_id), PeepSo::PERM_REPORT, $user_id)) {
            $reasons = str_replace("\r", '', PeepSo::get_option('site_reporting_types', __('Spam', 'peepso-core')));
            $reasons = explode("\n", $reasons);
            $reasons = apply_filters('peepso_activity_report_reasons', $reasons);

            $reason = $this->_input->value('reason', '', $reasons); // SQL Safe
            $reason_desc = $this->_input->value('reason_desc', '', FALSE); // SQL Safe
            $rep = new PeepSoReport();

            if (!$rep->is_reported($post_id, $user_id, $act_data->act_module_id)) {
                if (!empty($reason_desc)) {
                    $reason = $reason . ' - ' . $reason_desc;
                }
                $rep->add_report($post_id, $user_id, $act_data->act_module_id, $reason);

                // notify to admin
                if (PeepSo::get_option('reporting_notify_email', 0)) {

                    // send admin an email
                    $args = array(
                        'role' => 'administrator',
                    );

                    $user_query = new WP_User_Query($args);
                    $users = $user_query->get_results();

                    $adm_email = PeepSo::get_notification_emails();
                    $wpuser = PeepSoUser::get_instance($user_id);

                    $permalink = PeepSo::get_page('activity_status') . $orig_post->post_title;

                    if (intval($act_data->act_comment_object_id) !== 0) {
                        $comment_activity = $this->get_activity_data($act_data->act_comment_object_id, $act_data->act_comment_module_id);
                        if (intval($comment_activity->act_comment_object_id) !== 0) {
                            $post_activity = $this->get_activity_data($comment_activity->act_comment_object_id, $comment_activity->act_comment_module_id);

                            $parent_comment = $this->get_activity_post($comment_activity->act_id);
                            $parent_post = $this->get_activity_post($post_activity->act_id);
                            $parent_id = $parent_comment->act_external_id;

                            $post_link = PeepSo::get_page('activity_status') . $parent_post->post_title . '/';
                            $permalink = $post_link . '?t=' . time() . '#comment.' . $post_activity->act_id . '.' . $parent_comment->ID . '.' . $comment_activity->act_id . '.' . $act_data->act_external_id;
                        } else {
                            $post_activity = $comment_activity;

                            $parent_post = $this->get_activity_post($post_activity->act_id);
                            $permalink = PeepSo::get_page('activity_status') .  $parent_post->post_title . '/#comment.' . $post_activity->act_id . '.' . $post_id . '.' . $act_data->act_external_id;
                        }
                    }

                    $data = array(
                        'userlogin' => $wpuser->get_username(),
                        'userfullname' => trim(strip_tags($wpuser->get_fullname())),
                        'userfirstname' => $wpuser->get_firstname(),
                        'permalink' => admin_url('admin.php?page=peepso-manage&tab=reports'),
                        'activityurl' => $permalink
                    );

                    $list_adm_email = [];

                    if (count($users) > 0) {
                        foreach ($users as $user) {
                            $list_adm_email[] = $user->data->user_email;
                        }
                    }

                    // PeepSo Admin Email
                    $list_adm_email[] = $adm_email;

                    // Additional email
                    $additional_adm_emails = str_replace("\r", '', PeepSo::get_option('reporting_notify_email_list'));
                    $additional_adm_emails = explode("\n", $additional_adm_emails);
                    if (count($additional_adm_emails)) {
                        foreach ($additional_adm_emails as $key => $additional_email) {
                            $list_adm_email[] = $additional_email;
                        }
                    }

                    $list_adm_email = array_unique($list_adm_email);

                    if (count($list_adm_email) > 0) {
                        foreach ($list_adm_email as $adm_email) {
                            if (empty($adm_email)) {
                                continue;
                            }
                            $fullname = '';
                            $adm_user_id = 0;

                            $adm_user = get_user_by('email', $adm_email);
                            if($adm_user != FALSE){
                                $fullname = PeepSoUser::get_instance($adm_user->ID)->get_fullname();
                                $adm_user_id = $adm_user->ID;
                            }

                            $data['useremail'] = $adm_email;
                            $data['currentuserfullname'] = $fullname;
                            PeepSoMailQueue::add_message($adm_user_id, $data, __('{sitename} - New Reported Content', 'peepso-core'), 'reported_content', 'reported_content', 0);
                        }
                    }
                }
            }

            $resp->success(TRUE);
            $resp->notice(__('This item has been reported', 'peepso-core'));
        } else {
            $resp->success(FALSE);
            $resp->error(__('You do not have permission to do that.', 'peepso-core'));
        }
    }

    /*
     * Writes a comment on a post
     * @param PeepSoAjaxResponse $resp The AJAX response object
     */
    public function makecomment(PeepSoAjaxResponse $resp)
    {
        $content = $this->_input->raw('content');

        // don't allow empty comments
        if (empty($content) && !apply_filters('peepso_activity_allow_empty_comment', FALSE)) {
            $resp->success(FALSE);
            $resp->notice(__('Comment is empty', 'peepso-core'));
            return;
        }

        $act_id = $this->_input->int('act_id');
        $activity = $this->get_activity($act_id);

        if (NULL === $activity) {
            $resp->success(FALSE);
            $resp->notice(__('Activity not found', 'peepso-core'));
            return;
        }

        $module_id = $activity->act_module_id;
        $user_id = $this->_input->int('uid');
        $owner_id = $activity->act_owner_id;
        $post_id = $activity->act_external_id;

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_COMMENT, $user_id)) {
            $args = array(
                'content' => $content,
                'user_id' => $user_id,
                'target_user_id' => $owner_id,
//				'type' => $type,
                'written' => 0,
            );

            $extra = array('module_id' => $module_id);

            $id = $this->add_comment($post_id, $user_id, $content, $extra);
            $resp->set('has_max_comments', FALSE);

            if (FALSE !== $id) {
                $args['written'] = 1;
                $args['post_id'] = $id;
            }

            if (isset($args['written']) && 1 === $args['written']) {
                $resp->success(TRUE);

                $this->set_user_id($user_id);
                $this->set_owner_id($owner_id);

                $this->last_post_id = $this->_input->int('last', NULL);
                add_filter('posts_where', array(&$this, 'filter_since_id'));
                $wpq = $this->get_comments($post_id, NULL, 1, NULL, $module_id);
                remove_filter('posts_where' , array(&$this, 'filter_since_id'));

                if ($this->has_comments()) { // ($wpq->have_posts()) {
                    ob_start();
                    while ($this->next_comment())
                        $this->show_comment();

                    $comment_data = ob_get_clean();

                    $resp->set('html', $comment_data);
                }
            } else {
                $resp->success(FALSE);
                if ($max_comments)
                    $resp->error(__('The comment limit for this post has been reached.', 'peepso-core'));
                else
                    $resp->error(__('Error in writing Activity Stream comment.', 'peepso-core'));
            }
        } else {
            $resp->success(FALSE);
            $resp->error(__('You don\'t have permissions for that ', 'peepso-core') . $user_id . '/' . $owner_id);
        }
    }

    //
    // the following are the Activity Stream template tag methods
    //

    /*
     * Output post action options for the post
     */
    public function comment_actions()
    {
        global $post;

        $logged_in = is_user_logged_in();		// we're using this a lot, save function overhead

        $like = $this->get_like_status($post->act_external_id, $post->act_module_id);

        $acts = array();
        if (apply_filters('peepso_permissions_reactions_create', TRUE)) {
            $acts['like'] = array(
                'href' => '#like',
                'label' => $like['label'],
                'class' => 'actaction-like' . ( $like['liked'] ? ' liked' : '' ),
                'icon' => $like['icon'],
                'click' => 'activity.comment_action_like(this, ' . $post->act_id . '); return false;',
                'count' => $like['count'],
            );
        }

        $acts['report'] = array(
            'href' => '#report',
            'label' => __('Report', 'peepso-core'),
            'class' => 'actaction-report',
            'icon' => 'warning-sign',
            'click' => 'activity.comment_action_report(' . $post->act_id . '); return false;',
        );

        // save comment query
        $comment_query = $this->comment_query;

        $comments_batch = intval(PeepSo::get_option('activity_comments_batch'));
        $this->get_comments($post->act_external_id, NULL, 1, $comments_batch, $post->act_module_id);
        $this->comment_query->posts = array_reverse($this->comment_query->posts);

        // if (count($this->comment_query->posts) > 0) {
        //     $reply_label = __('View Replies', 'peepso-core');
        //     $reply_icon = 'eye';
        // } else {
        //     $reply_label = __('Reply', 'peepso-core');
        //     $reply_icon = 'plus';
        // }
        $show_replybutton = apply_filters('peepso_commentsbox_display', apply_filters('peepso_permissions_comment_create', TRUE), $post->ID);

        if($show_replybutton) {
            $reply_label = __('Reply', 'peepso-core');
            $reply_icon = 'plus';

            $author = PeepSoUser::get_instance($post->post_author);
            $author_data = '{ id: ' . $post->post_author . ', name: \'' . esc_js( $author->get_fullname() ) . '\' }';

            $acts['reply'] = array(
                'href' => '#reply',
                'label' => $reply_label,
                'class' => 'actaction-reply',
                'icon' => $reply_icon,
                'click' => 'activity.comment_action_reply(' . $post->act_id . ', ' . $post->ID . ', this, ' . $author_data . '); return false;',
            );
        }

        // restore comment query
        $this->comment_query = $comment_query;

        // if it's the post author or an admin - add edit  action
        #if (get_current_user_id() === intval($post->author_id) || PeepSo::is_admin()) {
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_EDIT, get_current_user_id())) {
            $acts['edit'] = array(
                'href' => '#edit',
                'label' => __('Edit', 'peepso-core'),
                'class' => 'actaction-edit',
                'icon' => 'pencil',
                'click' => 'activity.comment_action_edit(' . $post->ID . ', this); return false;',
            );
        }

        // if it's the post author, owner or an admin - add delete action
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, get_current_user_id())) {
            $acts['delete'] = array(
                'href' => '#delete',
                'label' => '',
                'class' => 'actaction-delete',
                'icon' => 'trash',
                'click' => 'activity.comment_action_delete(' . $post->ID . '); return false;',
            );

            // if it's the post author, owner or an admin - add remove preview
            $allow_embed = PeepSo::get_option('allow_embed', 1) === 1;
            $show_preview = get_post_meta($post->ID, '_peepso_display_link_preview', TRUE);
            $media = get_post_meta($post->ID, 'peepso_media', TRUE);
            if ($allow_embed && ('0' !== $show_preview) && (!empty($media)) && ($post->act_module_id == PeepSoActivity::MODULE_ID))
                $acts['remove_link_preview'] = array(
                    'href' => '#',
                    'label' => __('Remove Link Preview', 'peepso-core'),
                    'class' => 'actaction-removepreview',
                    'icon' => 'eye-off', // 'trash',
                    'click' => 'return activity.comment_action_remove_preview(' . $post->ID . ');',
                );

        }

        if (! PeepSo::get_option('site_reporting_enable', TRUE) || // global config
            (!is_user_logged_in() && !PeepSo::get_option('site_reporting_allowguest', FALSE)) ||
            get_current_user_id() === intval($post->author_id))	// own content
            unset($acts['report']);

        if (! PeepSo::check_permissions($post->post_author, PeepSo::PERM_POST_LIKE, get_current_user_id()))
            unset($acts['like'], $acts['reply']);

        $acts = apply_filters('peepso_activity_comment_actions', $acts);

        // if no actions, exit
        if (0 === count($acts))
            return;

        echo '<nav class="ps-stream-status-action ps-stream-status-action">', PHP_EOL;
        foreach ($acts as $name => $act) {
            if ($name == 'like' && !apply_filters('peepso_permissions_reactions_create', TRUE)) {
                continue;
            }

            echo '<a data-stream-id="', $post->ID, '" ';
            if (isset($act['click']) && $logged_in)
                echo ' onclick="', $act['click'], '" ';
            else
                echo ' onclick="return false;" ';
            if (isset($act['title']) && $logged_in)
                echo ' title="', $act['title'], '" ';
            else if (!$logged_in)
                echo ' title="', __('Please register or log in to perform this action', 'peepso-core'), '" ';
            echo ' href="', ($logged_in ? $act['href'] : '#'), '" ';
            echo ' class="', $act['class'], ' ps-icon-', $act['icon'], '">';
            echo '<span>',$act['label'],'</span>';
            echo '</a>', PHP_EOL;
        }
        echo '</nav>', PHP_EOL;
    }

	/*
     * Show replies and reply textarea on comment
     * @param PeepSoAjaxResponse $resp The response object
     */
	public function replycomment(PeepSoAjaxResponse $resp) {
        $post_id = $this->_input->int('postid');
        $act_id = $this->_input->int('actid');
        $user_id = $this->_input->int('uid');

		$post = $this->get_comment($post_id);
        if ($post->have_posts()) {
            $post->the_post();
            $this->post_data = get_object_vars($post->post);
		}

        $owner_id = intval($this->get_author_id($post_id));

        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_COMMENT, $user_id)) {

            $html = PeepSoTemplate::exec_template('activity', 'comment-reply', array('act_id' => $act_id, 'PeepSoActivity' => $this), TRUE);

            $resp->set('html', $html);
            $resp->success(1);
        }
    }

    public function show_replycomment($user_id, $post_id, $act_id) {

        if(empty($post_id) || empty($act_id)) {
            return;
        }

        $post = $this->get_comment($post_id);
        if ($post->have_posts()) {
            $post->the_post();
            $this->post_data = get_object_vars($post->post);
        }

        $owner_id = intval($this->get_author_id($post_id));

//        if (PeepSo::check_permissions($owner_id, PeepSo::PERM_COMMENT, $user_id)) {
            echo PeepSoTemplate::exec_template('activity', 'comment-reply', array('act_id' => $act_id, 'PeepSoActivity' => $this), TRUE);
//        }

    }


    /**
     * Allow add-on to attach content to a comment
     */
    public function comment_attachment()
    {
        global $post;
        global $PeepSoActivityDidInit;

        if(!isset($PeepSoActivityDidInit)) {
            $PeepSoActivityDidInit = TRUE;
            $this->init();
        }

        // let other add-ons have a chance to attach content to the comment
        do_action('peepso_activity_comment_attachment', $post, $post->ID, $post->act_module_id);
    }


    /**
     * Outputs the_content for the current post
     */
    public function content($post = NULL, $echo = TRUE)
    {
        $psnewline = 'PEEPSONEWLINE';
        if(defined('PEEPSO_NEWLINE')) {
            $psnewline = PEEPSO_NEWLINE;
        }

        if (is_null($post))
            global $post;

        $content = strip_tags($post->post_content, '<a>');

        $content = apply_filters('peepso_activity_content_before', $content);

        $content = str_replace("\n",$psnewline, $content);

        if(strstr($content, '[')) {
            $content = apply_filters('peepso_activity_remove_shortcode', $content);
        }

        // PeepSo/peepso#2588 do not execute shortcode on peepso activity or comment
        // $content = apply_filters(
        //     'peepso_activity_content',
        //     apply_filters('the_content', $content),
        //     $post
        // );
        //
        // -> change to
        $content = apply_filters(
            'peepso_activity_content',
            convert_smilies($content),
            $post
        );

        $target = '';
        if ( 0 == PeepSo::get_option('site_activity_open_links_in_new_tab',1)) {
            $content = str_replace('_blank','',$content);
        }
        $attachments = $this->get_content_attachments($post);

        $attachment_string = '';
        if ( count($attachments) >= 1 ) {
            $content = rtrim($content);

            if ('</p>' === ($markup = substr($content, -4))) {
                $content = substr($content, 0, -4);
            } else {
                $markup = '';
            }

            $attachment_string = ' ' . $this->format_content_attachments($attachments) . $markup;
        }

        // add read-more link on long activity/stream posts and comments

        $doreadmore = array ( PeepSoActivityStream::CPT_POST, PeepSoActivityStream::CPT_COMMENT );

        if(in_array($post->post_type, $doreadmore))
        {
            switch($post->post_type) {
                case PeepSoActivityStream::CPT_POST:
                    $type = "post";
                    break;
                case PeepSoActivityStream::CPT_COMMENT:
                    $type = "comment";
                    break;
                default:
                    $type = "post";
                    break;
            }

            $min_readmore = PeepSo::get_option('site_activity_readmore', 1000);
            $min_readmore_single = PeepSo::get_option('site_activity_readmore_single', 2000);


            if ($min_readmore_single <= $min_readmore) {
                $min_readmore_single = 2 * $min_readmore;
            }

            $length = strlen(wp_strip_all_tags($content));

            if ($length > $min_readmore && $min_readmore > 0) {

                // default in all scenarios unless processing a stream post on a permalink page
                $is_permalink = PeepSoActivity::is_permalink_ajax();


                // add read-more on all but single activity's POST
                if (!$is_permalink) {
                    $link = ' href="#"';

                    // POST link can lead to a single activity if it's really long
                    if('post' == $type) {
                        if ($length > $min_readmore_single && $min_readmore_single > 0) {
                            $link = ' href="' . $this->post_link(FALSE, $post) . '" data-single-act="1"';
                        }
                    }

                    $excerpt = trim(truncateHtml($content, $min_readmore, '', false, true));

                    $readmorelink ='&hellip; <a class="ps-stream-post-more ps-js-content-excerpt-toggle"' . $link . '>' . __('Read more', 'peepso-core') . '</a>';

                    // attach the read more link in the same line
                    if ('</p>' == substr($excerpt, -4)) {
                        $excerpt = substr($excerpt, 0, -4);
                        $excerpt = $excerpt . $readmorelink ."</p>";
                    } else {
                        $excerpt = $excerpt . $readmorelink;
                    }

                    $wrapper = array(
                        '<div>',
                        '<div class="ps-js-content-excerpt">', $excerpt , '</div>',
                        '<div class="ps-js-content-full" style="display:none">', $content, $attachment_string, '</div>',
                        '</div>'
                    );

                    $content = implode('', $wrapper);
                }
            } else {
                $content .= $attachment_string;
            }
        } else {
            $content .= $attachment_string;
        }


        $content = str_replace($psnewline, "<br/>", $content);

        if ($echo)
            echo $content;
        else
            return ($content);
    }

    /**
     * Get list of attachments for particular post
     * @param WP_Post The current post object
     * @return array List of attachment contents
     */
    public function get_content_attachments($post)
    {
        $args = array(
            'post_id' => $post->ID,
            'attachments' => array()
        );

        $args = apply_filters('peepso_activity_content_attachments', $args);

        return $args['attachments'];
    }

    /**
     * Format post attachments
     * @param array List of post attachments
     * @return string Formatted post attachments
     */
    public function format_content_attachments($attachments)
    {
        $content = '';

        if ( count($attachments) >= 1 ) {
            $glue = ' ' . __('and', 'peepso-core') . ' ';
            $content .= '&mdash; ' . implode($glue, $attachments);
        }

        return ($content);
    }

    /**
     * Displays class used as attribute in any HTML element
     * @param string $class Default class name
     * @param boolean $return_raw If set to TRUE it returns string otherwise it prints the modified class name
     */
    public function content_media_class($class)
    {
        $class = apply_filters('peepso_activity_content_media_class', $class);
        echo $class;
    }

    /**
     * Displays the embeded media on the post or comment.
     * - peepso_activity_post_attachment
     * - peepso_activity_comment_attachment
     * @param WP_Post The current post object
     */
    public function content_attach_media($post)
    {
        $allow_embed = PeepSo::get_option('allow_embed', 1) === 1;
        if (!$allow_embed)
            return;

        $show_preview = get_post_meta($post->ID, '_peepso_display_link_preview', TRUE);

        if ('0' === $show_preview)
            return;

        $peepso_media = get_post_meta($post->ID, 'peepso_media');

        if (empty($peepso_media))
            return;

        $peepso_media = apply_filters('peepso_content_media', $peepso_media, $post);
        $new_tabs = PeepSo::get_option('site_activity_open_links_in_new_tab', 1);
        foreach ($peepso_media as $media) {
            if (isset($media['embed']) && $media['embed']) {
                PeepSoTemplate::exec_template('activity', 'content-embed', $media['embed']);
                continue;
            }

            if (!isset($media['url']) || !isset($media['description']))
                continue;

            $url = parse_url($media['url']);
            if('https' != $url['scheme'] && !PeepSo::get_option('allow_non_ssl_embed', 0)) {
                update_post_meta($post->ID, '_peepso_display_link_preview', 0);
                continue;
            }

            $media['target'] = '';
            if ($new_tabs)
                $media['target'] = 'target="_blank"';

            $media['host'] = parse_url($media['url'], PHP_URL_HOST);

            // make iframe full-width
            $media['content'] = isset($media['content']) ? $media['content'] : '';
            if (preg_match('/<iframe/i', $media['content'])) {
                $width_pattern = "/width=\"[0-9]*\"/";
                $media['content'] = preg_replace($width_pattern, "width='100%'", $media['content']);
                $media['content'] = '<div class="ps-media-iframe">' . $media['content'] . '</div>';
            }

            // Improve Facebook embedded content rendering.
            if (preg_match('#class="fb-(post|video)"#i', $media['content'])) {

                // Remove Facebook SDK loader code.
                $media['content'] = preg_replace('#<div[^>]+id="fb-root"[^<]+</div>#i', '', $media['content']);
                $media['content'] = preg_replace('#<script[^<]+</script>#i', '', $media['content']);

                // Remove width setting, follow container width.
                // #1931 Fix Facebook video issue.
                $media['content'] = preg_replace('#\sdata-width=["\']\d+%?["\']#i', '', $media['content']);
            }

            PeepSoTemplate::exec_template('activity', 'content-media', $media);
        }
    }

    /**
     * Hide activity when groupso deactivate
     * @param boolean $hide
     * @return boolean always returns TRUE
     */
    public function activity_hide_before($hide, $post_id, $module_id)
    {
        $group_id = get_post_meta($post_id, 'peepso_group_id', TRUE);

        if(!empty($group_id) && !class_exists('PeepSoGroup') && self::MODULE_ID == $module_id) {
            $hide = TRUE;
        }

        return ($hide);
    }

    /*
     * loads the poststatus template
     */
    public function post_status()
    {
        PeepSoTemplate::exec_template('activity', 'poststatus');

        wp_enqueue_script('peepso-activitystream');
    }


    /*
     * checks whether the query has any remaining posts
     * returns only the first page
     * @return boolean TRUE if there are more posts in the query
     */
    public function has_posts($stream_id = 1, $pinned = FALSE, $limit=1)
    {
		$this->stream_id = $stream_id;

        if (NULL === $this->post_query || $pinned != $this->pinned) {
			$this->pinned = $pinned;
            if (PeepSo::get_option('site_activity_hide_stream_from_guest', 0) && FALSE === is_user_logged_in()) {
				return (0);
			}

            $owner = apply_filters('peepso_user_profile_id', 0);
            $user = get_current_user_id();
            $this->get_posts(NULL, $owner, $user, 1, $pinned, $limit);
        }
        return ($this->post_query->have_posts());
    }


    /*
     * sets up the next post from the result set to be used with the templating system
     * @return Boolean TRUE on success with a valid post ready; FALSE otherwise
     */
    public function next_post()
    {
        if ($this->post_query->have_posts()) {
            if ($this->post_query->current_post >= $this->post_query->post_count)
                return (FALSE);

            $this->post_query->the_post();
            $this->post_data = get_object_vars($this->post_query->post);
            return (TRUE);
        }
        return (FALSE);
    }

    /* display post age
     */
    public function post_age()
    {
        // config
        $config_date_format = get_option('date_format'). ' ' . get_option('time_format');
        $config_absolute_dates = PeepSo::get_option('absolute_dates', 0);


        // GMT post date and current date
        $post_date = get_post_time('U', TRUE);
        $curr_date = current_time('timestamp', TRUE);

        // post time & time adjusted to user's timezone
        $post_timestamp_user_offset = $post_date + 3600 * PeepSoUser::get_gmt_offset(get_current_user_id());
        $post_time_user_offset = date($config_date_format, $post_timestamp_user_offset);

        // scheduled post
        if($post_date > $curr_date) {

            echo sprintf(__('Scheduled for %s','peepso-core'),date($config_date_format, $post_timestamp_user_offset));

        } else {

            if (0 == $config_absolute_dates || (24 == $config_absolute_dates && 24 * 3600 > ($curr_date - $post_timestamp_user_offset))) {
                echo '<span class="ps-js-autotime" data-timestamp="', get_the_time('U'), '" title="', mysql2date($config_date_format, $post_time_user_offset), '">', PeepSoTemplate::time_elapsed($post_date, $curr_date), '</span>';
            } else {
                echo mysql2date($config_date_format, $post_time_user_offset);
            }

        }
    }

    public function post_permalink(){
        echo '<span class="ps-tooltip ps-tooltip--permalink ps-js-permalink" data-tooltip="' .
            esc_attr__( 'Click to copy.', 'peepso-core' ) . '" data-tooltip-initial="' .
            esc_attr__( 'Click to copy.', 'peepso-core' ) . '" data-tooltip-success="' .
            esc_attr__( 'Copied.', 'peepso-core' ) . '"><i class="ps-icon-link"></i></span>';
    }

    public function post_edit_notice() {

        $config_date_format = get_option('date_format'). ' ' . get_option('time_format');

        if(!get_current_user_id()) return;
        if(!PeepSo::get_option('post_edit_notice_show',1)) return;

        global $post;

        if(strlen($edit_date = get_post_meta($post->ID, 'peepso_last_edit', TRUE))){ ?>

            <?php
            $edit_date_user_offset = date($config_date_format, $edit_date + 3600 * PeepSoUser::get_gmt_offset(get_current_user_id()));
            $edit_date_user_offset = mysql2date( $config_date_format, $edit_date_user_offset);
            ?>

            <span class="ps-post__edited ps-stream-edit-notice  ps-tooltip"  data-tooltip="<?php echo esc_attr(sprintf(__('Last edited %s','peepso-core'), $edit_date_user_offset)); ?>">
                <i class="ps-icon-edit"></i>
            </span>
        <?php
        }
    }

    /*
     * Output post action options for the post
     */
    public function post_actions()
    {
        global $post;

        $like = $this->get_like_status($post->act_external_id, $post->act_module_id);
        $logged_in = is_user_logged_in();		// we're using this a lot, save function overhead

        $acts = array();
        if (apply_filters('peepso_permissions_reactions_create', TRUE)) {
            $acts['like'] = array(
                'href' => '#like',
                'label' => $like['label'],
                'class' => 'actaction-like' . ( $like['liked'] ? ' liked' : '' ),
                'icon' => $like['icon'],
                'click' => 'activity.comment_action_like(this, ' . $post->act_id . '); return false;',
                'count' => $like['count'],
            );
        }

        $acts['repost'] = array(
            'href' => '#repost',
            'label' => __('RePost', 'peepso-core'),
            'class' => 'actaction-share',
            'icon' => 'forward',
            'click' => 'return activity.action_repost(' . $post->act_id . ', this);',
        );

        if (get_current_user_id() === intval($post->author_id) || get_current_user_id() === intval($post->act_owner_id) || PeepSo::is_admin()) {
            $allow_embed = PeepSo::get_option('allow_embed', 1) === 1;
            $show_preview = get_post_meta($post->ID, '_peepso_display_link_preview', TRUE);
            $media = get_post_meta($post->ID, 'peepso_media', TRUE);
            if ($allow_embed && ('0' !== $show_preview) && (!empty($media)))
                $acts['remove_link_preview'] = array(
                    'href' => '#',
                    'label' => __('Remove Link Preview', 'peepso-core'),
                    'class' => 'actaction-removepreview',
                    'icon' => 'eye-off',
                    'click' => 'return activity.action_remove_link_preview(' . $post->ID . ', ' . $post->act_id . ');',
                );
        }

        if (PeepSo::get_option('activity_social_sharing_enable', 0)) {
            $acts['share'] = array(
                'href' => '#share',
                'label' => __('Share', 'peepso-core'),
                'class' => 'actaction-share',
                'icon' => 'share-alt',
                'click' => "share.share_url('" . PeepSo::get_page('activity_status') . $post->post_title . "'); return false;",
                'allow_guest' => TRUE
            );
        }

        if ($logged_in && PeepSo::get_option('post_save_enable', 0)) {
            $acts['save'] = array(
                'href' => '#save',
                'label' => __('Save', 'peepso-core'),
                'class' => 'actaction-save ps-js-save-toggle',
                'icon' => 'bookmark-empty',
                'style' => 'float:right'
            );
        }

        if (PeepSo::get_option('post_view_count_show', 0) && isset($this->post_data['act_id'])) {

            $acts['views'] = array(
                'href' => '',
                'label' => PeepSoActivityRanking::get_view_count($this->post_data['act_id']),
                'class' => 'ps-post__view-count',
                'icon' => 'eye'
            );
        }

        $options = apply_filters('peepso_activity_post_actions', array('acts'=>$acts,'post'=>$post));
        $acts = $options['acts'];

        if (! PeepSo::get_option('site_repost_enable', TRUE) ||
            !$logged_in || intval($post->post_author) === get_current_user_id() || (!in_array($post->act_access, array(PeepSo::ACCESS_MEMBERS, PeepSo::ACCESS_PUBLIC)))) {
            unset($acts['repost']);
        }

        if( isset($acts['repost']) && FALSE === apply_filters('peepso_permissions_repost_create', TRUE)) {
            unset($acts['repost']);
        }

        if (! PeepSo::check_permissions($post->post_author, PeepSo::PERM_POST_LIKE, get_current_user_id())) {
            unset($acts['like']);
        }


        if (0 === count($acts)) {
            // if no options, exit
            return;
        }

        echo '<nav class="ps-stream-status-action ps-stream-status-action">', PHP_EOL;
        $this->_display_post_actions($post->act_id, $acts);
        echo '</nav>', PHP_EOL;
    }


    /**
     * Echo the html for activity feed actions.
     * @param  int $post_id The post ID.
     * @param  array $acts  The list of actions with labels and click methods.
     */
    private function _display_post_actions($post_id, $acts)
    {
        $logged_in = is_user_logged_in();		// we're using this a lot, save function overhead
        foreach ($acts as $name => $act) {
            $allow_guest = $logged_in || ( isset($act['allow_guest']) && $act['allow_guest'] );

            $element = 'a';
            if(!strlen($act['href'])) {
                $element = 'span';

            }
            echo '<' . $element .' data-stream-id="', $post_id, '" ';
            if (isset($act['click']) && $allow_guest)
                echo ' onclick="', $act['click'], '" ';
            else
                echo ' onclick="return false;" ';
            if (isset($act['title']) && $allow_guest)
                echo ' title="', $act['title'], '" ';
            else if (!$allow_guest)
                echo ' title="', __('Please register or log in to perform this action', 'peepso-core'), '" ';
            echo ' href="', ($allow_guest ? $act['href'] : '#'), '" ';
            echo ' class="', (isset($act['class']) ? $act['class'] : ''), ' ps-icon-', $act['icon'], '" ';
            echo isset( $act['style'] ) ? ( ' style="' . $act['style'] .'"' ) : '';
            echo '>';
            echo '<span>',$act['label'],'</span>';
            echo '</' . $element .'>', PHP_EOL;
        }
    }


    /*
     * Creates a drop-down menu of post options, user and context appropriate
     */
    public function post_options()
    {
        global $post;
        if (!is_user_logged_in() ||
            ($post->act_owner_id === get_current_user_id() || $post->post_author === get_current_user_id()) )
            return;

        // current user is post owner

        $user_id = get_current_user_id();

        $options = array();

        // only add this if current_user == owner_id, it's an admin or other plugin allows it
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_EDIT, $user_id)) {
            $options['edit'] = array(
                'label' => __('Edit Post', 'peepso-core'),
                'icon' => 'edit',
                'click' => 'activity.option_edit(' . $post->ID . ', ' . $post->act_id . '); return false',
            );

            if(defined('PEEPSO_DEV_RESCHEDULE') && TRUE == PEEPSO_DEV_RESCHEDULE) {
                $options['reschedule'] = array(
                    'label' => __('Change date & time', 'peepso-core'),
                    'icon' => 'clock',
                    'click' => 'return activity.TBD(' . $post->ID . ', ' . $post->act_id . '); return false',
                );
            }
        }

        delete_post_meta($post->ID, 'peepso_disable_comments');

//        if(PeepSo::is_admin()) {
//
//            if (strlen(get_post_meta($post->ID, 'peepso_disable_comments', TRUE))) {
//
//                $options['comments_disable'] = array(
//                    'label' => __('Enable comments', 'peepso-core'),
//                    'icon' => 'play',
//                    'click' => 'activity.option_enable_comments(' . $post->ID . ', this); return false;',
//                );
//
//            } else {
//                $options['comments_disable'] = array(
//                    'label' => __('Disable comments', 'peepso-core'),
//                    'icon' => 'stop',
//                    'click' => 'activity.option_disable_comments(' . $post->ID . ', this); return false;',
//                );
//            }
//        }
        if (PeepSo::check_permissions(intval($post->author_id), PeepSo::PERM_POST_DELETE, $user_id) ||
            PeepSo::check_permissions(intval($post->act_owner_id), PeepSo::PERM_POST_DELETE, $user_id)) {

            $options['delete'] = array(
                'label' => __('Delete Post', 'peepso-core'),
                'icon' => 'trash', // 'remove',
                'click' => 'return activity.action_delete(' . $post->ID . ');',
            );
        }

        if (PeepSo::can_pin(intval($post->ID))) {
            $options['pin'] = array(
                'label' => __('Pin to top', 'peepso-core'),
                'icon' => 'move-up', // 'remove',
                'click' => 'return activity.action_pin(' . $post->ID . ', 1);',
            );

            if ($this->is_pinned($post)) {
                $options['unpin'] = array(
                    'label' => __('Unpin', 'peepso-core'),
                    'icon' => 'move-down', // 'remove',
                    'click' => 'return activity.action_pin(' . $post->ID . ', 0);',
                );
            }
        }

        if($this->is_pinned($post)) {

            $pinned_by = get_post_meta($post->ID, 'peepso_pinned_by', TRUE);
            $pinned = get_post_meta($post->ID, 'peepso_pinned', TRUE);

            if(strlen($pinned_by)) {

                $PeepSoUser = PeepSoUser::get_instance($pinned_by);

                $pinned_date = $pinned + 3600 * PeepSoUser::get_gmt_offset(get_current_user_id());

                $pinned_by_label = sprintf(__('Pinned by %s', 'peepso-core'), $PeepSoUser->get_firstname());
                $pinned_on_label = sprintf(__('Pinned %s at %s', 'peepso-core'), date_i18n(get_option('date_format'), $pinned_date), date_i18n(get_option('time_format'), $pinned_date));

                $options['pinned_by'] = array(
                    'label' => $pinned_by_label,
                    'icon' => 'info-circled',
                    'click' => 'window.open("'.$PeepSoUser->get_profileurl().'", "_blank");return false',
                );

                $options['pinned_on'] = array(
                    'label' => $pinned_on_label,
                    'icon' => 'calendar',
                    'click' => 'return false',
                    'li-class' => 'active',
                );
            }

        }

        if (intval($post->post_author) !== $user_id) {
            // only add this if it's not the current user
            /*$options['hide'] = array(
                'label' => __('Hide this post', 'peepso-core'),
                'icon' => 'eye',
                'click' => 'activity.option_hide(' . $post->act_id . '); return false',
            );

            $options['ignore'] = array(
                'label' => __('Block this user', 'peepso-core'),
                'icon' => 'remove', // 'minus-sign',
                'click' => 'activity.option_block(' . $post->ID . ',' . $post->post_author . '); return false',
            );*/

            if (PeepSo::get_option('site_reporting_enable', TRUE)) {
                $options['report'] = array(
                    'label' => __('Report', 'peepso-core'),
                    'icon' => 'warning-sign',
                    'click' => 'return activity.action_report(' . $post->act_id . ');',
                );
            }
        }

        $options = apply_filters('peepso_post_filters', $options);

        // if no options to display, exit
        if (0 === count($options)) {
			return;
		}

        echo '<div class="ps-dropdown ps-dropdown--stream ps-js-dropdown">', PHP_EOL;
        echo	'<a href="#" class="ps-dropdown__toggle ps-js-dropdown-toggle" data-value="">', PHP_EOL;
        echo		'<span class="dropdown-caret ps-icon-caret-down"></span>', PHP_EOL;
        echo	'</a>', PHP_EOL;

        echo	'<div class="ps-dropdown__menu ps-js-dropdown-menu">', PHP_EOL;
        foreach ($options as $name => $data) {
            echo '<a href="#"';
            if (isset($data['li-class']))
                echo ' class="', $data['li-class'], '"';
            if (isset($data['extra']))
                echo ' ', $data['extra'];
            if (isset($data['click']))
                echo ' onclick="', esc_js($data['click']), '"';
            echo ' data-post-id="', $post->ID, '">';
            echo '<i class="ps-icon-', $data['icon'], '"></i><span>', $data['label'], '</span>', PHP_EOL;
            echo '</a>', PHP_EOL;
        }
        echo	'</div>', PHP_EOL;
        echo '</div>', PHP_EOL;
    }


	public function is_pinned($post)
	{
		$pinned = get_post_meta($post->ID, 'peepso_pinned', TRUE);

		return strlen($pinned);
	}

    /*
     * Display the permalink href= link for the given post
     */
    public function post_link($echo = TRUE, $post = FALSE)
    {
        if (!$post)
            global $post;

        $link = PeepSo::get_page('activity_status') . $post->post_title . '/';
        if ($echo)
            echo $link;
        else
            return ($link);
    }

    /*
     * Display the permalink href= link for the given comment
     */
    public function comment_link($echo = TRUE, $post = FALSE)
    {
        if (!$post)
            global $post;

        $orig_act_data = $this->get_activity_data($post->ID, self::MODULE_ID);

        $parent_act_data = $this->get_activity_data($orig_act_data->act_comment_object_id, $orig_act_data->act_comment_module_id);
        $parent_orig_post = $this->get_activity_post($parent_act_data->act_id);

        $link = PeepSo::get_page('activity_status') . $parent_orig_post->post_title . '/' . '#comment.' . $parent_act_data->act_id . '.' . $post->ID . '.' . $post->ID;

        // comment reply
        if ($parent_orig_post->post_type == PeepSoActivityStream::CPT_COMMENT) {
            $comment_activity = $this->get_activity_data($parent_act_data->act_comment_object_id, $parent_act_data->act_comment_module_id);
            $parent_comment = $this->get_activity_post($comment_activity->act_id);

            $link = PeepSo::get_page('activity_status') . $parent_comment->post_title . '/' . '?t=' . time() . '#comment.' . $comment_activity->act_id . '.' . $parent_orig_post->ID . '.' . $parent_act_data->act_id . '.' . $orig_act_data->act_external_id;
        }

        if ($echo)
            echo $link;
        else
            return ($link);
    }


    /*
     * Output the post's modified time as a GMT based timestamp
     */
    public function post_timestamp()
    {
        the_time('U');
    }


    /*
     * Allows other add-ons to output their post-specific data
     */
    public function post_attachment()
    {
        global $post;
		global $PeepSoActivityDidInit;

		if(!isset($PeepSoActivityDidInit)) {
			$PeepSoActivityDidInit = TRUE;
			$this->init();
		}

        // let other add-ons have a chance to attach content to the post
        do_action('peepso_activity_post_attachment', $post, $post->ID, $post->act_module_id);
    }


    /**
     * Displays the activity privacy icon set on a post.
     */
    public function post_access()
    {
        global $post;

        $privacy = PeepSoPrivacy::get_instance();
        $level = $privacy->get_access_setting($post->act_access);

        echo '<i class="ps-icon-', $level['icon'], '"></i>';
    }

    /*
     * outputs the contents of a single post
     */
    public function show_post()
    {
		$url = PeepSoUrlSegments::get_instance();
        $post_slug = sanitize_key($url->get(2));

        $rank = new PeepSoActivityRanking();
        $rank->add_view_count($this->post_data['act_id']);

		if (!empty($post_slug)) {
            // if post type is comment and permalink, redirect to parent activity
            $parent_post = get_post($this->post_data['act_comment_object_id']);
            if ($this->post_data['post_type'] === PeepSoActivityStream::CPT_COMMENT) {
                PeepSo::redirect(PeepSo::get_page('activity_status') . $parent_post->post_name);
                exit;
            }
		}

        // Refresh media
        $refresh = PeepSo::get_option('refresh_embeds');
        if( $refresh > 0 && !strlen(get_transient($trans = 'peepso_cache_media_'.$this->post_data['ID']))) {
            new PeepSoError('Refreshing post '.$this->post_data['ID']);

            $post_content = $this->post_data['post_content'];
            add_filter('oembed_result', array(&$this, 'oembed_result'), 10, 3);
            $filtered_content = apply_filters('peepso_activity_post_content', $post_content,$this->post_data['ID']);
            remove_filter('oembed_result', array(&$this, 'oembed_result'));

            $this->handle_embed_data();
            $this->save_peepso_media($this->post_data['ID']);

            // Add several seconds of randomness between transients, to avoid slowing down the entire stream at once
            set_transient($trans,1,$refresh+rand(30,60));
        }



        $this->post_data['pinned'] = get_post_meta($this->post_data['ID'],'peepso_pinned', TRUE);
        $this->post_data['pinned_by'] = get_post_meta($this->post_data['ID'],'peepso_pinned_by', TRUE);
        $this->post_data['pinned_date'] = get_post_meta($this->post_data['ID'],'peepso_pinned_date', TRUE);

        $this->post_data['human_friendly'] = strlen($human_friendly = get_post_meta($this->post_data['ID'], 'peepso_human_friendly', TRUE)) ? $human_friendly : FALSE;

        PeepSoTemplate::exec_template('activity', 'post', $this->post_data);

		/*
		 * https://github.com/peepso/peepso/issues/1536
		 * reset wp query to prevent function in another plugin or theme get wrong data
		 */
		wp_reset_query();
    }


    /**
     * Ajax callback, returns json encoded data of a page in an activity feed
     * @param  PeepSoAjaxResponse $resp
     */
    public function show_posts_per_page(PeepSoAjaxResponse $resp)
    {
        $start = microtime(TRUE);
        add_filter('peepso_user_profile_id', array(&$this, 'ajax_get_profile_id'));
        $page = $this->_input->int('page', 1);
        $user = $this->_input->int('uid');
        $owner = $this->_input->int('user_id');
        $post_id = $this->_input->int('post_id', 0);  // optional, single activity view

        $context = $this->_input->value('context','',FALSE); // SQL safe

        // sticky stream ID
        if('stream' == $context && 1==$page && get_current_user_id()) {
            PeepSoUser::set_stream_filters($this->_input);
        }

        $stream_id_list = apply_filters('peepso_stream_id_list', array());
        $this->stream_id = $this->_input->value('stream_id', PeepSo::get_option('stream_id_default'), array_keys($stream_id_list));

        $this->is_loading_stream = TRUE;

        ob_start();

        $found_posts = 0;
        $post_count = 0;

        if(1==$this->_input->int('pinned', 0)) {
            $this->get_posts(NULL, $owner, $user, $page, TRUE, -1, $post_id);
            $found_posts += $this->post_query->found_posts;
            $post_count += $this->post_query->post_count;

            while ($this->next_post()) {
                $this->show_post(); // display post and any comments
            }
        }

        $this->get_posts(NULL, $owner, $user, $page, FALSE, 1, $post_id);

        if(!is_null($this->post_query->post)) {
            $act_id = $this->post_query->post->act_id;
            $resp->set('act_id', $act_id);
        }

        $found_posts += $this->post_query->found_posts;
        $post_count += $this->post_query->post_count;

        do_action('peepso_action_before_posts_per_page', $this);

        while ($this->next_post()) {
            $this->show_post(); // display post and any comments
        }

        $this->show_more_posts_link();
        $resp->set('render_time', microtime(TRUE)-$start);
        $resp->set('max_num_pages', $this->post_query->max_num_pages);
        $resp->set('found_posts', $found_posts);
        $resp->set('post_count', $post_count);
        $resp->set('posts', ob_get_clean());
    }

    public function set_human_friendly(PeepSoAjaxResponse $resp) {

        if($this->_input->int('reset',0)) {
            delete_post_meta_by_key('peepso_human_friendly');
            die('RESET');
        }

        $post_id = $this->_input->int('post_id');
        $user_id = get_current_user_id();
        $human_friendly = str_replace("\n", " ", urldecode($this->_input->value('human_friendly','',FALSE))); // SQL safe, meta

        if(!strlen($human_friendly)) {
            $resp->success(FALSE);
            $resp->error('TEXT_INVALID');
            return;
        }

        if($post = get_post($post_id)) {

            if (!PeepSo::is_admin() && $post->post_author != $user_id) {
                $resp->error('USER_INVALID');
                return;
            }

            update_post_meta($post_id, 'peepso_human_friendly', $human_friendly);
            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        $resp->error('POST_NOT_FOUND');
    }

	public function stream_status(PeepSoAjaxResponse $resp)
	{
		add_filter('peepso_user_profile_id', array(&$this, 'ajax_get_profile_id'));

		$user = $this->_input->int('uid');
		$owner = $this->_input->int('user_id');
		$this->stream_id = $this->_input->int('stream_id', 1);

		$user = $this->_input->int('uid');
		$owner = $this->_input->int('user_id');
		$this->stream_id = $this->_input->int('stream_id', 1);



		$this->get_posts(NULL, $owner, $user, 0, FALSE, 1);

		$all_posts = $this->_input->int('all_posts');
		$all_posts_new = intval($this->post_query->found_posts);

		$new_posts = $all_posts - $all_posts_new;
		$new_posts = ($new_posts<0) ? 0 : $new_posts;


		$resp->set('new_posts', $new_posts);

		$resp->success(TRUE);
	}


    /**
     * Filter callback to retrieve the current profile id, used in filter_post_clauses.
     * @return int The current viewed profile ID
     */
    public function ajax_get_profile_id()
    {
        return ($this->_input->int('user_id'));
    }


    /*
     * return a WP_Query instance for all custom post types by peepso
     * @param string $orderby The column which to sort from
     * @param string $order Sort direction
     * @param int $limit The number of posts to limit the query by
     * @param int $offset The number of posts to offset the query by
     * @param array $search Key-value pair of search options using sub parameters of WP_Query
     * @return WP_Query instance of queried Post data
     */
    public function get_all_activities($orderby = 'post_date_gmt', $order = 'DESC', $limit = NULL, $offset = NULL, $search = array())
    {
        $args = array(
            'post_type' => apply_filters('peepso_activity_post_types',
                array(
                    PeepSoActivityStream::CPT_POST,
                    #PeepSoActivityStream::CPT_COMMENT
                )
            ),
            'order_by' => $orderby,
            'order' => $order,
            'post_status' => 'any',
            'posts_per_page' => (NULL === $limit ? -1 : $limit),
            'offset' => (NULL === $offset ? 0 : $offset)
        );

        foreach ($search as $parameter => $value)
            $args[$parameter] = $value;

        return (new WP_Query($args));
    }


    /*
     * Callback for filtering post content to create anchor links
     * @param string $content The post content to filter
     * @return string Modified post content with URL converted to <a>nchor links
     */
    public function activity_post_content($content)
    {
        $cont = trim($content);		// empty(trim()) throws a PHP error
        if (empty($cont) && (strlen($cont) == 0))
            return ('');

        if (function_exists('mb_convert_encoding'))
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        $xml = new DOMDocument();
        $xml->loadHTML($content);

        $links = $xml->getElementsByTagName('a');

        // loop through each <a> tags and replace them by their text content
        for ($i = $links->length - 1; $i >= 0; $i--) {
            $link_node = $links->item($i);
            $link_text = $link_node->getAttribute('href');
            $new_text_node = $xml->createTextNode($link_text);
            $link_node->parentNode->replaceChild($new_text_node, $link_node);
        }

        // remove <!DOCTYPE
        $xml->removeChild($xml->firstChild);
        $content = $xml->saveXML();

        // remove extra XML content added by parser
        $content = trim(str_replace(array('<?xml version="1.0" standalone="yes"?>',
            '<html>',
            '</html>',
            '<body>',
            '</body>'),'', $content));

        $content = preg_replace('/<p[^>]*>(.*)<\/p[^>]*>/i', '$1', $content);
        if (function_exists('mb_convert_encoding'))
            $content = mb_convert_encoding($content, 'UTF-8', 'HTML-ENTITIES');


        $pattern = '/(?<=^|\s)(' .
            // Common TLDs does not need to have a scheme.
            '((https?:\/\/)?([a-z0-9-]+\.)+((com|net|org|int|edu|gov|mil|biz|info|mobi|co|io|me)(\.[a-z]{2})?)(?![a-z]))|' .
            // Other TLDs need to have a scheme to make sure it is a URL.
            '((https?:\/\/)([a-z0-9-]+\.)+([a-z]{2,24}))' .
        ')(:\d+)?(\/[^*\s]*)?/i';
        $content = preg_replace_callback($pattern, array(&$this, 'make_link'), $content);

        return $content;
    }

    /*
     * Callback for preg_replace_callback to convert URLs to <a>nchor links
     * OR images/video if applicable
     *
     * @param array $matches The matched items
     * @return string the new content, with <a>nchor links added
     */

    /**
     * Assigns the oemebed type
     * @param  array $return
     * @param  object $data The oembed response data
     * @return array
     */
    public function oembed_dataparse($return, $data)
    {
        $this->post_media['oembed_type'] = $data->type;

        return ($return);
    }

    /**
     * Assigns the oemebed args limit data size
     * @param  array $return
     * @param  object $data The oembed response data
     * @return array
     */
    public function oembed_limit_response_size($args)
    {
        if(isset($args['limit_response_size'])) {
            $args['limit_response_size'] = 2048000; // 2 MB
        }

        return ($args);
    }

    public function make_link($matches)
    {
        $url = strip_tags($matches[0]);
        $url = trim($url);

        $url_text = $url;

        // Automatically add HTTPS by default if no scheme is provided.
        if (FALSE === strpos($url, '://')) {
            $url = 'https://' . $url;
        }
        // prevent SSRF, such as file://, ftp://, gopher://
        else if (strpos($url, '://') !== FALSE && strpos($url, 'https://') === FALSE && strpos($url, 'http://') === FALSE) {
            return $url;
        }

        $target = '';
        if (PeepSo::get_option('site_activity_open_links_in_new_tab',1))
            $target = 'target="_blank"';

        if (!empty($this->post_media))
            return ('<a class="ps-media-link" href="' . $url . '" rel="nofollow" ' . $target . '>' . $url_text . '</a>');

        // if embed is disabled
        if (PeepSo::get_option('allow_embed', 1) === 0) {
            return '<a class="ps-media-link" href="' . $url . '" rel="nofollow" ' . $target . '>' . $url_text . '</a>';
        }

        $this->post_media = array(
            'title' => $url,
            'description' => $url,
        );

        // Skip if client send embed data.
        $embed = $this->_input->value('embed', NULL, FALSE);
        if ($embed) {
            return ('<a class="ps-media-link" href="' . $url . '" rel="nofollow" ' . $target . '>' . $url_text . '</a>');
        }

        // Get first image/video

        add_filter('oembed_dataparse', array(&$this, 'oembed_dataparse'), 10, 2);
        add_filter('oembed_remote_get_args', array(&$this, 'oembed_limit_response_size'), 10, 2);
        $embed_code = ps_oembed_get($url, array('width' => 500, 'height' => 300), TRUE);
        remove_filter('oembed_remote_get_args', array(&$this, 'oembed_limit_response_size'));
        remove_filter('oembed_dataparse', array(&$this, 'oembed_dataparse'), 10, 2);

        if (strlen($embed_code['html'])) {

            $this->post_media['content'] = $embed_code['html'];
            $this->post_media['force_oembed'] = $embed_code['force_oembed'];

        } else if ($this->is_image_link($url)) {
            $parts = parse_url($url);

            $this->post_media['content'] = '';
            $this->post_media['title'] = $parts['host'];
            $this->post_media['description'] = $parts['host'];
            $this->post_media['content'] = '<img src="' . $url . '" height="150" width="150" alt="" />';
        }

        $og_tags = $this->_fetch_og_tags($url);

        // has og_tags available
        // overide the content
        // todo: handle for rich content from wordpress that using peepso
        /*if((isset($this->post_media['title']) && !empty($this->post_media['title'])) &&
            (isset($this->post_media['oembed_type']) && $this->post_media['oembed_type'] == 'rich')) {
            unset($this->post_media['oembed_type']);
            unset($this->post_media['content']);
            $this->post_media['force_oembed'] = FALSE;
        }*/

        if (($og_tags) && $og_tags->image) {

            if(!isset($this->post_media['content'])) {
                $this->post_media['content'] = '<img src="' . esc_url($og_tags->image) . '" alt="" />';
            }

            $this->post_media['og_image'] = $og_tags->image;
        }


        // generate hash to avoid duplicate media
        if (FALSE === empty($this->post_media)) {
            $this->post_media['url'] = $url;
            $hash = md5(serialize($this->post_media));
            $this->peepso_media[$hash] = $this->post_media;
        }

        return ('<a class="ps-media-link" href="' . $url . '" rel="nofollow" ' . $target . '>' . $url_text . '</a>');
    }

    /**
     * Checks whether the given URL is a link to an image.
     * Requires CURL library.
     *
     * @param  string $url The image URL to be checked.
     * @return boolean
     */
    private function is_image_link($url)
    {
        if ( ! function_exists('curl_version') ) {
            return FALSE;
        }

        $ch = curl_init( trim($url) );
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $content = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $imageTypes = array('image/gif', 'image/png', 'image/jpeg', 'image/bmp', 'image/webp');

        return in_array( strtolower($contentType), $imageTypes );
    }

    /**
     * Try and get facebook og tags for the given URL, sets post_media values.
     * @param  string $url The URL to get og tags of.
     * @return array The OpenGraph tags available, if any.
     */
    private function _fetch_og_tags($url)
    {
        $og_tags = PeepSoOpenGraph::fetch($url);

        if ($og_tags) {
            if ($og_tags->title)
                $this->post_media['title'] = $og_tags->title;

            if ($og_tags->description)
                $this->post_media['description'] = $og_tags->description;
        }

        return ($og_tags);
    }

    /**
     * Set width and height of the videos
     * @param  string $html The embed HTML
     * @param  string $url  Media URL
     * @param  array $args  An array of arguments passed.
     * @return string The customized HTML.
     */
    public function oembed_result($html, $url, $args)
    {
        // Make video width follows container width.
        $html = preg_replace('#\swidth=["\']\d+%?["\']#i', " width='100%'", $html);

        return $html;
    }

    /**
     * Returns the current retrieved link information
     * @return array
     */
    public function get_media()
    {
        return ($this->post_media);
    }

    /**
     * Set media properties
     * @param  array $return
     * @param  object $data The oembed response data
     */
    public function set_media_properties($return, $data)
    {
        if (isset($data->title))
            $this->post_media['title'] = $data->title;
        return ($return);
    }

    /*
     * Outputs <select> element for list of reporting reasons
     */
    public function report_reasons()
    {
        $reasons = str_replace("\r", '', PeepSo::get_option('site_reporting_types', __('Spam', 'peepso-core')));
        $reasons = explode("\n", $reasons);

        $reasons = apply_filters('peepso_activity_report_reasons', $reasons);

        echo '<select class="ps-select ps-full ps-js-report-type">', PHP_EOL;
            echo '<option value="">', __('- select reason -', 'peepso-core'), '</option>', PHP_EOL;
            foreach ($reasons as $reason) {
                $reason = esc_attr($reason);
                $reason = stripslashes($reason);
                echo '<option value="', $reason, '">', $reason, '</option>';
            }
            echo '<option value="Other" data-need-reason="1">' . __('Other', 'peepso-core') . '</option>';
        echo '</select>', PHP_EOL;

        echo '<div class="ps-js-report-desc" style="position:relative; margin-top:10px">';
        echo '<textarea class="ps-textarea ps-full" maxlength="250" placeholder="' . esc_attr__( 'Report description...', 'peepso-core' ) . '"></textarea>';
        echo '<div class="ps-charcount ps-charcount--input ps-js-counter"></div>';
        echo '</div>';

        echo '<div class="ps-alert ps-alert-danger ps-js-report-error" style="margin:10px 0 0; display:none"></div>';
    }


    /*
     * This template tag gives add-on authors a chance to output dialog box HTML content
     */
    public function dialogs()
    {
        do_action('peepso_activity_dialogs');
    }

    /**
     * Return the number of likes for an activity
     * @param  int  $act_id The act_id to look for
     * @return int  The number of likes
     */
    public function has_likes($act_id)
    {
        $activity = $this->get_activity($act_id);
        $like = PeepSoLike::get_instance();
        return ($like->get_like_count($activity->act_external_id, $activity->act_module_id));
    }

    /**
     * Show the "`n` person likes this" text
     * @param  int $count Set like count display explicitly
     */
    public function show_like_count($count = 0, $act_id = NULL)
    {
        if (NULL === $act_id) {
            global $post;
            $act_id = $post->act_id;
        }

        if ($count > 0)
            echo '<a href="#" onclick="return activity.show_likes(', $act_id, ');">',
            $count, _n(' person likes this', ' people like this.', $count, 'peepso-core'), '</a>';
    }

    /**
     * Return an html list of persons who liked a post.
     */
    public function get_like_names(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id');

        $activity = $this->get_activity($act_id);

        $like = PeepSoLike::get_instance();
        $names = $like->get_like_names($activity->act_external_id, $activity->act_module_id);

        if (count($names) > 0) {
            $users = array();
            $html_names = array();
            $html = '';

            foreach ($names as $name) {
                $user = PeepSoUser::get_instance($name->ID);
                ob_start();
                do_action('peepso_action_render_user_name_before', $user->get_id());
                $before_fullname = ob_get_clean();

                ob_start();
                do_action('peepso_action_render_user_name_after', $user->get_id());
                $after_fullname = ob_get_clean();

                $users[$name->ID] = array(
                    'display_name' => $user->get_fullname(),
                    'profile_url' => $user->get_profileurl()
                );

                $html_names[] = '<a class="ps-comment-user" href="' . $users[$name->ID]['profile_url']
                              . '" data-hover-card="' . $name->ID . '">'
                              . $users[$name->ID]['display_name'] . '</a>';
            }

            $users = apply_filters('peepso_activity_like_names', $users, $act_id);

            $html .= implode(', ', $html_names);
            $html .= _n(' likes this', ' like this', 1, 'peepso-core');

            $resp->success(TRUE);
            $resp->set('users', $users);
            $resp->set('html', $html);
        } else {
            $resp->success(FALSE);
        }
    }

    public function set_comments_status(PeepSoAjaxResponse $resp) {

        if (!PeepSo::is_admin())
        {
            $resp->error(__('You do not have permission to do that', 'peepso-core'));
            $resp->success(FALSE);
            return FALSE;
        }

        $open = $this->_input->int('open', 1);
        $post_id = $this->_input->int('post_id', 0);

        if($open) {
            delete_post_meta($post_id, 'peepso_disable_comments');
        } else {
            update_post_meta($post_id, 'peepso_disable_comments', 1);
        }

        $resp->success(TRUE);
    }

	public function pin(PeepSoAjaxResponse $resp)
	{
		$pin_status = $this->_input->int('pinstatus',0);
		$post_id 	= $this->_input->int('postid',0);

        $this->get_post($post_id);
		$post = get_post($post_id);
		$post->can_be_pinned = 1;
		$post = apply_filters('peepso_post_can_be_pinned', $post);

		// BREAK for unknown action
		if( !in_array($pin_status, array(0,1)) || $post->can_be_pinned == 0) {
			$resp->error(__('Invalid Action', 'peepso-core'));
			$resp->success(FALSE);
			return( FALSE );
		}

		// BREAK If post not found
		if(!$this->has_posts()) {
			$resp->error(__('Invalid Post ID', 'peepso-core'));
			$resp->success(FALSE);
			return( FALSE );
		}

		// BREAK If not admin
        if (!PeepSo::can_pin($post->ID)) {
			$resp->error(__('You do not have permission to do that', 'peepso-core'));
			$resp->success(FALSE);
			return( FALSE );
		}

		$this->next_post();

        delete_post_meta($post_id, 'peepso_pinned');
        delete_post_meta($post_id, 'peepso_pinned_by');

        if( 1 == $pin_status ) {
            add_post_meta($post_id, 'peepso_pinned', current_time('timestamp', TRUE), TRUE);
            add_post_meta($post_id, 'peepso_pinned_by', get_current_user_id(), TRUE);
        }

		$resp->success(TRUE);
		return TRUE;
	}

    public function add_view_count(PeepSoAjaxResponse $resp) {
        $resp->success(PeepSoActivityRanking::add_unique_view_count($this->_input->int('act_id')));
    }


    /**
     * Return an array of Like information. Icon, count and label to display.
     * @param  int $post_id The post ID of which item to get like info from
     * @return array The Like data
     */
    public function get_like_status($post_id, $module_id = PeepSoActivity::MODULE_ID)
    {
        $logged_in = is_user_logged_in();
        $like = PeepSoLike::get_instance();
        $likes = $like->get_like_count($post_id, $module_id);
        $user_liked = $like->user_liked($post_id, $module_id, get_current_user_id());

        $like_icon = 'thumbs-up';
        $like_label = $like_text = __('Like', 'peepso-core');

        if ($likes > 0 && $logged_in) {
            $like_label = '<span title="' . $likes . _n(' person likes this', ' people like this', $likes, 'peepso-core') .
                '">' . $like_text . '</span>';
        }

        return (array(
            'label' => $like_label,
            'icon' => $like_icon,
            'count' => $likes,
            'liked' => $user_liked
        ));
    }

    /**
     * Get a single post's html via ajax.
     * @param  PeepSoAjaxResponse $resp
     */
    public function ajax_show_post(PeepSoAjaxResponse $resp)
    {
        global $post;

        $owner_id = $this->_input->int('user_id');
        $user_id = $this->_input->int('uid');
        $act_id = $this->_input->int('act_id');

        $activity = $this->get_activity($act_id);

        $act_post = apply_filters('peepso_activity_get_post', NULL, $activity, $owner_id, $user_id);
        if (NULL !== $act_post) {
            $post = get_post($act_post->ID, OBJECT);
            setup_postdata($act_post);

            ob_start();
            $this->content();
            $this->post_attachment();

            $resp->set('html', ob_get_clean());
            $resp->success(TRUE);
        } else
            $resp->success(FALSE);
    }

    /**
     * Returns the HTML content to display or NULL if no relevant content is found
     * @param  string $post  The post to return
     * @param  array $activity  The activity data
     * @param  int $owner_id The owner of the activity
     * @param  int $user_id The user requesting access to the activity post
     *
     * @return mixed The HTML post to display | NULL if no relevant post is found
     */
    public function activity_get_post($post, $activity, $owner_id, $user_id)
    {
        if (NULL === $post && is_object($activity)) {
            $this->get_post($activity->act_external_id, $owner_id, $user_id, TRUE);

            if ($this->post_query->have_posts()) {
                $this->post_query->the_post();
                $post = $this->post_query->post;
            }
        }

        return ($post);
    }

    /**
     * Get a single comment's html via ajax.
     * @param  PeepSoAjaxResponse $resp
     */
    public function ajax_show_comment(PeepSoAjaxResponse $resp)
    {
        global $post;

        $act_id = $this->_input->int('act_id');
        $activity = $this->get_activity($act_id);

        $this->get_comment($activity->act_external_id);
        $this->comment_query->the_post();

        ob_start();
        $this->content();
        $this->comment_attachment();
        $resp->set('html', ob_get_clean());
        $resp->set('act_id', $post->act_id);

        $resp->success(TRUE);
    }

    /**
     * Return a WP_Query object containing comments from the given post, grouped by author.
     * @param  int $post_id The post to get the comments from.
     * @return object WP_Query object
     */
    public function get_comment_users($post_id, $module_id)
    {
        $args = array(
            'post_type' => $this->query_type = PeepSoActivityStream::CPT_COMMENT,
            '_comment_object_id' => $post_id,
            '_comment_module_id' => $module_id
        );

        add_filter('posts_groupby', array(&$this, 'groupby_author_id'), 10, 1);
        add_filter('posts_join', array(&$this, 'filter_act_join'));
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10, 2);
        add_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20, 2);
        $query = new WP_Query($args);
        remove_filter('posts_groupby', array(&$this, 'groupby_author_id'), 10);
        remove_filter('posts_join', array(&$this, 'filter_act_join'));
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses'), 10);
        remove_filter('posts_clauses_request', array(&$this, 'filter_post_clauses_comments'), 20);

        return ($query);
    }

    /**
     * posts_groupby callback to group by author.
     * @param  string $groupby The groupby string.
     * @return string
     */
    public function groupby_author_id($groupby)
    {
        global $wpdb;

        return ($wpdb->posts . ".post_author");
    }

    /**
     * Change a post's privacy setting.
     * @param  PeepSoAjaxResponse $resp
     */
    public function change_post_privacy(PeepSoAjaxResponse $resp)
    {
        $act_id = $this->_input->int('act_id');
        $user_id = $this->_input->int('uid');

        $activity = $this->get_activity_post($act_id);
        $owner_id = intval($activity->post_author);

        // SQL safe, WP sanitizes it
        if (wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'peepso-nonce') &&
            PeepSo::check_permissions($owner_id, PeepSo::PERM_POST_EDIT, $user_id)) {
            global $wpdb;

            $aActData = array(
                'act_access' => $this->_input->int('acc'),
            );

            do_action('peepso_activity_change_privacy', $activity, $this->_input->int('acc'));

            $resp->notice(__('Changes saved.', 'peepso-core'));
            $resp->success($wpdb->update($wpdb->prefix . self::TABLE_NAME, $aActData, array('act_id' => $act_id)));
        } else {
            $resp->error(__('You do not have permission to change post privacy settings.', 'peepso-core'));
            $resp->success(FALSE);
        }
    }

    /**
     * Outputs the description of an activity stream item
     */
    public function post_action_title()
    {
        global $post;

        $action = '';
        if ($post->post_author === $post->act_owner_id) {
            $user = PeepSoUser::get_instance($post->post_author);

            $default_action = __('shared a %s', 'peepso-core');
            $default_action_text = __('post', 'peepso-core');

            $default_action = '%s';
            $default_action_text = '';

            if ($post->act_repost_id) {
                $repost = $this->get_activity_post($post->act_repost_id);

                if (NULL !== $repost)
                    $default_action_text = '<a href="' . PeepSo::get_page('activity_status') . $repost->post_title . '/' . '">' . $default_action_text . '</a>';
            }
            ob_start();
            do_action('peepso_action_render_user_name_before', $user->get_id());
            $before_fullname = ob_get_clean();

            ob_start();
            do_action('peepso_action_render_user_name_after', $user->get_id());
            $after_fullname = ob_get_clean();

            $mention = '@peepso_user_'.$user->get_id().'('.$user->get_fullname().')';

            $action = apply_filters('peepso_activity_stream_action', sprintf($default_action, $default_action_text), $post);
            $title = sprintf('%s <span class="ps-post__subtitle ps-stream-action-title">%s</span> ',
                $mention, $action);
        } else {
            $author = PeepSoUser::get_instance($post->post_author);
            $owner = PeepSoUser::get_instance($post->act_owner_id);

            ob_start();
            do_action('peepso_action_render_user_name_before', $author->get_id());
            $before_authorname = ob_get_clean();

            ob_start();
            do_action('peepso_action_render_user_name_after', $author->get_id());
            $after_authorname = ob_get_clean();

            ob_start();
            do_action('peepso_action_render_user_name_before', $owner->get_id());
            $before_ownername = ob_get_clean();

            ob_start();
            do_action('peepso_action_render_user_name_after', $owner->get_id());
            $after_ownername = ob_get_clean();

            $title = sprintf(
                '<a class="ps-stream-user" href="%s" data-hover-card="%d">%s</a><i class="ps-icon-caret-right"></i>
				<a class="ps-stream-user" href="%s" data-hover-card="%d">%s</a>',
                $author->get_profileurl(), $author->get_id(), $before_authorname . $author->get_fullname() . $after_authorname,
                $owner->get_profileurl(), $owner->get_id(), $before_ownername . $owner->get_fullname() . $after_ownername
            );
        }

        echo apply_filters('peepso_activity_stream_title', $title, $post, $action);
    }

    /**
     * Returns the act_id of the original activity being shared.
     *
     * @param  int $post_id
     * @return int
     */
    protected function get_repost_root($post_id)
    {
        if (empty($post_id))
            return (0);

        $sql = $this->_get_repost_root_query($post_id);
        $sql .= ' LIMIT 1';

        global $wpdb;
        $result = $wpdb->get_row($sql);

        return ($result->act_id);
    }

    /**
     * Returns the sql string to generate the hierarchy of repost events of an activity.
     *
     * source: http://explainextended.com/2009/07/20/hierarchical-data-in-mysql-parents-and-children-in-one-query/
     *
     * @param  intval $post_id The post ID
     * @return string The prepared sql query
     */
    private function _get_repost_root_query($post_id)
    {
        global $wpdb;

        $sql = "SELECT `T2`.`act_id`
			FROM (
				SELECT
					@r AS `_id`,
					(SELECT @r := `act_repost_id` FROM `{$wpdb->prefix}" . self::TABLE_NAME . "` WHERE `act_id` = _id) AS `act_repost_id`,
					@l := @l + 1 AS lvl
				FROM
					(SELECT @r := %d, @l := 0) vars,
					`{$wpdb->prefix}" . self::TABLE_NAME . "` `h`
				WHERE @r <> 0) `T1`
			JOIN `{$wpdb->prefix}" . self::TABLE_NAME . "` `T2`
				ON `T1`.`_id` = `T2`.`act_id`
			ORDER BY `T1`.`lvl` DESC";

        return ($wpdb->prepare($sql, $post_id));
    }

    /**
     * Removes the Only Me access if a post does not belong to the current user's stream
     * @param  array $acc
     * @return array
     */
    public function privacy_access_levels($acc)
    {
        global $post;

        if('page' == $post->post_type || 'post' == $post->post_type) {
            return $acc;
        }

        if ($post->post_author !== $post->act_owner_id)
            unset($acc[PeepSo::ACCESS_PRIVATE]);

        return ($acc);
    }

    /**
     * Saves/updates post meta data "peepso_media"
     * @param int $post_id Post content ID
     */
    protected function save_peepso_media($post_id)
    {
        // delete oldies
        delete_post_meta($post_id, 'peepso_media');

        foreach ($this->peepso_media as $post_media) {
            add_post_meta($post_id, 'peepso_media', $post_media);
        }
    }

    /*
     * Output addons for the commentsbox
     * @param int $post_id Post content ID
     */
    public function show_commentsbox_addons($post_id = FALSE)
    {
        // if theres any comments addons at core plugins, need to be added here
        $acts = array();
        $html = array();

        $acts = apply_filters('peepso_commentsbox_interactions', $acts, $post_id);
        $html = apply_filters('peepso_commentsbox_addons', $html, $post_id);

        if (0 === count($acts)) {
            // if no addons, exit
            return;
        }

        echo '<div class="ps-commentbox__addons ps-js-addons">', PHP_EOL;
        echo implode('', $html);
        echo '</div>', PHP_EOL;

        echo '<div class="ps-commentbox-actions">', PHP_EOL;
        $this->_display_commentsbox_addons($acts);
        echo '</div>', PHP_EOL;
    }


    /**
     * Echo the html for activity feed actions.
     * @param  array $acts  The list of actions with labels and click methods.
     */
    private function _display_commentsbox_addons($acts)
    {
        $logged_in = is_user_logged_in();       // we're using this a lot, save function overhead
        foreach ($acts as $name => $act) {
            echo '<a ';
            if (isset($act['click']) && $logged_in)
                echo ' onclick="', $act['click'], '" ';
            else
                echo ' onclick="return false;" ';
            if (isset($act['title']) && $logged_in)
                echo ' title="', $act['title'], '" ';
            else if (!$logged_in)
                echo ' title="', __('Please register or log in to perform this action', 'peepso-core'), '" ';
            echo ' href="', ($logged_in && isset($act['href']) ? $act['href'] : '#'), '" ';
            echo ' class="', (isset($act['class']) ? $act['class'] : ''), ' ps-icon-', $act['icon'], '">';
            if (isset($act['label'])) {
                echo '<span>',$act['label'],'</span>';
            }
            echo '</a>', PHP_EOL;
        }
    }

    /**
     * Handle new embed data.
     * @param int $post_id
     * @param int $act_id
     */
    public function handle_embed_data($post_id = 0, $act_id = 0)
    {
        if (count($this->peepso_media) > 0) {
            foreach ($this->peepso_media as $key => $value) {
                if (isset($value['url'])) {
                    // Get embed data from a URL.
                    $embed = apply_filters('peepso_embed_content', array(), $value['url']);
                    if ($embed['data']) {
                        $embed = array('embed' => $embed['data']);

                        $this->peepso_media[$key] = $embed;
                    }
                }
            }
        } else {
            $type = $this->_input->value('type', '', FALSE);
            if ($type !== 'activity') {
                return;
            }

            $embed_url = $this->_input->raw('embed', NULL, FALSE);
            if (!$embed_url) {
                return;
            }

            // Get embed data from a URL.
            $embed = apply_filters('peepso_embed_content', array(), $embed_url);
            if ($embed['data']) {
                // Use a new key to separate from the previous media data.
                $embed = array('embed' => $embed['data']);

                $hash = md5(serialize($embed));
                $this->peepso_media[$hash] = $embed;
            }
        }
    }

	/**
	 * todo:docblock
	 */
	public function get_activity_by_permalink($permalink) {
		global $wpdb;

		$sql = "SELECT `{$wpdb->posts}`.*, `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . '`.* ' .
			" FROM `{$wpdb->posts}` " .
			" LEFT JOIN `{$wpdb->prefix}" . PeepSoActivity::TABLE_NAME . "` ON `act_external_id`=`{$wpdb->posts}`.`ID` " .
			' WHERE `post_name`=%s AND `post_type`=%s ' .
			' LIMIT 1 ';

		return $wpdb->get_row($wpdb->prepare($sql, $permalink, PeepSoActivityStream::CPT_POST));
	}

	public static function is_permalink_ajax($return_post_id = FALSE) {
        $PeepSoInput = new PeepSoInput();
        $post_id = $PeepSoInput->int('post_id', 0);

        if($return_post_id) {
            return $post_id;
        }

        return ($post_id > 0);
    }

}

// EOF
