<?php

class PeepSoConfigSectionNavigation extends PeepSoConfigSectionAbstract
{
	// Builds the groups array
	public function register_config_groups()
	{
        $this->set_context('full');
        $this->navigation();
        $this->login_logout();
	}

    private function navigation() {

        delete_post_meta_by_key('peepso_shortcode');

        $shortcodes = PeepSo::get_instance()->all_shortcodes();

        foreach($shortcodes as $sc => $method) {

            $options=array();
            $description='';

            if(is_callable($method)) {
                $method=explode('::', $method);
                $method = $method[0].'::description';
            } else {
                $method .='::description';
            }

            $post_state_method = explode('::',$method);
            $post_state_method = $post_state_method[0].='::post_state';

            if(is_callable($method)) {
                $this->args('descript', call_user_func($method));
            }


            if(is_callable($post_state_method)) {
                $post_state = call_user_func($post_state_method);
            } else {
                $post_state = $sc;
            }

            $page = str_ireplace('peepso_','', $sc);
            $page_key = 'page_'.$page;

            $pages = PeepSo::get_instance()->pages_with_shortcode($sc);

            foreach($pages as $key => $value) {
                $options[$key] = "{$value['label']}";

                if($key == PeepSo::get_option($page_key)) {
                    add_post_meta($value['id'], 'peepso_shortcode', $post_state, 1);
                }
            }

            $error = PeepSo::get_instance()->check_shortcode($sc, $options);
            $error = strip_tags($error,'<b><br>');

            $label = ''
                . str_ireplace(_x('PeepSo', 'Page listing', 'peepso-core'). ' - ','',$post_state)
                . '<a name="' . $sc . '" href="' . PeepSo::get_page($page) . '" target="_blank"> '
                .   '<i  class="fa fa-external-link"></i>'
                . '</a>'
                . '<br/><small style="color:#aaaaaa">[' . $sc . ']</small>';

            if(strlen($error)) {

                $options = array_merge(array($page_key=> __('Error: page not found!','peepso-core')), $options);

                $label = "<span style=color:red>$label<br/><small>$error</small></span>";
            }

            $this->args('options', $options);

            $this->set_field(
                $page_key,
                $label ,
                'select'
            );
        }

        // Build Group
        $this->set_group(
            'profiles',
            __('Primary Navigation', 'peepso-core')
        );
    }

    public function login_logout() {
        // # Redirect Successful Logins
        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $options = array(
            -1 => __('Front Page'),
            0 => __('No redirect', 'peepso-core'),
        );

        $pageredirect = PeepSo::get_option('site_frontpage_redirectlogin');
        $settings = PeepSoConfigSettings::get_instance();
        foreach ($pages as $page) {
            // handling selected old value (activity/profile)
            if($page->post_name == $pageredirect) {
                //$this->args('default', $page->ID);
                // update option to selected ID
                $settings->set_option('site_frontpage_redirectlogin', $page->ID);
            }

            $options[$page->ID] = __('Page:','peepso-core') . ' ' . ($page->post_parent > 0 ? '&nbsp;&nbsp;' : '') . $page->post_title;
        }

        $this->args('options', $options);

        $this->set_field(
            'site_frontpage_redirectlogin',
            __('Log-in redirect', 'peepso-core'),
            'select'
        );


        // # Redirect Logout

        $args = array(
            'sort_order' => 'asc',
            'sort_column' => 'post_title',
            'hierarchical' => 1,
            'exclude' => '',
            'include' => '',
            'meta_key' => '',
            'meta_value' => '',
            'authors' => '',
            'child_of' => 0,
            'parent' => -1,
            'exclude_tree' => '',
            'number' => '',
            'offset' => 0,
            'post_type' => 'page',
            'post_status' => 'publish'
        );
        $pages = get_pages($args);
        $options = array(
            -1 => __('Front Page'),
            0 => __('No redirect', 'peepso-core'),
        );

        $pageredirect = PeepSo::get_option('logout_redirect');
        $settings = PeepSoConfigSettings::get_instance();
        foreach ($pages as $page) {
            // handling selected old value (activity/profile)
            if($page->post_name == $pageredirect) {
                //$this->args('default', $page->ID);
                // update option to selected ID
                $settings->set_option('logout_redirect', $page->ID);
            }

            $options[$page->ID] = __('Page:','peepso-core') . ' ' . ($page->post_parent > 0 ? '&nbsp;&nbsp;' : '') . $page->post_title;
        }

        $this->args('options', $options);

        $this->set_field(
            'logout_redirect',
            __('Log-out redirect', 'peepso-core'),
            'select'
        );

        // Build Group
        $this->set_group(
            'login_logout',
            __('Login & Logout', 'peepso-core')
        );

    }

}
?>
