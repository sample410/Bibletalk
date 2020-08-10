<?php

class PeepSoAdminConfigLicense extends PeepSoAjaxCallback
{
    /*
     * Builds the required flot data set based on the request
     * @param PeepSoAjaxResponse $resp The response object
     */
    public function check_license(PeepSoAjaxResponse $resp)
    {
        if (!PeepSo::is_admin()) {
            $resp->success(FALSE);
            $resp->error(__('Insufficient permissions.', 'peepso-core'));
            return;
        }
		
        $plugins = $this->_input->value('plugins','',FALSE); // SQL safe, admin only
        $response = array();
        $response_details = array();

        if(count($plugins)) {

            foreach ($plugins as $slug => $name) {

                PeepSoLicense::activate_license($slug, $name);

                $response[$slug] = (int)PeepSoLicense::check_license($name, $slug, TRUE);
                $license = PeepSoLicense::get_license($slug);

                $details = '';

                if(isset($license['expire']) && $license['expire']) {
                    $expires = strtotime($license['expire']);

                    if ($expires > time()) {
                        $color = '#dddddd';
                        $message = sprintf(__('%s remaining', 'peepso-core'), human_time_diff_round_alt($expires));
                    } else {
                        $color = '#ff0000';
                        $message = sprintf(__('Expired on %s', 'peepso-core'), date('d-M-Y', $expires));

                        if(strstr($license['expire'], '1999')) {
                            $message = __('Your license can\'t be checked because of an API request limit.<br/> Please wait a few minutes and try again.<br/>If the problem persists, please contact <a href="https://peepso.com/contact" target="_blank">PeepSo Support</a>.', 'peepso-core');
                        }

                    }

                    $details = sprintf('<span style="font-size:11px;color:%s">%s</span>', $color, $message);
                }

                $response_details[$slug] = $details;
            }
        }

        $resp->set('valid', $response);
        $resp->set('details', $response_details);
        $resp->success(TRUE);
    }
}

// EOF