<?php

class PeepSoProfile extends PeepSoAjaxCallback
{
    private $user_id = NULL;
    public $user = NULL;
    private $acting_user_id = NULL;

    private $notifications = NULL;
    private $num_notifications = 0;
    private $note_data = array();
    private $message = NULL;

    private $blocked = NULL;
    private $num_blocked = 0;
    private $block_idx = 0;
    private $block_data = array();
    private $_url_segments = null;

    protected function __construct()
    {
        parent::__construct();
        $this->init();

        add_filter('peepso_postbox_access_settings', array(&$this, 'filter_postbox_access_settings'), 10, 1);
        $this->_url_segments = PeepSoUrlSegments::get_instance();
    }

    /**
     * Set which user is to be handled and return the user object
     * @param int $user_id The ID of the user - if "0" it will look for user_id in request or cascade to current user
     * @return PeepSoUser|null
     */
    public function init($user_id = 0)
    {
        // Only fire if PeepSoUser is empty OR the user_id is being overriden OR self::user_id is 0
        if(!$this->user instanceof PeepSoUser || 0!=$user_id || 0==$this->user_id) {
            if (0 == $user_id) {
                $PeepSoInput = new PeepSoInput();
                $user_id = $PeepSoInput->int('user_id', 0);
            }

            if (0 == $user_id) {
                $user_id = get_current_user_id();
            }

            $this->user_id = $user_id;

            $this->user = PeepSoUser::get_instance($this->user_id);

            $this->acting_user_id = get_current_user_id();
        }

        return $this->user;
    }

    /**
     * Check if editing self or being an admin
     * @return bool
     */
    public function can_edit()
    {
        if (get_current_user_id() == $this->user_id || PeepSo::is_admin()) {
            return (TRUE);
        }
        return (FALSE);
    }

    /**
     * Check if editing self or being an admin
     * @return bool
     */
    public function can_delete()
    {
        if (get_current_user_id() == $this->user_id || PeepSo::is_admin()) {
            if(PeepSo::get_option('site_registration_allowdelete', 0)) {
                return (TRUE);
            }
            return (FALSE);
        }
        return (FALSE);
    }

    /**
     * Checks to see whether the current viewed profile is the current user's own profile.
     * @return boolean
     */
    public function is_current_user()
    {
        return ($this->user_id == get_current_user_id());
    }

    /**
     * Called after rendering the profile edit page.
     */
    public function after_edit_form()
    {
        do_action('peepso_profile_after_edit_form');
    }

    /*********** FORM VALIDATION ****************/

    /**
     * Used in conjunction with form validation
     * @param string $value The value of Change Password field
     * @return boolean Either to generate an error message if FALSE otherwise not
     */
    public function check_password_change($value)
    {
        $verify_password = $this->_input->value('verify_password', '', FALSE); // SQL Safe
        if (($value || $verify_password) && $value !== $verify_password)
            return (FALSE);
        return (TRUE);
    }

    /**
     * Used in conjunction with form validation
     * @param string $value The value of User Name field
     * @return boolean Either to generate an error message if FALSE otherwise not
     * @deprecated since 1.8.4
     */
    public function check_username_change($value)
    {
        if ($value !== $this->user->get_username()) {
            $check_existing_username = get_user_by('login', $value);
            if (FALSE === $check_existing_username)
                return (TRUE);
            return (FALSE);
        }
        return (TRUE);
    }

    /**
     * Used in conjunction with form validation
     * @param string $value The value of Email field
     * @return boolean Either to generate an error message if FALSE otherwise not
     */
    public function check_email_change($value)
    {
        $user = get_user_by('email', $value);

        if (is_object($user) && $user->ID != get_current_user_id()) {
            return (FALSE);
        }

        return (TRUE);
    }

    /**
     * Set validation for change_password field
     * @param boolean $valid Whether or not the form passed the initial validation
     * @param object $form Instance of PeepSoForm
     * @return boolean
     */
    public function change_password_validate_after($valid, PeepSoForm $form)
    {
        $field = &$form->fields['change_password'];

        $change_password = $this->_input->raw('change_password', ''); // Accept Raw, since password can be special char 
        $verify_password = $this->_input->raw('verify_password', ''); // Accept Raw, since password can be special char

        $user = get_user_by('id', get_current_user_id());
        $check = wp_check_password($verify_password, $user->data->user_pass, $user->ID);
        
        if (!$check) {
            $field['valid'] = FALSE;
            $field['error_messages'][] = __('Please enter current password in <b>Current Password</b> field.', 'peepso-core');
            return FALSE;
        }

        if ($valid && $change_password) {
            if (strlen($change_password) >= intval(PeepSo::get_option('minimum_password_length', 10))) {
                $valid = TRUE;
            } else {
                $valid = FALSE;
                $field['error_messages'][] = sprintf(__('The password should be at least %d characters.', 'peepso-core'), PeepSo::get_option('minimum_password_length', 10));
            }
        }

        $field['valid'] = $valid;

        return $valid;
    }



    /**************** AJAX - BLOCKED USERS ****************/

