<?php

    class PeepSoStats {

        private $optin_stats = 0;

        private static $_instance = NULL;

        private $trans = 'peepso_stats_last_run';
        private $trans_expire = 3600;

        private $debug = FALSE;

        private function __construct()
        {
            $this->debug = isset($_GET['debug']);

            $this->optin_stats = PeepSo::get_option('optin_stats', 0);
            if(!$this->optin_stats) {
                return FALSE;
            }

            $this->run();
        }

        public static function get_instance()
        {
            if (NULL === self::$_instance)
                self::$_instance = new self();
            return (self::$_instance);
        }

        private function run() {

            if($this->debug) {
                delete_transient($this->trans);
            }

            if(!PeepSo::is_api_request() && !strlen(get_transient($this->trans))) {

                global $wp_version;

                $stats = array(

                    'url'               => trim(strtolower(str_ireplace(array('http://','https://','www.'),'',get_option( 'siteurl' ))),'/'),

                    'ver_peepso'        => PeepSo::PLUGIN_VERSION,
                    'ver_wp'           =>  $wp_version,
                    'ver_php'           => PHP_VERSION,
                    'ver_locale'        =>  get_locale(),

                    'prd_gecko'         => (int) class_exists('GeckoConfigSettings'),

                    'prd_photos'        => (int) class_exists('PeepSoSharePhotos'),
                    'prd_media'         => (int) class_exists('PeepSoVideos'),
                    'prd_chat'          => (int) class_exists('PeepSoMessagesPlugin'),
                    'prd_groups'        => (int) class_exists('PeepSoGroupsPlugin'),
                    'prd_friends'       => (int) class_exists('PeepSoFriendsPlugin'),
                    'prd_polls'         => (int) class_exists('PeepSoPolls'),
                    'prd_userlimits'    => (int) class_exists('peepsolimitusers'),
                    'prd_vip'           => (int) class_exists('PeepSoVIPPlugin'),
                    'prd_giphy'         => (int) class_exists('PeepSoGiphyPlugin'),
                    'prd_emaildigest'   => (int) class_exists('PeepSoEmailDigest'),
                    'prd_automator'     => (int) class_exists('PeepSoAutoFriendsPlugin'),
                    'prd_wordfilter'    => (int) class_exists('PeepSoWordFilterPlugin'),
                    'prd_advancedads'    => (int) class_exists('PeepSoAdvancedAdsPlugin'),
                    'prd_badgeos'       => (int) class_exists('BadgeOS_PeepSo'),
                    'prd_learndash'     => (int) class_exists('PeepSoLearnDash'),
                    'prd_pmp'           => (int) class_exists('PeepSoPMP'),
                    'prd_mycred'        => (int) class_exists('PeepSoMyCreds'),
                    'prd_wpadverts'     => (int) class_exists('PeepSoWPAdverts'),
                    'prd_woocommerce'   => (int) class_exists('WBPWI_PeepSo_Woo_Integration'),
                );

                foreach($stats as $k=>$v) {
                    $stats[$k] = urlencode($v);
                }

                $url = 'https://stats.peep.so?action=insert';

                foreach($stats as $k => $v) {
                    $url .= "&$k=$v";
                }

                $request = wp_safe_remote_get($url);

                if($this->debug) {
                    echo "<pre>";
                    var_dump($stats);
                    echo "<hr>";
                    echo $url;
                    echo "<hr>";
                    var_dump($request);
                    die();
                }

                set_transient($this->trans, $url, $this->trans_expire);
            }
        }


    }