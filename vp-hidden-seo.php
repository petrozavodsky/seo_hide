<?php
/*
Plugin Name:SeoHide
Plugin URI: http://alkoweb.ru/seohide-plugin-wodpress/
Description: Plugin for hiding external links
Version: 1.3.5
Author: Petrozavodsky Vladimir
Author URI: http://alkoweb.ru/
Text Domain: vp-seo-hide
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('vp_seo_hide')) {
    class vp_seo_hide
    {
        protected $version='1.3.5';
        protected $settings;
        protected $text_domain;
        protected $option_prefix;
        protected $site_host;
        protected $priority;

        function __construct()
        {
            $this->priority = apply_filters('vp_seo_hide_add_priority',10000);
            add_action('plugins_loaded', array($this, 'text_domine'));
            $this->set_site_host();
            $this->set_text_domain('vp-seo-hide');
            $this->set_option_prefix('seohide_');
            $this->load_dependencies();
            $this->settings = array(
                'basename' => plugin_basename(__FILE__),
                'path' => plugin_dir_path(__FILE__),
                'url' => plugin_dir_url(__FILE__),
            );
            add_action('wp_enqueue_scripts', array(&$this, 'load_scripts'));
            add_filter('the_content', array(&$this, 'search_links'),$this->priority);
            add_filter('get_comment_author_link', array($this, 'hide_comment_author_link_target'), $this->priority, 3);
            $this->add_experimental_functions();
        }

        function punycode_encode($str)
        {
            $idn = new idna_convert(array('idn_version' => 2008));
            return $idn->encode($str);
        }

        function cyrillic_detect($str)
        {
            $res = preg_match("/[а-яА-ЯёЁ]/i", $str);
            if ($res) {
                return true;
            }
            return false;
        }

        function set_site_host()
        {
            $host = network_home_url();
            $host = parse_url($host);
            $host = $host['host'];
            $host = apply_filters('vp_seo_hide_site_host', $host);
            $this->site_host = $host;
        }


        function add_experimental_functions()
        {
            $comment = get_option('seohide_comment');
            $comment = $this->option_checker($comment);
            if ($comment) {
                add_filter('comment_text', array($this, 'search_links'));
            }

            $comment_site_field = get_option($this->option_prefix . 'comment_site_field', false);
            $comment_site_field = $this->option_checker($comment_site_field);
            if ($comment_site_field) {
                add_filter('get_comment_author_link', array($this, 'hide_comment_author_link'), 10, 1);
            }


            $external_blank = get_option($this->option_prefix . 'external_blank', false);
            $external_blank = $this->option_checker($external_blank);
            if ($external_blank) {
                add_filter('vp_seo_hide_pre_show', array($this, 'links_render_cb_help_blank'), 10, 1);
            }

        }

        function hide_comment_author_link($return)
        {
            $return = $this->search_links($return);
            return $return;
        }

        function hide_comment_author_link_target($return, $author, $comment_ID)
        {
            $pos = strpos($return, '<a ');
            if (is_int($pos)) {
                $return = str_replace('href=', 'target="_blank" href=', $return);
            }
            return $return;
        }

        function option_checker($opt)
        {

            if ($opt == false || $opt == 0 || $opt == '0' || $opt == '') {
                return false;
            } elseif ($opt == true || $opt == 1 || $opt == '1') {
                return true;
            }

            return true;

        }

        function set_text_domain($var)
        {
            $this->text_domain = $var;
        }

        function set_option_prefix($var)
        {
            $this->option_prefix = strval($var);
        }

        function load_dependencies()
        {
            require_once plugin_dir_path(__FILE__) . 'includes/class-Seohide-Add-Settings-Page.php';
            require_once plugin_dir_path(__FILE__) . 'includes/idna_convert.class.php';
            $settings_page = new Seohide_Add_Settings_Page($this->text_domain, $this->option_prefix);
        }

        function text_domine()
        {
            load_textdomain($this->text_domain, plugin_dir_path(__FILE__) . 'lang/vp-seo-hide-' . get_locale() . '.mo');
        }

        public function load_scripts()
        {
            wp_register_script('sh', $this->settings['url'] . 'js/sh.js', array('jquery'), $this->version, false);
            if (!is_admin()) {
                wp_enqueue_script('sh');
            }
        }

        /**
         * @param $content string
         *
         * @return mixed
         */
        function search_links($content, $target = false)
        {
            $tmp = preg_replace_callback('/<a (.+?)>/i', array(&$this, 'links_render_cb'), $content);
            return $tmp;
        }

        function links_render_cb($input)
        {

            if (strpos($input[0], $this->site_host) !== false) {
                return $input[0];
            } else {
                preg_match('~\s(?:href)=(?:[\"\'])?(.*?)(?:[\"\'])?(?:[\s\>])~i', $input[0], $matches);

                if (preg_match("/^(#[a-z0-9-]{1,128}|#)/i", $matches[1])) {
                    return $input[0];
                }
                if ($this->cyrillic_detect($matches[1])) {
                    $punycode_url = $this->punycode_encode($matches[1]);
                    $input[0] = str_replace($matches[1], $this->method_hash($punycode_url), $input[0]);
                } else {
                    $input[0] = str_replace($matches[1], $this->method_hash($matches[1]), $input[0]);
                }
                $input[0] = str_replace('href=', 'href="#" data-sh=', $input[0]);
                return apply_filters('vp_seo_hide_pre_show', $input[0]);
            }
        }

        function links_render_cb_help_blank($str)
        {
            preg_match('~<a.*?target=["\']([^"]+)["\'].?>~s', $str, $matches);

            if (count($matches) == 0) {
                $str = str_replace('data-sh', 'target="_blank" data-sh', $str);
            }

            return $str;
        }


        /**
         * @param $var string
         *
         * @return string
         */
        function method_hash($var)
        {
            return base64_encode($var);
        }

    }


    function vp_seo_hide()
    {
        global $vp_seo_hide;
        if (!isset($vp_seo_hide)) {
            $vp_seo_hide = new vp_seo_hide();
        }

        return $vp_seo_hide;
    }

    vp_seo_hide();

    function vp_seo_hide_in_text($text)
    {
        global $vp_seo_hide;
        return $vp_seo_hide->search_links($text);
    }

}