    public function block_delete(PeepSoAjaxResponse $resp)
    {
        $this->init();

        if($this->can_edit()) {
            $block_ids = explode(',', $this->_input->value('delete', array(), FALSE)); // SQL Safe
            $aIds = array();

            foreach ($block_ids as $id) {
                $id = intval($id);
                if (!in_array($id, $aIds))
                    $aIds[] = $id;
            }

            if (0 != count($aIds)) {
                $blk = new PeepSoBlockUsers();
                $blk->delete_by_id($aIds);
            }

            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**************** AJAX - AVATARS ****************/

    /**
     * Avatar change #1 - upload
     * @param PeepSoAjaxResponse $resp
     */
    public function upload_avatar(PeepSoAjaxResponse $resp)
    {
        $this->init();

        // SQL safe, WP sanitizes it
        if($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'profile-photo')) {

            $shortcode = PeepSoProfileShortcode::get_instance();
            $shortcode->set_page('profile');
            $shortcode->init();

            if ($shortcode->has_error()) {
                $resp->error($shortcode->get_error_message());
                return;
            }

            $image_url = $this->user->get_tmp_avatar();
            $full_image_url = $this->user->get_tmp_avatar(TRUE);
            $orig_image_url = str_replace('-full', '-orig', $full_image_url);

            // check image dimensions
            $si = new PeepSoSimpleImage();
            $orig_image_path = $this->user->get_image_dir() . 'avatar-orig-tmp.jpg';
            $si->load($orig_image_path);
            $width = $si->getWidth();
            $height = $si->getHeight();
            $avatar_size = PeepSo::get_option('avatar_size','250');

            if (($width < $avatar_size) || ($height < $avatar_size)) {
                $resp->set('width', $width);
                $resp->set('height', $height);
                $resp->error(sprintf(__('Minimum avatar resolution is %d x %d pixels.', 'peepso-core'), $avatar_size, $avatar_size));
                $resp->success(FALSE);
                return;
            }

            $resp->set('image_url', $image_url);
            $resp->set('orig_image_url', $orig_image_url);
            $resp->set('orig_image_path', $orig_image_path);
            $resp->set('html', PeepSoTemplate::exec_template('profile', 'dialog-profile-avatar', NULL, TRUE));
            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**
     * Avatar change #2 (optional) - crop
     * @param PeepSoAjaxResponse $resp
     */
    public function crop(PeepSoAjaxResponse $resp)
    {
        $this->init();

        // SQL safe, WP sanitizes it
        if($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'profile-photo') && $this->can_edit()) {

            $x = $this->_input->int('x');
            $y = $this->_input->int('y');
            $x2 = $this->_input->int('x2');
            $y2 = $this->_input->int('y2');
            $width = $this->_input->int('width');
            $height = $this->_input->int('height');
            $tmp = $this->_input->int('tmp');

            $avatar_hash = '';

            // get avatar hash value if exist
            if (!$tmp) {
                $avatar_hash = get_user_meta($this->user->get_id(), 'peepso_avatar_hash', TRUE);
                if ($avatar_hash) {
                    $avatar_hash = $avatar_hash . '-';
                }
            }

            $src_file = $this->user->get_image_dir() . $avatar_hash  . 'avatar-orig' . ($tmp ? '-tmp' : '') . '.jpg';
            $dest_file = $this->user->get_image_dir() . $avatar_hash  . 'avatar-full' . ($tmp ? '-tmp' : '') . '.jpg';

            $si = new PeepSoSimpleImage();
            $si->load($src_file);

            // Resize image as edited on the screen, we do this because getting x and y coordinates
            // are unreliable when we are cropping from the edit avatar page; the dimensions on the edit
            // avatar page is not the same as the original image dimensions.
            if (isset($width) && isset($height) && $width > 0 && $height > 0) {
                $si->resize($width, $height);
            }

            $new_image = imagecreatetruecolor(PeepSo::get_option('avatar_size', 250), PeepSo::get_option('avatar_size', 250));
            imagecopyresampled($new_image, $si->image,
                0, 0, $x, $y,
                PeepSo::get_option('avatar_size', 250), PeepSo::get_option('avatar_size', 250), $x2 - $x, $y2 - $y);
            imagejpeg($new_image, $dest_file, 75);

            // re-crop thumbnailavatar image
            $dest_file = $this->user->get_image_dir() . $avatar_hash . 'avatar' . ($tmp ? '-tmp' : '') . '.jpg';

            // create a new instance of PeepSoSimpleImage - just in case
            $_si = new PeepSoSimpleImage();
            $_si->load($src_file);
            $new_image = imagecreatetruecolor(PeepSoUser::THUMB_WIDTH, PeepSoUser::THUMB_WIDTH);
            imagecopyresampled($new_image, $si->image, // Resize from cropeed image "$si"
                0, 0, $x, $y,
                PeepSoUser::THUMB_WIDTH, PeepSoUser::THUMB_WIDTH, $x2 - $x, $y2 - $y);
            imagejpeg($new_image, $dest_file, 75);

            $image_url = $tmp ? $this->user->get_tmp_avatar() : $this->user->get_avatar();
            $resp->set('image_url', $image_url);
            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;

    }

    /**
     * Avatar change #3 - finalize
     * @param PeepSoAjaxResponse $resp
     */
    public function confirm_avatar(PeepSoAjaxResponse $resp)
    {
        $this->init();

        // SQL safe, WP sanitizes it
        if($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'profile-photo')) {

            delete_user_meta($this->_input->int('user_id'), 'peepso_use_gravatar');

            if ($this->_input->value('use_gravatar', 0, FALSE) == 1 && PeepSo::get_option('avatars_gravatar_enable') == 1)
            {
                add_user_meta($this->_input->int('user_id'), 'peepso_use_gravatar', 1);
            }

            $this->user->finalize_move_avatar_file();

            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**
     * Set user account to use Gravatar instead of uploaded file
     * @param PeepSoAjaxResponse $resp
     */
    public function use_gravatar(PeepSoAjaxResponse $resp)
    {
        $this->init();

        if($this->can_edit()) {
            $file = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->user->get_email()))) . '?s=160&r=' . strtolower(get_option('avatar_rating'));

            $resp->set('image_url', $file);
            $resp->set('html', PeepSoTemplate::exec_template('profile', 'dialog-profile-avatar', NULL, TRUE));

            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**
     * Avatar delete
     * @param PeepSoAjaxResponse $resp
     */
    public function remove_avatar(PeepSoAjaxResponse $resp)
    {
        $this->init($this->_input->int('user_id'));

        // SQL safe, WP sanitizes it
        if($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'profile-photo')) {
            $this->user->delete_avatar();
            $resp->set('image_url', $this->user->get_avatar());
            $resp->success(TRUE);
        } else {
            $resp->success(FALSE);
        }
    }

    /**************** AJAX - COVER ****************/

    /**
     * Cover change #1 - upload
     * @param PeepSoAjaxResponse $resp
     */
    public function upload_cover(PeepSoAjaxResponse $resp)
    {
        $this->init();

        // SQL safe, WP sanitizes it
        if ($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'profile-photo')) {
            $shortcode = PeepSoProfileShortcode::get_instance();
            $shortcode->set_page('profile');
            $shortcode->init();

            if ($shortcode->has_error()) {
                $resp->error($shortcode->get_error_message());
                $resp->success(FALSE);
                return;
            }

            $resp->set('image_url', $this->user->get_cover());
            $resp->set('html', PeepSoTemplate::exec_template('profile', 'dialog-profile-cover', NULL, TRUE));
            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**
     * Cover change #2 - reposition
     * @param PeepSoAjaxResponse $resp
     */
    public function reposition_cover(PeepSoAjaxResponse $resp)
    {
        $this->init();

        // SQL safe, WP sanitizes it
        if ($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'profile-photo')) {
            $x = $this->_input->int('x', 0);
            $y = $this->_input->int('y', 0);

            update_user_meta($this->user_id, 'peepso_cover_position_x', $x);
            update_user_meta($this->user_id, 'peepso_cover_position_y', $y);

            $resp->success(TRUE);
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**
     * Cover - delete
     * @param PeepSoAjaxResponse $resp
     */
    public function remove_cover_photo(PeepSoAjaxResponse $resp)
    {
        $this->init();

        // SQL safe, WP sanitizes it
        if ($this->can_edit() && wp_verify_nonce($this->_input->value('_wpnonce','',FALSE), 'cover-photo')) {
            $resp->success($this->user->delete_cover_photo());
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**************** AJAX - NOTIFICATIONS ****************/

    /*
     * Performs delete operation on notification messages
     * @param PeepSoAjaxResponse $resp The AJAX response object
     */
    public function notification_delete(PeepSoAjaxResponse $resp)
    {
        // SQL Safe
        if ('' === ($delete_values = $this->_input->value('delete', '', FALSE))) {
            $resp->success(FALSE);
            $resp->error(__('Please select at least one notification to delete.', 'peepso-core'));
        } else {
            $note_ids = explode(',', $delete_values);
            $aIds = array();

            foreach ($note_ids as $id) {
                $id = intval($id);
                if (!in_array($id, $aIds))
                    $aIds[] = $id;
            }

            if (0 !== count($aIds)) {
                $note = new PeepSoNotifications();
                $note->delete_by_id($aIds);
            }

            $resp->success(1);
        }
    }

    /**************** AJAX - misc****************/

    /**
     * Performs delete operation on the current user's profile information
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function delete_profile(PeepSoAjaxResponse $resp)
    {
        $this->init();
        $pass = $this->_input->raw('password');
        $user = $this->user->get_user();
        if ( $user && wp_check_password( $pass, $user->data->user_pass, $user->ID ) ) {
            if($this->can_edit()) {
                require_once(ABSPATH.'wp-admin/includes/user.php');
                wp_delete_user($this->user_id);
                wp_logout();

                $resp->set('url', PeepSo::get_page('logout_redirect'));
                $resp->set('messages', __('Your account has been completely removed from our system. Please bear in mind, it might take a while to completely delete all your content.', 'peepso-core'));
                $resp->success(TRUE);
            } else {
                $resp->error(__('You don\'t have permissions to do this.', 'peepso-core'));
                $resp->success(FALSE);
            }
        } else {
            $resp->error(__('Invalid password.', 'peepso-core'));
            $resp->success(FALSE);
        }
    }

    /**
     * Performs request account data operation on the current user's profile information
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function request_account_data(PeepSoAjaxResponse $resp)
    {
        $this->init();
        $pass = $this->_input->raw('password');
        $user = $this->user->get_user();
        if ( $user && wp_check_password( $pass, $user->data->user_pass, $user->ID ) ) {
            if($this->can_edit()) {

                PeepSoGdpr::add($user->ID);

                $resp->set('url', $this->user->get_profileurl() . 'about/account/');
                $resp->set('messages', __('Your request has been recorded by our system.', 'peepso-core'));
                $resp->success(TRUE);
            } else {
                $resp->error(__('You don\'t have permissions to do this.', 'peepso-core'));
                $resp->success(FALSE);
            }
        } else {
            $resp->error(__('Invalid password.', 'peepso-core'));
            $resp->success(FALSE);
        }
    }

    /**
     * Performs download account data operation on the current user's profile information
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function download_account_data(PeepSoAjaxResponse $resp)
    {
        $this->init();
        $pass = $this->_input->raw('password');
        $user = $this->user->get_user();
        if ( $user && wp_check_password( $pass, $user->data->user_pass, $user->ID ) ) {
            if($this->can_edit()) {

                $request_exists = PeepSoGdpr::request_exists($user->ID);
                if (count($request_exists) > 0 && (isset($request_exists[0]) && $request_exists[0]->request_status == PeepSoGdpr::STATUS_SUCCESS)) {
                    $url = $request_exists[0]->request_file_url;

                    $resp->set('url', $url);
                    $resp->set('messages', __('Your download is starting.', 'peepso-core'));
                    $resp->success(TRUE);
                } else {
                    $resp->error(__('Account data not found.', 'peepso-core'));
                    $resp->success(FALSE);
                }
            } else {
                $resp->error(__('You don\'t have permissions to do this.', 'peepso-core'));
                $resp->success(FALSE);
            }
        } else {
            $resp->error(__('Invalid password.', 'peepso-core'));
            $resp->success(FALSE);
        }
    }

    /**
     * Performs delete account data archive operation on the current user's profile information
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function delete_account_data_archive(PeepSoAjaxResponse $resp)
    {
        $this->init();
        $pass = $this->_input->raw('password');
        $user = $this->user->get_user();
        if ( $user && wp_check_password( $pass, $user->data->user_pass, $user->ID ) ) {
            if($this->can_edit()) {

                PeepSoGdpr::delete_request($user->ID);

                $resp->set('url', $this->user->get_profileurl() . 'about/account/');
                $resp->set('messages', __('Your achive has been deleted.', 'peepso-core'));
                $resp->success(TRUE);
            } else {
                $resp->error(__('You don\'t have permissions to do this.', 'peepso-core'));
                $resp->success(FALSE);
            }
        } else {
            $resp->error(__('Invalid password.', 'peepso-core'));
            $resp->success(FALSE);
        }
    }

    /**
     * Like/unlike action
     * @param PeepSoAjaxResponse $resp
     * @return bool
     */
    public function like(PeepSoAjaxResponse $resp)
    {
        $this->init();

        if(PeepSo::check_permissions($this->user_id, PeepSo::PERM_PROFILE_LIKE, $this->acting_user_id)) {

            $PeepSoLike = PeepSoLike::get_instance();

            if (FALSE === $PeepSoLike->user_liked($this->user_id, PeepSo::MODULE_ID, $this->acting_user_id)) {

                $PeepSoLike->add_like($this->user_id, PeepSo::MODULE_ID, $this->acting_user_id);

                $PeepSoUser = PeepSoUser::get_instance($this->acting_user_id);
                $data = array(
                    'permalink' => PeepSo::get_page('profile') . '?notifications',
                );
                $data = array_merge($data, $PeepSoUser->get_template_fields('from'), $this->user->get_template_fields('user'));
                PeepSoMailQueue::add_notification($this->user_id, $data, __('Someone liked your profile', 'peepso-core'), 'like_profile', 'profile_like', PeepSo::MODULE_ID);

                $PeepSoNotifications = new PeepSoNotifications();
                $PeepSoNotifications->add_notification($this->acting_user_id, $this->user_id, __('liked your profile', 'peepso-core'), 'profile_like', PeepSo::MODULE_ID);

            } else {
                $PeepSoLike->remove_like($this->user_id, PeepSo::MODULE_ID, get_current_user_id());
            }

            $resp->success(TRUE);
            $resp->set('like_count', $PeepSoLike->get_like_count($this->user_id, PeepSo::MODULE_ID));

            ob_start();
            $this->interactions();
            $resp->set('html', ob_get_clean());
            return;
        }

        $resp->success(FALSE);
        return;
    }

    /**
     * Report a profile
     * @param PeepSoAjaxResponse $resp
     */
    public function report(PeepSoAjaxResponse $resp)
    {
        $this->init();
        $reason = $this->_input->value('reason', '', FALSE); // SQL Safe
        $reason_desc = $this->_input->value('reason_desc', '', FALSE); // SQL Safe

        if (PeepSo::check_permissions($this->user_id , PeepSo::PERM_REPORT, $this->acting_user_id)) {
            if (!empty($reason_desc)) {
                $reason = $reason . ' - ' . $reason_desc;
            }
            $rep = new PeepSoReport();
            $rep->add_report($this->user_id, $this->acting_user_id, PeepSo::MODULE_ID, $reason);

            $resp->success(TRUE);
            $resp->notice(__('This profile has been reported', 'peepso-core'));
            return;
        }

        $resp->success(FALSE);
        return;
    }


    /**************** UTILITIES - NOTIFICATIONS ****************/
    /*
   * Determine if user has any pending notifications
   */
    public function has_notifications()
    {
        return (0 !== $this->num_notifications());
    }

    /*
     * Return number of pending notifications
     * @return int Number of pending notifications
     */
    public function num_notifications()
    {
        if (0 === $this->num_notifications) {
            $note = new PeepSoNotifications();
            $this->num_notifications = $note->get_count_for_user(get_current_user_id());
        }
        return ($this->num_notifications);
    }

    /*
     * Checks for any remaining notifications and sets up current notification data
     * for showing with 'show_notification' template tag.
     * @return Boolean TRUE if more notifications; otherwise FALSE
     */
    public function next_notification($limit = 40, $offset = 0, $unread_only =0 )
    {
        if (NULL === $this->notifications) {
            $note = new PeepSoNotifications();
            $this->notifications = $note->get_by_user(get_current_user_id(), $limit, $offset, $unread_only);
            $this->note_idx = 0;
        }

        if (0 !== count($this->notifications)) {
            if ($this->note_idx >= count($this->notifications)) {
                return (FALSE);											// ran out; exit loop
            } else {
                $this->note_data = get_object_vars($this->notifications[$this->note_idx]);
                ++$this->note_idx;
                return (TRUE);
            }
        } else {
            return (FALSE);
        }
    }

    /*
     * Outputs notification content based on template
     */
    public function show_notification()
    {
        PeepSoTemplate::exec_template('profile', 'notification', $this->note_data);
    }

    /*
     * Display notifications age in human readable form
     */
    public function notification_age()
    {
        $post_date = mysql2date('U', $this->note_data['not_timestamp'], FALSE);
        $curr_date = date('U', current_time('timestamp', 0));

        echo '<span title="', esc_attr($this->note_data['not_timestamp'], ' ', $this->note_data['not_timestamp']), '">';
        echo PeepSoTemplate::time_elapsed($post_date, $curr_date), '</span>';
    }

    /*
     * Displays the notification record's ID value
     */
    public function notification_id($echo = TRUE)
    {
        $id = $this->note_data['not_id'];

        if ( !$echo ) {
            return $id;
        }

        echo $id;
    }

    /*
     * Displays the notification record's "from" user id
     */
    public function notification_user()
    {
        return ($this->note_data['not_from_user_id']);
    }

    /**
     * Get read status from notification
     */
    public function notification_readstatus()
    {
        if ( intval($this->note_data['not_read']) === 1 ) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /*
     * Displays the link for the notification's content
     */
    public function notification_link($echo = 1)
    {
        /*if (0 === intval($this->note_data['not_external_id']))
            return;*/

        $link = PeepSo::get_page('activity_status') . $this->note_data['post_title'] . '/';
        $link = apply_filters('peepso_profile_notification_link', $link, $this->note_data);

        // checking if the like was not actually made on a comment
        // @todo this might be a bit MySQL expensive
        $is_a_comment = 0;
        if ('user_comment' === $this->note_data['not_type']) {
            $is_a_comment = 1;
        }

        if ('like_post' == $this->note_data['not_type']) {
            global $wpdb;
            $sql = 'SELECT COUNT(id) as `is_comment_like` FROM `' . $wpdb->prefix . 'posts` WHERE `post_type`=\'peepso-comment\' AND ID=' . $this->note_data['not_external_id'];
            $res = $wpdb->get_row($sql);

            $is_a_comment = $res->is_comment_like;
        }

        $print_link = '';
        $activity_type = array(
            'type' => 'post',
            'text' => __('post', 'peepso-core')
        );

        if ('stream_reply_comment' === $this->note_data['not_type']) {

            $activities = PeepSoActivity::get_instance();

            $not_activity = $activities->get_activity_data($this->note_data['not_external_id'], $this->note_data['not_module_id']);
            $comment_activity = $activities->get_activity_data($not_activity->act_comment_object_id, $not_activity->act_comment_module_id);
            $post_activity = $activities->get_activity_data($comment_activity->act_comment_object_id, $comment_activity->act_comment_module_id);

            if (is_object($comment_activity) && is_object($post_activity)) {
                $parent_comment = $activities->get_activity_post($comment_activity->act_id);
                $parent_post = $activities->get_activity_post($post_activity->act_id);
                $parent_id = $parent_comment->act_external_id;

                $post_link = PeepSo::get_page('activity_status') . $parent_post->post_title . '/';
                $comment_link = $post_link . '?t=' . time() . '#comment.' . $post_activity->act_id . '.' . $parent_comment->ID . '.' . $comment_activity->act_id . '.' . $not_activity->act_external_id;

                if( 0 === intval($echo) ) {
                    return $comment_link;
                }

                ob_start();

                echo ' ';
                $post_content = __('a comment', 'peepso-core');

                if (intval($parent_comment->post_author) === get_current_user_id()) {
                    $post_content =  ($this->note_data['not_message'] != __('replied to', 'peepso-core')) ? __('on ', 'peepso-core') : '';
                    $post_content .= __('your comment', 'peepso-core');
                }

                echo $post_content;

                $print_link = ob_get_clean();
            }

        } else if ('profile_like' === $this->note_data['not_type']) {

            $author = PeepSoUser::get_instance($this->note_data['not_from_user_id']);

            $link = $author->get_profileurl();

            if( 0 === intval($echo) ) {
                return $link;
            }

        } else if (1 == $is_a_comment) {

            $activities = PeepSoActivity::get_instance();

            $not_activity = $activities->get_activity_data($this->note_data['not_external_id'], $this->note_data['not_module_id']);

            $parent_activity = $activities->get_activity_data($not_activity->act_comment_object_id, $not_activity->act_comment_module_id);
            if (is_object($parent_activity)) {
                $not_post = $activities->get_activity_post($not_activity->act_id);
                $parent_post = $activities->get_activity_post($parent_activity->act_id);
                $parent_id = $parent_post->act_external_id;

                // modify the type of post (eg. post, photo, video, avatar, cover);
                $activity_type = apply_filters('peepso_notifications_activity_type', $activity_type, $parent_id, NULL);

                // check if parent post is a comment
                if($parent_post->post_type == 'peepso-comment') {
                    $comment_activity = $activities->get_activity_data($not_activity->act_comment_object_id, $not_activity->act_comment_module_id);
                    $post_activity = $activities->get_activity_data($comment_activity->act_comment_object_id, $comment_activity->act_comment_module_id);

                    $parent_post = $activities->get_activity_post($post_activity->act_id);
                    $parent_comment = $activities->get_activity_post($comment_activity->act_id);

                    $parent_link = PeepSo::get_page('activity_status') . $parent_post->post_title . '/?t=' . time() . '#comment.' . $post_activity->act_id . '.' . $parent_comment->ID . '.' . $comment_activity->act_id . '.' . $not_activity->act_external_id;
                } else {
                    $parent_link = PeepSo::get_page('activity_status') .  $parent_post->post_title . '/#comment.' . $parent_activity->act_id . '.' . $not_post->ID . '.' . $not_activity->act_external_id;
                }

                if( 0 === intval($echo) ) {
                    return $parent_link;
                }

                ob_start();
                $post_content = '';
                $on = '';
                if($activity_type['type'] == 'post') {
                    $on = ' ' . __('on', 'peepso-core');
                    $post_content = sprintf(__('a %s', 'peepso-core'), $activity_type['text']);
                }

                /* todo : add some filter for handling notification type cover/avatar*/
                if (intval($parent_post->post_author) === get_current_user_id() || (intval($parent_post->post_author) === get_current_user_id() && in_array($activity_type['type'], array('cover','avatar')))) {
                    $on = ' ' . __('on', 'peepso-core');
                    $post_content = sprintf(__('your %s', 'peepso-core'), $activity_type['text']);
                }

                if(in_array($activity_type['type'], array('cover','avatar')) && (intval($parent_post->post_author) !== get_current_user_id()))
                {
                    $on = ' ' . __('on', 'peepso-core');
                    if(preg_match('/^[aeiou]/i', strtolower($activity_type['text']))) {
                        $post_content = sprintf(__('an %s', 'peepso-core'), $activity_type['text']);
                    } else {
                        $post_content = sprintf(__('a %s', 'peepso-core'), $activity_type['text']);
                    }
                }

                echo $on, ' ';

                echo $post_content;

                $print_link = ob_get_clean();


            }
        } else {

            if( 0 === intval($echo) ) {
                return $link;
            }

            if ('share' === $this->note_data['not_type']) {

                $activities = PeepSoActivity::get_instance();
                $repost = $activities->get_activity_data($this->note_data['not_external_id'], $this->note_data['not_module_id']);
                $orig_post = $activities->get_activity_post($repost->act_repost_id);

                // modify the type of post (eg. post, photo, video, avatar, cover);
                $activity_type = apply_filters('peepso_notifications_activity_type', $activity_type, $orig_post->ID, NULL);

                ob_start();
                echo ' ' , sprintf(__('your %s', 'peepso-core'), $activity_type['text']);

                $print_link = ob_get_clean();
            }
        }

        $print_link = apply_filters('peepso_modify_link_item_notification', array($print_link, $link), $this->note_data);

        if(is_array($print_link)) {
            echo $print_link[0];
        } else {
            echo $print_link;
        }
    }


    /*
     * Displays the notification message
     */
    public function notification_message()
    {
        echo trim($this->note_data['not_message'],' .');
    }

    public function notification_human_friendly() {

        $icon = '';

        if(!PeepSo::get_option('notification_previews',0)) {
            return;
        }

        $preview = get_post_meta($this->note_data['not_external_id'],'peepso_human_friendly', TRUE);

        if(!strlen($preview) && class_exists('PeepSoGroupsPlugin') && $this->note_data['not_module_id']==PeepSoGroupsPlugin::MODULE_ID) {
            $PeepSoGroup = new PeepSoGroup($this->note_data['not_external_id']);
            $preview = $PeepSoGroup->name;

            $icon= '<i class="ps-icon-group"></i>';
        }

//        if(!strlen($preview) && strlen($post_title=$this->note_data['post_title'])) {
//            $preview = $post_title;
//        }

        if(!is_array($preview) && strlen($preview)) {
            ?>
            <div style="font-style:italic;">

                <?php echo $icon;?>

                <?php echo trim(truncateHtml($preview, PeepSo::get_option('notification_preview_length',50), PeepSo::get_option('notification_preview_ellipsis','...'), false, FALSE)); ?>
            </div>
            <?php
        }
    }

    /*
     * Displays the notification record's timestamp value
     */
    public function notification_timestamp()
    {
        echo $this->note_data['not_timestamp'];
    }

    /*
     * Displays the notification record's type
     */
    public function notification_type()
    {
        echo $this->note_data['not_type'];
    }


    /**************** UTILITIES - ACTIONS, INTERACTIONS & MENUS ****************/

    /**
     * Render profile segment menu
     * @param $args
     * @return string
     */
    public function profile_navigation($args)
    {
        $links = array('_user_id'=>$this->user_id);
        $links = apply_filters('peepso_navigation_profile', $links);

        $args['links'] = $links;
        return PeepSoTemplate::exec_template('profile','profile-menu', $args);
    }

    public function profile_actions()
    {
        $act = array();

        if (is_user_logged_in()) {
            if ($this->user_id == get_current_user_id() && $this->_url_segments->_shortcode == "peepso_profile" && $this->_url_segments->get(2) == null) {
                $act['update_info'] = array(
                    'label' => __('Update Info', 'peepso-core'),
                    'class' => 'ps-btn ps-btn-small',
                    'title' => __('Redirect to about page', 'peepso-core'),
                    'icon'	=> 'pencil',
                    'click' => 'window.location="'.$this->user->get_profileurl().'about"; return false;',
                );
            }
            $act = apply_filters('peepso_profile_actions', $act, $this->user_id);
        }

        foreach ($act as $name => $data) {

            echo '<a href="#" ';
            if (isset($data['class']))
                echo ' class="', esc_attr($data['class']), '" ';
            if (isset($data['title']))
                echo ' title="', esc_attr($data['title']), '" aria-label="', esc_attr($data['title']), '" ';
            if (isset($data['click']))
                echo ' onclick="', esc_js($data['click']), '" ';

            if (isset($data['extra']))
                echo $data['extra'];
            echo '>';
            if (isset($data['icon']))
                echo '<i class="ps-icon-' . $data['icon'] . '"></i> ';
            if (isset($data['label']))
                echo'<span>' . $data['label'] . '</span>';

            echo '<img style="display:none" src="', PeepSo::get_asset('images/ajax-loader.gif'), '"></a>', PHP_EOL;
        }

        if (is_user_logged_in()) {
            PeepSoMemberSearch::member_options($this->user_id, TRUE);
        }
    }

    /*
     * Output a series of <li> with links for profile interactions
     */
    public function interactions()
    {
        $aAct = array();


        // @todo privacy
        if (PeepSo::get_option('profile_sharing', TRUE)) {
            $aAct['share'] = array(
                'label' => __('Share', 'peepso-core'),
                'title' => __('Share this Profile', 'peepso-core'),
                'click' => 'share.share_url("' . $this->user->get_profileurl() . '"); return false;',
                'icon' => 'share-alt',
                'order' => 10
            );
        }


        if (is_user_logged_in()) {
            if (PeepSo::get_option('site_likes_profile', TRUE) && $this->user->is_profile_likable()) {
                $peepso_like = PeepSoLike::get_instance();
                $likes = $peepso_like->get_like_count($this->user_id, PeepSo::MODULE_ID);

                if (FALSE === $peepso_like->user_liked($this->user_id, PeepSo::MODULE_ID, get_current_user_id())) {
                    $like_icon = 'thumbs-up';
                    $like_label = __('Like', 'peepso-core');
                    $like_title = __('Like this Profile', 'peepso-core');
                    $like_liked = FALSE;
                } else {
                    $like_icon = 'thumbs-up';
                    $like_label = __('Like', 'peepso-core');
                    $like_title = __('Unlike this Profile', 'peepso-core');
                    $like_liked = TRUE;
                }

                $aAct['like'] = array(
                    'label' => $like_label,
                    'title' => $like_title,
                    'click' => 'profile.new_like();',
                    'icon' => $like_icon,
                    'count' => (! empty($likes) ? $likes : 0),
                    'class' => $like_liked ? 'liked' : '',
                    'order' => 20
                );
            }
        }

        $aAct['views'] = array(
            'label' => __('Views', 'peepso-core'),
            'title' => __('Profile Views', 'peepso-core'),
            'icon' => 'eye',
            'count' => $this->init()->get_view_count(), // PeepSoViewLog::get_views($this->user_id, PeepSo::MODULE_ID),
            'order' => 40
        );

        $aAct = apply_filters('peepso_user_activities_links', $aAct);

        $sort_col = array();

        foreach ($aAct as $item)
            $sort_col[] = (isset($item['order']) ? $item['order'] : 50);

        array_multisort($sort_col, SORT_ASC, $aAct);

        foreach ($aAct as $sName => $aAttr) {
            $withClick = (isset($aAttr['click']) && !empty($aAttr['click']));
//echo '<!-- label=', $aAttr['label'], ' -->', PHP_EOL;
            if ($withClick)
                echo '<a class="ps-focus__interactions-item" href="#" onclick="', esc_js(trim($aAttr['click'], ';')), '; return false;" ',
                (isset($aAttr['title']) ? ' title="' . esc_attr($aAttr['title']) . '" ' : ''),
                (isset($aAttr['class']) ? ' class="' . esc_attr($aAttr['class']) . '" ' : ''),
                '>', PHP_EOL;
            else
                echo '<span class="ps-focus__interactions-item" ',
                (isset($aAttr['title']) ? ' title="' . esc_attr($aAttr['title']) . '" ' : ''),
                '>', PHP_EOL;

            echo '<i class="ps-icon-', esc_attr($aAttr['icon']), '"></i>';
            if (isset($aAttr['count'])) {
                $count = $aAttr['count'];

                // if the key "all_values" is not present, values below 1 will not render
                if( $count<1 && (!array_key_exists('all_values', $aAttr) || FALSE === $aAttr['all_values'])) {
                    $count = '';
                }

                echo '<span id="', $sName, '-count">', $count , '</span>&nbsp;';
            }
//			echo		$aAttr['label'], PHP_EOL;
            echo ($withClick ? '</a>' : '</span>'), PHP_EOL;
        }
    }

    /**************** UTILITIES - ALERTS ****************/

    /**
     * Defines all alerts
     * @return array $alerts List of all alerts
     */
    public function get_alerts_definition()
    {
        static $alerts = NULL;
        if (NULL !== $alerts)
            return ($alerts);

        $activity_items = array(
            /*
            array(
                // TODO: what's the difference between 'new comment' and 'reply comment'? Are these all Comments on Posts? The wording needs to be more clear
                // Art: They are just copied from the Technical Specs, see Enhancement specs
                'label' => __('New Comments on Stream Items', 'peepso-core'),
                'setting' => 'stream_new_comment',
            ),
            array(
                'label' => __('New Replies to Stream Comments', 'peepso-core'),
                'setting' => 'stream_reply_comment',
            ),
            */
            array(
                'label' => __('Someone wrote a post on my Profile', 'peepso-core'),
                'setting' => 'wall_post',
                'loading' => TRUE,
            ),
            array(
                'label' => __('Someone commented on my Post', 'peepso-core'),
                'setting' => 'user_comment',
                'loading' => TRUE,
            ),
            array(
                'label' => __('Someone liked my Post', 'peepso-core'),
                'setting' => 'like_post',
                'loading' => TRUE,
            ),
            array(
                'label' => __('Someone liked my Comment', 'peepso-core'),
                'setting' => 'like_comment',
                'loading' => TRUE,
            ),
            array(
                'label' => __('Someone replied to my Comment', 'peepso-core'),
                'setting' => 'stream_reply_comment',
                'loading' => TRUE,
            ),
            // TODO: need to add settings for each type of alert/email being created
            // Art: I don't think we need this? 2 checkboxes are created for each setting
            // TODO: check calls to PeepSoNotifications::add_notification() and PeepSoMailQueue::add_messsage()- we need a config setting for each of those
            // Art: I'm not quite understand, for each setting we have 2 distinct names, for instance 'stream_reply_comment' creates 2 settings named 'stream_reply_comment_notification' and 'stream_reply_comment_email' and they are controlled or managed by 2 checkboxes for each setting, hence the 'stream_reply_comment' is just a prefix for the 2 notifications
        );

        if (PeepSo::get_option('site_repost_enable', TRUE)) {
            array_push($activity_items, array(
                'label' => __('Someone shared my Post', 'peepso-core'),
                'setting' => 'share',
                'loading' => TRUE,
            ));
        }

        $alerts = array(
            'activity' => array(
                'title' => __('Activity Stream', 'peepso-core'),
                'items' => $activity_items,
            ),
            'profile' => array(
                'title' => __('Profile', 'peepso-core'),
                'items' => array(
                    array(
                        'label' => __('Someone liked my Profile', 'peepso-core'),
                        'setting' => 'profile_like',
                        'loading' => TRUE,
                    ),
                    // TODO: we *always* want emails/notifications for password change/recovery messages. These are not to be user configurable since these are on user demand.
                    /*					array(
                                            'label' => __('Change Password', 'peepso-core'),
                                            'setting' => 'password_changed',
                                        ),
                                        array(
                                            'label' => __('Password Recovery', 'peepso-core'),
                                            'setting' => 'password_recover',
                                        ), */
                ),
            ),

            // NOTE: when adding new items here, also add settings to /install/activate.php site_alerts_ sections
        );

        if (!PeepSo::get_option('site_likes_profile', TRUE)) {
            unset($alerts['profile']);
        }

        $alerts = apply_filters('peepso_profile_alerts', $alerts);
        return ($alerts);
    }

    /**
     * Get available or configurable alerts
     * @return array List of alerts where user can override
     */
    public function get_available_alerts()
    {
        $alerts = array();
        $alerts_definition = $this->get_alerts_definition();

        foreach ($alerts_definition as $key => $value) {
            if (isset($value['items'])) {
                $alerts[$key] = $value;
            }
        }

        return ($alerts);
    }

    /**
     * Get alerts form fields definitions
     * @return array $fields
     */
    public function get_alerts_form_fields()
    {
        $alerts = $this->get_available_alerts();

        $fields = array();
        if (!empty($alerts)) {
            // append group alerts to field
            $fields['group_alerts'] = array(
                'label' => '',
                'descript' => '',
                'value' => 1,
                'fields' => array(),
                'type' => 'title',
                'section' => 1,
            );

            $fields['form_header'] = array(
                'label' => '',
                'field_wrapper_class' => 'ps-js-preferences-header',
                'fields' => array(
                    array(
                        'label' => __('On-Site', 'peepso-core'),
                        'type' => 'label',
                        'id' => 'onsite',
                    ),
                    array(
                        'label' => __('Email', 'peepso-core'),
                        'type' => 'label',
                        'id' => 'email',
                    ),
                ),
                'type' => 'custom',
            );

            $counter = 0;

            // generate form fields
            foreach ($alerts as $key => $value) {
                // generate section
                $fields[$key] = array(
                    'label' => '',
                    'descript' => "<b>{$value['title']}</b>",
                    'value' => 1,
                    'fields' => array(),
                    'type' => 'custom',
                    'section' => 1,
                );

                // title
                if (!isset($value['items']) || empty($value['items']))
                    continue;

                $peepso_notifications = get_user_meta(get_current_user_id(), 'peepso_notifications');
                $notifications = ($peepso_notifications) ? $peepso_notifications[0] : array();
                if (count($value['items']) <= 1)
                    $fields[$key]['fields'] = array();

                // generate items
                foreach ($value['items'] as $item) {
                    $name_email = "{$item['setting']}_email";
                    $name_notification = "{$item['setting']}_notification";
                    $fields[$item['setting']] = array(
                        'label' => '',
                        'descript' => ''.$item['label'],
                        'value' => 1,
                        'fields' => array(
                            array(
                                'label' => 'onsite',
                                'name' => $name_notification,
                                'type' => 'checkbox',
                                'group_key' => "__{$key}_notification",
                                'value' => apply_filters('peepso_get_notification_value', !in_array($name_notification, $notifications) ? 1 : 0, $name_notification),
                            ),
                            array(
                                'label' => 'email',
                                'name' => $name_email,
                                'type' => 'checkbox',
                                'group_key' => "__{$key}_email",
                                'value' => apply_filters('peepso_get_notification_value', !in_array($name_email, $notifications) ? 1 : 0, $name_email),
                            ),
                        ),
                        'type' => 'custom',
                        'loading' => (isset($item['loading']) && $item['loading'] ? 1 : 0),
                    );
                }
            }
        }
        $fields = apply_filters('peepso_profile_alerts_form_fields', $fields);
        return ($fields);
    }

    /**************** UTILITIES - BLOCKED USERS ****************/

    public function num_blocked()
    {
        if (0 === $this->num_blocked) {
            $blk = new PeepSoBlockUsers();
            $this->num_blocked = $blk->get_count_for_user(get_current_user_id());
        }

        return ($this->num_blocked);
    }

    public function block_user()
    {
        return ($this->block_data['blk_blocked_id']);
    }

    public function block_username()
    {
        $PeepSoUser = PeepSoUser::get_instance($this->block_data['blk_blocked_id']);
        echo $PeepSoUser->get_fullname();
    }

    /**************** UTILITIES - EDIT ACCOUNT ****************/

    /**
     * Render the form
     */
    public function edit_form()
    {
        if($this->can_edit()) {

            $fields = array(
                'verify_password' => array(
                    'label' => __('Current Password', 'peepso-core'),
                    'descript' => __('Enter your current password to change your account information', 'peepso-core'),
                    'class' => '',
                    'type' => 'password',
                    #'row_wrapper_class' => 'ps-form__row--half',
                ),
                'user_nicename_readonly' => array(
                    'section' => __('Your Account', 'peepso-core'),
                    'label' => __('User Name', 'peepso-core'),
                    #'descript' => __('Enter your user name', 'peepso-core'),
                    'value' => $this->user->get_username(),
                    'type' => 'hidden',
                    'html' => $this->user->get_username(),
                    'row_wrapper_class' => 'ps-form__row--user',
                ),
                'user_nicename' => array(
                    'section' => __('Your Account', 'peepso-core'),
                    'label' => __('User Name', 'peepso-core'),
                    'descript' => __('If you change your username, you will be signed out', 'peepso-core'),
                    'value' => $this->user->get_username(),
                    'required' => 1,
                    'type' => 'text',
                    'class' => 'ps-input-readonly',
                    'extra'=>'readonly',
                    'validation' => array(
                        'username',
                        'required',
                        'minlen:' . PeepSoUser::USERNAME_MINLEN,
                        'maxlen:' . PeepSoUser::USERNAME_MAXLEN,
                        'custom'
                    ),
                    'validation_options' => array(
                        'error_message' => __('That username is already in use by someone else.', 'peepso-core'),
                        'function' => array($this, 'check_username_change')
                    )
                ),
                'user_email_readonly' => array(
                    'section' => __('Your Account', 'peepso-core'),
                    'label' => __('Email', 'peepso-core'),
                    'value' => $this->user->get_email(),
                    'type' => 'hidden',
                    'html' => $this->user->get_email(),
                ),
                'account' => array(
                    'value' => 1,
                    'type' => 'hidden',
                ),
                'user_email' => array(
                    'section' => __('Your Account', 'peepso-core'),
                    'label' => __('Email', 'peepso-core'),
                    #'descript' => __('Enter your email address', 'peepso-core'),
                    'value' => $this->user->get_email(),
                    'required' => 1,
                    'type' => 'text',
                    'class' => 'ps-input-readonly',
                    'extra'=>'readonly',
                    'validation' => array(
                        'email',
                        'required',
                        'maxlen:' . PeepSoUser::EMAIL_MAXLEN,
                        'custom'
                    ),
                    'validation_options' => array(
                        'error_message' => __('This email is already in use by someone else.', 'peepso-core'),
                        'function' => array($this, 'check_email_change')
                    )
                ),
                'change_password' => array(
                    'label' => __('Change Password', 'peepso-core'),
                    'descript' => __('If you change your password, you will be signed out', 'peepso-core'),
                    'class' => 'ps-input-readonly',
                    'type' => 'password',
                    'validation' => array('password'),
                    'extra'=>'readonly',
                    #'row_wrapper_class' => 'ps-form__row--half',
                    /*'validation_options' => array(
                        'error_message' => __('Passwords mismatched.', 'peepso-core'),
                        'function' => array($this, 'check_password_change'),
                    ),*/
                ),
                'user_id' => array(
                    'type' => 'hidden',
                    'value' => $this->user_id,
                ),
                'task' => array(
                    'type' => 'hidden',
                    'value' => 'profile_edit_save',
                ),
                '-form-id' => array(
                    'type' => 'hidden',
                    'value' => wp_create_nonce('profile-edit-form'),
                ),
                'authkey' => array(
                    'type' => 'hidden',
                    'value' => '',
                ),
            );

            // enable username change
            if (0 === intval(PeepSo::get_option('system_allow_username_changes', 0))) {
                $fields['user_nicename']['type'] = 'hidden';
                $fields['user_nicename_readonly']['type'] = 'html';
            }

            $fields['submit'] = array(
                'label' => __('Save', 'peepso-core'),
                'class' => 'ps-btn-primary',
                'click' => 'submitbutton(\'frmSaveProfile\'); return false;',
                'type' => 'submit',
            );

            $form = array(
                'container' => array(
                    'element' => 'div',
                    'class' => 'ps-form__container',
                ),
                'fieldcontainer' => array(
                    'element' => 'div',
                    'class' => 'ps-form__row',
                ),
                'form' => array(
                    'name' => 'profile-edit',
                    'action' => $this->user->get_profileurl(). 'about/account/',
                    'method' => 'POST',
                    'class' => 'community-form-validate',
                    'extra' => 'autocomplete="off"',
                ),
                'fields' => $fields,
            );

            $peepso_form = PeepSoForm::get_instance();
            $peepso_form->render(apply_filters('peepso_profile_edit_form', $form));
        }
    }




    /**
     * Read or write message to be displayed after form is saved
     * @param bool $set
     * @return bool|null
     */
    public function edit_form_message($set = FALSE)
    {
        if(FALSE != $set) {
            $this->message = $set;
        }

        if (!is_null($this->message)) {
            return $this->message;
        }

        return FALSE;
    }

    /**************** UTILITIES - DELETE ACCOUNT ****************/

    /**
     * Render the form
     */
    public function delete_form()
    {
        if($this->can_delete()) {

            $fields = array(
                'profile_username' => array(
                    'section' => __('Profile Deletion', 'peepso-core'),
                    'label' => __('User Name', 'peepso-core'),
                    #'descript' => __('Enter your user name', 'peepso-core'),
                    'value' => $this->user->get_username(),
                    'type' => 'hidden',
                    'html' => $this->user->get_username(),
                ),
                'delete_account' => array(
                    'value' => 1,
                    'type' => 'hidden',
                ),
                'profile_user_id' => array(
                    'type' => 'hidden',
                    'value' => $this->user_id,
                ),
                'task' => array(
                    'type' => 'hidden',
                    'value' => 'profile_delete',
                ),
                '-form-id' => array(
                    'type' => 'hidden',
                    'value' => wp_create_nonce('profile-delete-form'),
                ),
                'authkey' => array(
                    'type' => 'hidden',
                    'value' => '',
                ),
            );

            $fields['submit'] = array(
                'label' => __('Delete', 'peepso-core'),
                'class' => 'ps-btn-danger',
                'click' => 'profile.profile_deletion(this); return false;',
                'type' => 'submit',
            );

            $form = array(
                'container' => array(
                    'element' => 'div',
                    'class' => 'ps-form__container',
                ),
                'fieldcontainer' => array(
                    'element' => 'div',
                    'class' => 'ps-form__row',
                ),
                'form' => array(
                    'name' => 'profile-delete',
                    'action' => $this->user->get_profileurl(). 'about/account/',
                    'method' => 'POST',
                    'class' => 'community-form-validate',
                    'extra' => 'autocomplete="off"',
                ),
                'fields' => $fields,
            );

            $peepso_form = PeepSoForm::get_instance();
            $peepso_form->render($form);
        }
    }

    /**************** UTILITIES - DELETE ACCOUNT ****************/

    /**
     * Render the form
     */
    public function request_data_form()
    {

        $can_edit = FALSE;
        if(get_current_user_id()) {
            $can_edit = TRUE;
        }

        $request_exists = PeepSoGdpr::request_exists(get_current_user_id());
        $content = '';

        if($can_edit) {

            $fields = array(
                'profile_username' => array(
                    'section' => __('Request your data.', 'peepso-core'),
                    'label' => __('User Name', 'peepso-core'),
                    #'descript' => __('Enter your user name', 'peepso-core'),
                    'value' => $this->user->get_username(),
                    'type' => 'hidden',
                    'html' => $this->user->get_username(),
                ),
                'request_account_data' => array(
                    'value' => 1,
                    'type' => 'hidden',
                ),
                'profile_user_id' => array(
                    'type' => 'hidden',
                    'value' => $this->user_id,
                ),
                'task' => array(
                    'type' => 'hidden',
                    'value' => 'request_account_data',
                ),
                '-form-id' => array(
                    'type' => 'hidden',
                    'value' => wp_create_nonce('request-account-data-form'),
                ),
                'authkey' => array(
                    'type' => 'hidden',
                    'value' => '',
                ),
            );

            $fields['submit'] = array(
                'label' => __('Export my Community data', 'peepso-core'),
                'class' => 'ps-btn-danger',
                'click' => 'profile.request_account_data(this); return false;',
                'type' => 'submit',
            );

            $form = array(
                'container' => array(
                    'element' => 'div',
                    'class' => 'ps-form__container',
                ),
                'fieldcontainer' => array(
                    'element' => 'div',
                    'class' => 'ps-form__row',
                ),
                'form' => array(
                    'name' => 'profile-request-account-data',
                    'action' => $this->user->get_profileurl(). 'about/account/',
                    'method' => 'POST',
                    'class' => 'community-form-validate',
                    'extra' => 'autocomplete="off"',
                ),
                'fields' => $fields,
            );


            $content .= '<p>' . __('You can download a complete copy of all the data you have shared in this Community. This includes posts, messages, photos, videos, comments, etc.  The data will be compiled automatically and delivered to you in a machine-readable JSON format. Please bear in mind that depending on the amount of data that needs to be compiled, preparing your download might take a while.', 'peepso-core') .'</p>';


            if (count($request_exists) > 0 && (isset($request_exists[0]) && $request_exists[0]->request_status != PeepSoGdpr::STATUS_SUCCESS)) {
                $content .= '<blockquote>' . __('Your export is being prepared. We\'ll email you when it\'s ready.', 'peepso-core') .'</blockquote>';
            } else {
                ob_start();
                $peepso_form = PeepSoForm::get_instance();
                $peepso_form->render($form);

                $content .= ob_get_clean();
            }


            //$content .= '<p>' . __('You can access your data by visiting your Activity Log or by downloading your information, or by simply logging into your account.', 'peepso-core') . '</p>';
        }

        if (count($request_exists) > 0 && (isset($request_exists[0]) && ($request_exists[0]->request_status == PeepSoGdpr::STATUS_SUCCESS))) {

            $gdpr = new PeepSoGdpr;
            $array_status = $gdpr::$array_status;

            $fields = array(
                'profile_username' => array(
                    'section' => __('Download your data.', 'peepso-core'),
                    'label' => __('User Name', 'peepso-core'),
                    #'descript' => __('Enter your user name', 'peepso-core'),
                    'value' => $this->user->get_username(),
                    'type' => 'hidden',
                    'html' => $this->user->get_username(),
                ),
                'download_account_data' => array(
                    'value' => 1,
                    'type' => 'hidden',
                ),
                'download_user_id' => array(
                    'type' => 'hidden',
                    'value' => $this->user_id,
                ),
                'task' => array(
                    'type' => 'hidden',
                    'value' => 'download_account_data',
                ),
                '-form-id' => array(
                    'type' => 'hidden',
                    'value' => wp_create_nonce('download-account-data-form'),
                ),
                'authkey' => array(
                    'type' => 'hidden',
                    'value' => '',
                ),
            );

            $fields['submit'] = array(
                'label' => __('Download Archive', 'peepso-core'),
                'class' => 'ps-btn-danger',
                'click' => 'profile.download_account_data(this); return false;',
                'type' => 'submit',
            );

            $fields['delete'] = array(
                'label' => __('Delete Archive', 'peepso-core'),
                'class' => 'ps-btn-danger',
                'click' => 'profile.delete_account_data_archive(this); return false;',
                'type' => 'submit',
            );

            $download_form = array(
                'container' => array(
                    'element' => 'div',
                    'class' => 'ps-form__container',
                ),
                'fieldcontainer' => array(
                    'element' => 'div',
                    'class' => 'ps-form__row',
                ),
                'form' => array(
                    'name' => 'download-request-account-data',
                    'action' => $this->user->get_profileurl(). 'about/account/',
                    'method' => 'POST',
                    'class' => 'community-form-validate',
                    'extra' => 'autocomplete="off"',
                ),
                'fields' => $fields,
            );

            $content = '';
            $content .= '<p>' . __('This is a copy of personal information you\'ve shared on this site. To protect your info, we\'ll ask you to re-enter your password to confirm that this is your account.', 'peepso-core') .  '</p>';
            $content .= '<p>' . __('Please note that your archive will be deleted after one week.', 'peepso-core')  . '</p>';

            ob_start();
            $peepso_form = PeepSoForm::get_instance();
            $peepso_form->render($download_form);

            $content .= ob_get_clean();

            $content .= '<p>' . __('Caution: Protect your archive', 'peepso-core') . '</p>';
            $content .= '<p>' . __('Your data archive includes sensitive info like your private activity, photos and profile information. Please keep this in mind before storing or sending your archive.', 'peepso-core')  . '</p>';
        }

        echo $content;
    }

    /*************** UTILITIES - POSTBOX ****************/

    /**
     * Remove "only me" privace when posting to a different user
     * @param  array $acc The access settings from the apply_filters call.
     * @return array The modified access settings.
     */
    public function filter_postbox_access_settings($acc)
    {
        if (is_int($this->user_id) && $this->user_id !== intval(get_current_user_id())) {
            unset($acc[PeepSo::ACCESS_PRIVATE]);
        }

        return ($acc);
    }

    /**************** UTILITIES - PREFERENCES ****************/

    public function num_preferences_fields()
    {
        return (count($this->get_available_preferences()));
    }

    public function get_preferences_definition($override = FALSE)
    {
        static $pref = NULL;
        if (NULL !== $pref)
            return ($pref);

        if(FALSE == $override) {
            $offset_range = array(-12, -11.5, -11, -10.5, -10, -9.5, -9, -8.5, -8, -7.5, -7, -6.5, -6, -5.5, -5, -4.5, -4, -3.5, -3, -2.5, -2, -1.5, -1, -0.5,
                0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 7.5, 8, 8.5, 8.75, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.75, 13, 13.75, 14);

            $options_gmt = array();

            foreach ($offset_range as $offset) {


                $offset_label = (string)$offset;

                if (0 <= $offset) {
                    $offset_label = '+' . $offset_label;
                }

                $offset_label = 'UTC' . str_replace(array('.25', '.5', '.75'), array(':15', ':30', ':45'), $offset_label);


                $options_gmt[(string)$offset] = $offset_label;
            }

            $group_profile_fields = array();

            if (1 === intval(PeepSo::get_option('system_override_name', 0))) {

                $options = apply_filters('peepso_filter_display_name_styles', []);

                foreach($options as $style => $description) {
                    $description = $description . ': ' . $this->user->get_fullname(FALSE, $style);
                    $options[$style] = $description;
                }

                $group_profile_fields['peepso_profile_display_name_as'] = array(
                    'label' => __('Display my name as', 'peepso-core'),
                    'type' => 'select',
                    'descript' => __('Settings based on real name will display your username if you don\'t provide your real name', 'peepso-core'),
                    'validation' => array(/*'required'*/),
                    'options' => $options,
                    'value' => $this->user->get_display_name_as(),
                    'loading' => TRUE,
                );
            }

            if(PeepSo::get_option('site_likes_profile')) {
                $group_profile_fields['peepso_is_profile_likable'] = array(
                    // 'label' => __('Profile Likes', 'peepso-core'),
                    'label-desc' => __('Allow others to "like" my profile', 'peepso-core'),
                    'value' => $this->user->is_profile_likable(),
                    'type' => 'yesno_switch',
                    'loading' => TRUE,
                );
            }

            $field = PeepSoField::get_field_by_id('birthdate');

            if(is_object($field) && $field->prop('published') && !stristr($field->prop('meta','method'), 'relative')) {
                $group_profile_fields['peepso_hide_birthday_year'] = array(
                    'label-desc' => __('Hide my birthday year', 'peepso-core'),
                    'value' => $this->user->get_hide_birthday_year(),
                    'type' => 'yesno_switch',
                    'validation' => array(/*'required'*/),
                    'loading' => TRUE,
                );
            }


            $group_profile_fields['usr_profile_acc'] = array(
                'label' => __('Who can see my profile', 'peepso-core'),
                'value' => $this->user->get_profile_accessibility(),
                'type' => 'access-profile',
                'validation' => array(/*'required'*/),
                'loading' => TRUE,
            );





            $group_profile_fields['peepso_profile_post_acc'] = array(
                'label' => __('Who can post on my profile', 'peepso-core'),
                'value' => $this->user->get_profile_post_accessibility(),
                'type' => 'access-profile-post',
                'validation' => array(/*'required'*/),
                'loading' => TRUE,
            );

            // Allow users to hide themselves from all user listings
            if (1 === intval(PeepSo::get_option('allow_hide_user_from_user_listing', 0)))
                $group_profile_fields['peepso_is_hide_profile_from_user_listing'] = array(
                    // 'label' => __('Profile Likes', 'peepso-core'),
                    'label-desc' => __('Hide my profile from all user listings', 'peepso-core'),
                    'value' => $this->user->is_hide_profile_from_user_listing(),
                    'type' => 'yesno_switch',
                    'loading' => TRUE,
                );

            $group_other_fields = array();
            $group_other_fields['peepso_hide_online_status'] = array(
                //'label' => __('Don\'t show my online status', 'peepso-core'),
                'label-desc' => __('Don\'t show my online status', 'peepso-core'),
                'value' => $this->user->get_hide_online_status(),
                'type' => 'yesno_switch',
                'validation' => array(/*'required'*/),
                'loading' => TRUE,
            );

            $options_gmt = apply_filters('peepso_options_gmt', $options_gmt);

            $group_other_fields['peepso_gmt_offset'] = array(
                'label' => __('My timezone', 'peepso-core'),
                'descript' => __('Display all activity date and time in your own timezone', 'peepso-core'),
                'value' => PeepSoUser::get_gmt_offset($this->user->get_id()),
                'type' => 'select',
                'options' => $options_gmt,
                'validation' => array(/*'required'*/),
                'loading' => TRUE,
            );

            if (0 == PeepSo::get_option('site_profile_posts_override', 1)) {
                unset($group_profile_fields['peepso_profile_post_acc']);
            }

            $pref = array(
                'group_profile' => array(
                    'title' => __('Profile', 'peepso-core'),
                    'items' => $group_profile_fields,
                ),
                'group_other' => array(
                    'title' => __('Other', 'peepso-core'),
                    'items' => $group_other_fields,
                ),
            );
            $pref_plugins = apply_filters('peepso_profile_preferences', $pref);
            if (is_array($pref_plugins))
                $pref = array_merge($pref, $pref_plugins);
        } else {
            $pref = apply_filters('peepso_profile_preferences_'.$override, $pref);
        }

        return ($pref);
    }

    public function get_available_preferences($override = FALSE)
    {
        $prefs = array();
        $pref_definition = $this->get_preferences_definition($override);
        if(is_array($pref_definition) && count((array) $pref_definition) > 0) {
            foreach ($pref_definition as $key => $value) {
                if (!isset($value['items']))
                    continue;
                $items = array();
                foreach ($value['items'] as $key_fields => $value_fields) {
                    $field_name = $key_fields;
                    $items[$key_fields] = $value_fields;
                }
                if ($items) {
                    $value['items'] = $items;
                    $prefs[$key] = $value;
                }
            }
        }
        return ($prefs);
    }

    public function get_preferences_form_fields($override = FALSE)
    {
        $prefs = $this->get_available_preferences($override);

        $fields = array();
        if (!empty($prefs)) {

            $counter = 0;
            // generate form fields
            foreach ($prefs as $key => $value) {
                // generate section
                $fields[$key] = array(
                    'label' => "{$value['title']}",
                    'descript' => '',
                    'value' => 1,
                    'fields' => array(),
                    'type' => 'title',
                    'section' => 1,
                );

                // title
                if (!isset($value['items']) || empty($value['items']))
                    continue;

                if (count($value['items']) <= 1)
                    $fields[$key]['fields'] = array();

                // generate items
                foreach ($value['items'] as $key_item=> $value_item) {
                    $name_pref = $key_item;
                    $fields[$key_item] = $value_item;
                }
            }
        }
        $fields = apply_filters('peepso_profile_preferences_form_fields', $fields);
        return ($fields);
    }

    public function preferences_form_fields($preferences = TRUE, $notifications= FALSE)
    {
        $fields_preferences = array();
        $fields_notifications = array();

        if($preferences) {
            $override = FALSE;
            if(is_string($preferences)) {
                $override = $preferences;
            }

            $fields_preferences = $this->get_preferences_form_fields($override);
        }

        if($notifications) {
            $fields_notifications = $this->get_alerts_form_fields();
        }

        $fields = array_merge(
            $fields_preferences,
            $fields_notifications
        );

        $form = array(
            'container' => array(
                'element' => 'div',
                'class' => 'ps-form-row',
            ),
            'fieldcontainer' => array(
                'element' => 'div',
                'class' => 'ps-form-controls',
            ),
            'fields' => $fields,
        );

        //remove_filter('peepso_render_form_field', array(&$this, 'render_custom_form_field'), 10, 2);
        add_filter('peepso_render_form_field', array(&$this, 'render_preferences_form_field'), 10, 2);

        $peepso_form = PeepSoForm::get_instance();
        $peepso_form->render($form);
    }

    public function render_preferences_form_field($field, $name)
    {
        $peepso_form = PeepSoForm::get_instance();

        // $email_preference = get_user_option('peepso_email_intensity');
        // $email_preference = is_numeric($email_preference) ? (int) $email_preference : 100;

        $custom_field = '<div class="ps-preferences__notification">';
        if (isset($field['descript']) && !empty($field['descript'])) {
            $custom_field .= '<label id="' . $name . '" class="ps-form-label"> ' . $field['descript'];
            if (isset($field['loading']) && $field['loading']) {
                $custom_field .= ' <span class="ps-js-loading">' .
                    '<img src="' . PeepSo::get_asset('images/ajax-loader.gif') . '" style="display:none" />' .
                    '<i class="ps-icon-ok" style="color:green;display:none"></i></span>';
            }
            $custom_field .= '</label>';
        }

        $custom_field .= '<div class="ps-preferences__checkbox">';
        foreach ($field['fields'] as $value) {
            $email_hide = false;
            if (isset($email_preference)) {
                $email_hide = 'email' === $value['label'] && $email_preference < 100;
            }

            $custom_field .= '<span data-type="' . esc_attr($value['label']) . '" style="' . ($email_hide ? 'display:none' : '') . '">';
            if ('checkbox' === $value['type']) {
                if (isset($field['section']))
                    $custom_field .= '
						<div class="ps-checkbox">
							<input type="checkbox" aria-labelledby="' . $name . '" class="input" id="' . esc_attr($value['name']) . '" onclick="ps_alerts.toggle(\'' . esc_attr($value['name']) . '\', this.checked)" >
							<label for="' . esc_attr($value['name']) . '"></label>
						</div>';
                else {
                    $checked = (1 === $value['value'])? 'checked="checked"' : '';
                    $custom_field .= '
						<div class="ps-checkbox">
							<input type="checkbox" aria-labelledby="' . $name . ' ' . esc_attr($value['label']) . '" id="' . esc_attr($value['name']) . '" name="' . esc_attr($value['name']) . '" value="1" ' . $checked . ' class="input ' . esc_attr($value['group_key']) . '" />
							<label for="' . esc_attr($value['name']) . '"></label>
						</div>';
                }
            } else if('label' === $value['type'])
                $custom_field .= "<div id='".$value['id']."'>".$value['label']."</div>";
            $custom_field .= '</span>';
        }
        $custom_field .= '</div>';	// .ps-preferences__checkbox
        $custom_field .= '</div>';	// .ps-preferences__notification
        return ($custom_field);
    }


}

// EOF
