<?php

class PeepSo3_Compatibility_Yoast_SEO {
    private static $instance;

    public static function get_instance() {
        return isset(self::$instance) ? self::$instance : self::$instance = new self;
    }

    private function __construct()
    {
        // Unhook Yoast SEO Open Graph on pages with PeepSo shortcodes
        add_action('wp_head', function() {
            global $post;
            if($post instanceof WP_Post && stristr($post->post_content, '[peepso_')) {
                add_filter( 'wpseo_opengraph_url' , '__return_false' );
                add_filter( 'wpseo_opengraph_desc', '__return_false' );
                add_filter( 'wpseo_opengraph_title', '__return_false' );
                add_filter( 'wpseo_opengraph_type', '__return_false' );
                add_filter( 'wpseo_opengraph_site_name', '__return_false' );
                add_filter( 'wpseo_opengraph_image' , '__return_false' );
                add_filter( 'wpseo_og_locale' , '__return_false' );
                add_filter( 'wpseo_opengraph_author_facebook' , '__return_false' );
            }
        },1);
    }
}

if(!defined('PEEPSO_DISABLE_COMPATIBILITY_YOAST_SEO')) {
   PeepSo3_Compatibility_Yoast_SEO::get_instance();
}