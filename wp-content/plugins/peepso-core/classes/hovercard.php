<?php

class PeepSoHoverCard extends PeepSoAjaxCallback
{
    public function __construct()
    {
        parent::__construct();
        $this->enqueue_script();
    }

    /**
     * Enqueue needed scripts.
     */
    public function enqueue_script() {
        add_filter('peepso_data', function( $data ) {
            $hovercard = array(
                'template' => PeepSoTemplate::exec_template('general', 'hover-card', NULL, TRUE)
            );
            $data['hovercard'] = $hovercard;
            return $data;
        }, 10, 1 );
    }

    /**
     * Called from PeepSoAjaxHandler
     * Declare methods that don't need auth to run
     *
     * @return array
     */
    public function ajax_auth_exceptions()
    {
        return array('info');
    }

    /**
     * Get information of specific user.
     * 
     * @param  PeepSoAjaxResponse $resp
     */
    public function info(PeepSoAjaxResponse $resp)
    {
        $userid = $this->_input->int('userid', 0);
        $user = PeepSoUser::get_instance($userid);

        $resp->success(TRUE);
        $resp->set('name', $user->get_fullname());
        $resp->set('avatar', $user->get_avatar());
        $resp->set('cover', $user->get_cover(750));
        $resp->set('views', $user->get_view_count());
        $resp->set('link', $user->get_profileurl());

        // Get profile likes count.
        $peepso_like = PeepSoLike::get_instance();
        $likes = $peepso_like->get_like_count($user->get_id(), PeepSo::MODULE_ID);
        $resp->set('likes', $likes);

        // Extra data.
        $extra = apply_filters('peepso_hovercard', array(), $user->get_id());
        foreach ($extra as $key => $value) {
            $resp->set($key, $value);
        }
    }

}

// EOF