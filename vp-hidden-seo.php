<?php
/*
Plugin Name:SeoHide
Plugin URI: http://alkoweb.ru/seohide-plugin-wodpress/
Description: Plugin for hiding external links
Version: 1.4.0
Requires PHP: 5.4
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
        protected $version = '2.0.0';
        protected $settings;
        protected $text_domain;
        protected $option_prefix;
        protected $site_host;
        protected $priority;
        public $allow_hosts = [];
        public $disallow_hosts = [];

        public function __construct()
        {

            $this->site_host = parse_url(site_url('/'), PHP_URL_HOST);
            $this->allow_hosts = $this->options_hosts_extractor(get_option('seohide_white_list', false));
            $this->disallow_hosts = $this->options_hosts_extractor(get_option('seohide_black_list', false));

            $this->settings = [
                'basename' => plugin_basename(__FILE__),
                'path' => plugin_dir_path(__FILE__),
                'url' => plugin_dir_url(__FILE__),
            ];

            $this->priority = apply_filters('vp_seo_hide_add_priority', 40);

            add_action('plugins_loaded', [$this, 'text_domine']);
            $this->set_site_host();
            $this->set_text_domain('vp-seo-hide');
            $this->set_option_prefix('seohide_');
            $this->load_dependencies();

            add_action('wp_enqueue_scripts', [$this, 'load_scripts']);

            add_filter('the_content', [$this, 'search_links_in_content'], $this->priority);
            add_filter('get_comment_author_link', [$this, 'hide_comment_author_link_target'], $this->priority, 3);

            $this->add_experimental_functions();
            add_filter('vp_seo_hide_check_link', [$this, 'exclude_links'], 10, 2);

        }

        private function options_hosts_extractor($array)
        {
            if (!empty($array)) {

                $array = explode(PHP_EOL, $array);
                $array = array_diff($array, ['']);
                $array = array_map(function ($val) {
                    $val = trim($val);
                    return $val;
                }, $array);

                return $array;
            }

            return [];
        }

        public function search_links_in_content($content)
        {
            return preg_replace_callback('/<a (.+?)>/i', [$this, 'links_render_cb'], $content);
        }

        public function exclude_links($val, $url)
        {

            if (!empty($this->allow_hosts) || !empty($this->disallow_hosts)) {

                if (!empty($this->allow_hosts)) {
                    if (in_array($url, $this->allow_hosts)) {
                        return true;
                    }
                }

                if (!empty($this->disallow_hosts)) {
                    if (in_array($url, $this->disallow_hosts)) {
                        return false;
                    }
                }

            }

            return $val;
        }

        public function punycode_encode($str)
        {
            $idn = new idna_convert(['idn_version' => 2008]);
            return $idn->encode($str);
        }

        public function cyrillic_detect($str)
        {
            $res = preg_match("/[а-яА-ЯёЁ]/i", $str);
            if ($res) {
                return true;
            }
            return false;
        }

        public function set_site_host()
        {
            $host = network_home_url();
            $host = parse_url($host);
            $host = $host['host'];
            $host = apply_filters('vp_seo_hide_site_host', $host);
            $this->site_host = $host;
        }


        public function add_experimental_functions()
        {
            $comment = get_option('seohide_comment');
            $comment = $this->option_checker($comment);
            if ($comment) {
                add_filter('comment_text', [$this, 'search_links']);
            }

            $comment_site_field = get_option($this->option_prefix . 'comment_site_field', false);
            $comment_site_field = $this->option_checker($comment_site_field);
            if ($comment_site_field) {
                add_filter('get_comment_author_link', [$this, 'hide_comment_author_link'], 10, 1);
            }

            $external_blank = get_option($this->option_prefix . 'external_blank', false);
            $external_blank = $this->option_checker($external_blank);

            if ($external_blank) {
                add_filter('vp_seo_hide_pre_show', [$this, 'links_render_cb_help_blank'], 10, 1);
            }

        }

        public function hide_comment_author_link($return)
        {
            $return = $this->search_links($return);
            return $return;
        }

        public function hide_comment_author_link_target($return, $author, $comment_ID)
        {
            $pos = strpos($return, '<a ');
            if (is_int($pos)) {
                $return = str_replace('href=', 'target="_blank" href=', $return);
            }
            return $return;
        }

        public function option_checker($opt)
        {

            if ($opt == false || $opt == 0 || $opt == '0' || $opt == '') {
                return false;
            } elseif ($opt == true || $opt == 1 || $opt == '1') {
                return true;
            }

            return true;
        }

        public function set_text_domain($var)
        {
            $this->text_domain = $var;
        }

        public function set_option_prefix($var)
        {
            $this->option_prefix = strval($var);
        }

        public function load_dependencies()
        {
            require_once plugin_dir_path(__FILE__) . 'includes/class-Seohide-Add-Settings-Page.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-Seohide-Meta-Box.php';

            if (version_compare(phpversion(), '5.0.0', '>=')) {
                if (!class_exists('idna_convert')) {
                    require_once plugin_dir_path(__FILE__) . 'includes/idna_convert.class.php';
                }
            }

            $settings_page = new Seohide_Add_Settings_Page($this->text_domain, $this->option_prefix);
//            $settings_metabox = new Seohide_Metabox($this->text_domain, $this->version, $this->settings['url']);
        }

        function text_domine()
        {
            load_textdomain(
                $this->text_domain,
                plugin_dir_path(__FILE__) . 'lang/vp-seo-hide-' . get_locale() . '.mo'
            );
        }

        public function load_scripts()
        {
            wp_register_script(
                'sh',
                $this->settings['url'] . 'public/js/sh.min.js',
                ['jquery'],
                $this->version,
                false
            );

            if (!is_admin()) {
                wp_enqueue_script('sh');
            }
        }

        /**
         * @param $content string
         *
         * @return mixed
         */
        public function search_links($content, $target = false)
        {
            return preg_replace_callback('/<a (.+?)>/i', [$this, 'links_render_cb'], $content);
        }

        public function links_render_cb($input)
        {
            preg_match('~data-seohide=[\'|"]?(false|true)[\'|"]?~i', $input[0], $items);

            if (!empty($items) && isset($items[1]) && 'false' === $items[1]) {
                return $input[0];
            }

            if (strpos($input[0], $this->site_host) !== false && 'true' !== $items[1]) {
                return $input[0];
            } else {
                preg_match('~\s(?:href)=(?:[\"\'])?(.*?)(?:[\"\'])?(?:[\s\>])~i', $input[0], $matches);

                if ($this->cyrillic_detect($matches[1])) {
                    $punycode_url = $this->punycode_encode($matches[1]);

                    if (apply_filters('vp_seo_hide_check_link', false, $punycode_url)) {
                        return $input[0];
                    }

                    $input[0] = str_replace($matches[1], $this->method_hash($punycode_url), $input[0]);

                } else {

                    if (apply_filters('vp_seo_hide_check_link', false, $matches[1])) {
                        return $input[0];
                    }

                    $input[0] = str_replace($matches[1], $this->method_hash($matches[1]), $input[0]);
                }

                $input[0] = str_replace('href=', 'href="#" data-sh=', $input[0]);


                return apply_filters('vp_seo_hide_pre_show', $input[0]);
            }
        }

        public function links_render_cb_help_blank($str)
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
        public function method_hash($var)
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

    add_action('plugins_loaded', 'vp_seo_hide');

    function vp_seo_hide_in_text($text)
    {
        global $vp_seo_hide;
        return $vp_seo_hide->search_links($text);
    }

}
