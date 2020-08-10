<?php

class PeepSoEmbed
{
	public function __construct($url)
	{
		$this->url = $url;
		$this->_data = array();
		$this->_error = FALSE;
		$this->_oembed = defined('PEEPSO_ENABLE_OEMBED') ? PEEPSO_ENABLE_OEMBED : TRUE;
		$this->_oembed_discovery = defined('PEEPSO_ENABLE_OEMBED_DISCOVERY') ? PEEPSO_ENABLE_OEMBED_DISCOVERY : TRUE;
	}

	public function fetch()
	{
        $this->_oembed = apply_filters('peepso_enable_oembed', $this->_oembed, $this->url);
        $this->_oembed_discovery = apply_filters('peepso_enable_oembed_discovery', $this->_oembed_discovery, $this->url);

	    // This method might end up firing several calls
        // The timeout is multiplied by the amount of attempts
        // The time taken to fire everything might cause perceived very slow loads
	    $timeout = 2;

        new PeepSoError($this->url);

        $args = array('timeout' => $timeout, 'limit_response_size' => 512000,'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');

		if ($this->_oembed) {

		    new PeepSoError('_oembed TRUE');

			$oembed = _wp_oembed_get_object($args);

			// Try to find oEmbed endpoint from registered providers first
			// to prevent HTTP request from "WP_oEmbed::discover" function call.
			$html = $oembed->get_html( $this->url, array_merge(array('discover' => FALSE),$args) );
			if ($html) {
				$this->_data['html'] = $this->_filter_html($html);
				return TRUE;
			}
		} else {
            new PeepSoError('_oembed FALSE');
        }

        new PeepSoError('Checking content type');

		// Check content type before attempt to discover oEmbed endpoint from URL content.
		$request = wp_safe_remote_head($this->url, $args);
		$content_type = wp_remote_retrieve_header($request, 'content-type');
		$content_type = preg_replace('#^([^/]+/[^;]+).*$#i', '$1', $content_type);
		$is_text = preg_match('#^(text/|application/(javascript|x-javascript|json))#i', $content_type);
		$is_html = $is_text && preg_match('#^text/html#i', $content_type);

		// Do not fetch non-HTML content.
		if (!$is_html) {
            new PeepSoError('Content is not HTML, we won\'t fetch it');
			// Set thumbnail based on content type.
			if (preg_match('#^(image|audio|video)/#i', $content_type, $matches)) {
				$thumbnail = array('type' => $matches[1], 'value' => $this->url);
			} else {
				$thumbnail = $this->_content_type_to_thumbnail($content_type);
			}

			$this->_data['url'] = $this->url;
			$this->_data['site_name'] = $this->_url_to_sitename($this->url);
			$this->_data['title'] = $this->_url_to_title($this->url);
			$this->_data['description'] = '';
			$this->_data['mime_type'] = $content_type;
			$this->_data['thumbnail'] = $thumbnail;
			return TRUE;
		}

		// Discover oEmbed endpoint from URL content.
		if ($this->_oembed && $this->_oembed_discovery) {

		    new PeepSoError('Trying to discover oEmbed');

			$html = $oembed->get_html( $this->url, array_merge(array('discover' => TRUE), $args) );

			if ($html) {
                new PeepSoError('oEmbed has HTML');
                if(!stristr($html, '<img') && PeepSo::get_option('prefer_img_embeds', 0)) {
                    // admin prefers embeds with img, try falling back
                    new PeepSoError('oEmbed has no image, and we image preference is enabled');
                    $oembed_html = $html; // use this if PeepSoEmbed fails
                } else {
                    new PeepSoError('using oEmbed');
                    $this->_data['html'] = $this->_filter_html($html);
                    return TRUE;
                }
			}
		}

		// Do manual parsing if failed to get the oEmbed code.

        new PeepSoError('Embed fallback - try browser headers');

		$request = wp_safe_remote_get($this->url, $args);
		$html = wp_remote_retrieve_body($request);


		// If failed or can't find og:image, try spoofing Facebook user agent
		if(!$html || !strpos($html, 'og:image')) {

            new PeepSoError('Embed fallback 2 - try Facebook headers');

            $args_fb = array('timeout' => $timeout, 'limit_response_size' => 512000, 'user-agent' => 'facebookexternalhit/1.1');
            $request = wp_safe_remote_get($this->url, $args_fb);
            $html = wp_remote_retrieve_body($request);
        }

		if(!$html) {
            new PeepSoError('Embed fallbacks failed, using oEmbed anyway');
		    // PeepSoEmbed failed, use whatever oembed gave us
            $html = $oembed_html;
        }

		if ($html) {

            new PeepSoError('Embed result positive');

			$data = $this->_parse_html($html);

			// Set generic thumbnail icon if no thumbnail image is found.
			if ($data['thumbnail']) {
				$thumbnail = array('type' => 'image', 'value' => $data['thumbnail']);
			} else {
				$thumbnail = $this->_content_type_to_thumbnail($content_type);
			}

			$this->_data['url'] = $this->url;
			$this->_data['site_name'] = $this->_url_to_sitename($this->url);
			$this->_data['title'] = $data['title'];
			$this->_data['description'] = $data['description'];
			$this->_data['mime_type'] = $content_type;
			$this->_data['thumbnail'] = $thumbnail;
			return TRUE;
		}

        new PeepSoError('Embed result negative');

		return FALSE;
	}

	public function get_data() {
		return $this->_data;
	}

	public function get_error()
	{
		return $this->_error;
	}

	private function _url_to_sitename($url)
	{
		if (preg_match('#^https?://(([^/]+)/)#i', $url, $matches)) {
			return $matches[2];
		}
		return '';
	}

