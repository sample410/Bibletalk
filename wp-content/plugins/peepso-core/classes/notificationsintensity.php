<?php

class PeepSoNotificationsIntensity {

    /**
     * Each intensity level key is the amount of minutes that needs to pass between e-mails
     * @return array
     */
    public static function email_notifications_intensity_levels() {
        $levels = array(
            0 => array(
                'label' => __('Real time', 'peepso-core'),
                'schedule' => 'realtime',
                'desc'=>__('All enabled e-mail notifications will be sent out immediately','peepso-core'),
            ),

            60 => array(
                'label' => __('Once an hour', 'peepso-core'),
                'desc'=>sprintf(__('You will receive a summary of unread on-site notifications approximately %s','peepso-core'), __('every hour','peepso-core')),
            ),

            1440 => array(
                'label' => __('Once in 24 hours', 'peepso-core'),
                'desc'=>sprintf(__('You will receive a summary of unread on-site notifications approximately %s','peepso-core'), __('once a day','peepso-core')),
            ),

            10080 => array(
                'label' => __('Once in 7 days', 'peepso-core'),
                'desc'=>sprintf(__('You will receive a summary of unread on-site notifications approximately %s','peepso-core'), __('once a week','peepso-core')),
            ),

            999999 => array(
                'label' => __('Never', 'peepso-core'),
                'schedule' => 'disabled',
                'desc'=>sprintf(__('You will not receive any e-mail notifications','peepso-core'), __('hour','peepso-core')),
            ),
        );

        return $levels;
    }

    public static function user_email_notifications_intensity($user_id = 0) {

        if(!$user_id) {
            $user_id = get_current_user_id();
        }

        if(!$user_id) { return FALSE; }

        $levels = PeepSoNotificationsIntensity::email_notifications_intensity_levels();

        $email_preference = get_user_option('peepso_email_intensity');

        if(!is_numeric($email_preference)) { $email_preference = 0; }

        // if level is missing, pick the next one in queue
        if(!isset($levels[$email_preference])) {
            for($i=$email_preference;$i<=999999;$i++) {
                if(isset($levels[$i])) {
                    $email_preference = $i;
                    break;
                }
            }
        }

        return $email_preference;
    }


}