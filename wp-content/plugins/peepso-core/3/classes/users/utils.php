<?php

class PeepSo3_Users_Utils
{

    private static $instance;

    public static function get_instance()
    {
        return isset(self::$instance) ? self::$instance : self::$instance = new self;
    }

    private function __construct()
    {
        add_filter('peepso_filter_display_name_styles', function ($options) {

            return [
                'real_name' => __('Full name', 'peepso-core'),
                'real_name_first' => __('First name', 'peepso-core'),
                'real_name_first_last_initial' => __('First name + last name initial', 'peepso-core'),
                'username' => __('Username', 'peepso-core'),
            ];
        });
    }
}

PeepSo3_Users_Utils::get_instance();