	private function _url_to_title($url)
	{
		if (preg_match('#^https?://(([^/]+)/)*([^/\?\#]+)#i', $url, $matches)) {
			return $matches[3];
		}
		return '';
	}

	private function _content_type_to_thumbnail($content_type)
	{
        return array(
            'type' => 'image',
            'value' => PeepSo::get_asset('images/embeds/no_preview_available.png'),
        );

		$thumbnail = '';

		switch ($content_type) {
			// TODO: Return more thumbnail type based on content type.
			case 'application/pdf':
			case 'application/vnd.ms-excel':
			case 'application/x-sql':
			case 'application/zip':

			default:
				$thumbnail = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M4.01 2L4 22h16V8l-6-6H4.01zM13 9V3.5L18.5 9H13z"/></svg>';
				break;
		}

		return array(
			'type' => 'svg',
			'value' => $thumbnail
		);
	}

	private function _parse_html($html = '')
	{
		$data = array(
			'title' => '',
			'description' => '',
			'thumbnail' => ''
		);

		// Get title information.
		if (preg_match('#<meta[^>]+property=([\'"])og:title\1[^>]*>#is', $html, $matches)) {
			if (preg_match('# content=([\'"])(.*?)\1#is', $matches[0], $matches)) {
				$data['title'] = $matches[2];
			}
		} else if (preg_match('#<title[^>]*>(.*?)</title>#is', $html, $matches)) {
			$data['title'] = $matches[1];
		}

		// Get description information.
		if (preg_match('#<meta[^>]+property=([\'"])og:description\1[^>]*>#is', $html, $matches)) {
			if (preg_match('# content=([\'"])(.*?)\1#is', $matches[0], $matches)) {
				$data['description'] = $matches[2];
			}
		} else if (preg_match('#<meta[^>]+name=([\'"])description\1[^>]*>#is', $html, $matches)) {
			if (preg_match('# content=([\'"])(.*?)\1#is', $matches[0], $matches)) {
				$data['description'] = $matches[2];
			}
		}

		// Get thumbnail information.
		if (preg_match('#<meta[^>]+property=([\'"])og:image\1[^>]*>#is', $html, $matches)) {
			if (preg_match('# content=([\'"])(.*?)\1#is', $matches[0], $matches)) {
				$data['thumbnail'] = $matches[2];
			}
		}

		return $data;
	}

	private function _filter_html($html)
	{
		// Alter Facebook embed code.
		if (preg_match('# class=([\'"])fb-(post|video)\1#is', $html)) {
			$html = preg_replace('#<div[^>]+id=([\'"])fb-root\1[^<]+</div>#is', '', $html);
			$html = preg_replace('#<script[^<]+</script>#is', '', $html);
			// Remove video width setting.
			$html = preg_replace('# data-width=([\'"])\d+%?\1#is', '', $html);
			// Add PeepSo Facebook embed handler.
			$html = $html . '<script>try{peepso.util.fbParseXFBML()}catch(e){}</script>';

		}

		// Alter iframe width to match container width.
		$html = preg_replace(
			'#(<iframe[^>]*) width=([\'"])(\d+%?)\2([^>]*>)#is',
			'$1 width=${2}100%$2 data-original-width=$2$3$2$4',
			$html
		);

		return $html;
	}

	/**
	 * Setup PeepSo embed hooks.
	 *
	 * @static
	 */
	public static function init()
	{
		add_filter('peepso_data', array('PeepSoEmbed', 'filter_data'));
		add_filter('peepso_embed_content', array('PeepSoEmbed', 'filter_embed_content'), 10, 3);
		add_action('wp_ajax_peepso_embed_content', array('PeepSoEmbed', 'ajax_embed_content'));
	}

	/**
	 * Attach embed config to peepsodata variable.
	 *
	 * @static
	 * @param array $data
	 * @return array
	 */
	public static function filter_data($data)
	{
		$data['embed'] = array(
			'enable' => PeepSo::get_option('allow_embed', 1),
			'enable_non_ssl' => PeepSo::get_option('allow_non_ssl_embed', 0),
		);
		return $data;
	}

	/**
	 * Filter to fetch embed content of a URL.
	 *
	 * @static
	 * @param array $data
	 * @param string $url
	 * @param boolean $refresh
	 * @return array
	 */
	public static function filter_embed_content($data, $url, $refresh = FALSE)
	{
	    if(defined('PEEPSO_DEV_MODE_EMBEDS')) {
	        $refresh = true;
        }

		$embed_cache_key = 'peepso_embed_content_' . md5($url);

		// Get embed content from cache if possible.
		if (!$refresh) {
			$embed_content = get_transient($embed_cache_key);
			if ($embed_content) {
				$data['data'] = $embed_content;
				return $data;
			}
		}

		$embed = new PeepSoEmbed($url);
		if ($embed->fetch()) {
			$embed_content = $embed->get_data();
			$data['data'] = $embed_content;
			set_transient($embed_cache_key, $embed_content, HOUR_IN_SECONDS);
		} else {
			$data['error'] = $embed->get_error();
			delete_transient($embed_cache_key);
		}

		return $data;
	}

	/**
	 * Ajax endpoint to fetch embed content of a URL.
	 *
	 * @static
	 */
	public static function ajax_embed_content()
	{
		$result = array();
		$url = (string) $_POST['url'];
		$refresh = (int) $_POST['refresh'] ? TRUE : FALSE;
		$data = apply_filters('peepso_embed_content', array(), trim($url), $refresh);

		if ( isset($data['error']) ) {
			$result['error'] = $data['error'];
		} else {
			$html = PeepSoTemplate::exec_template('activity', 'content-embed', $data['data'], TRUE);
			$result['success'] = TRUE;
			$result['data'] = array('html' => $html);
		}

		echo json_encode($result);
		die();
	}
}

// EOF
