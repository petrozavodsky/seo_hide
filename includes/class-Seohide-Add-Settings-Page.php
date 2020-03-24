<?php

class Seohide_Add_Settings_Page
{

    private $text_domain;
    private $settings_section;
    private $settings_page;
    private $option_prefix;

    public function __construct($text_domain, $option_prefix)
    {
        $this->text_domain = $text_domain;
        $this->settings_section = 'seohide_settings';
        $this->settings_page = 'reading';
        $this->option_prefix = strval($option_prefix);
        add_action('admin_init', [$this, 'register_settings_api_init']);
    }

    public function register_settings_api_init()
    {

        // section
        add_settings_section(
            $this->settings_section,
            __('Seohide settings', 'vp-seo-hide'),
            [$this, 'label_text'],
            $this->settings_page
        );

        /*
        //field
        add_settings_field(
            $this->option_prefix . 'comment',
            __('Hide links comment body', 'vp-seo-hide'),
            function () {
                echo '<input name="' . $this->option_prefix . 'comment' . '" type="checkbox" '
                    . checked(1, get_option($this->option_prefix . 'comment'), false) .
                    ' value="1" class="code" />';
            },
            $this->settings_page,
            $this->settings_section
        );

        register_setting(
            $this->settings_page,
            $this->option_prefix . 'comment',
            ['sanitize_callback' => [$this, 'sanitize_callback_checkbox']]
        );


        //field
        add_settings_field(
            $this->option_prefix . 'comment_site_field',
            __('Hide link comment author', 'vp-seo-hide'),
            function () {
                echo '<input name="' . $this->option_prefix . 'comment_site_field' . '" type="checkbox"	' .
                    checked(1, get_option($this->option_prefix . 'comment_site_field'), false) .
                    '	value="1" class="code" 	/>';
            },
            $this->settings_page,
            $this->settings_section
        );

        register_setting(
            $this->settings_page,
            $this->option_prefix . 'comment_site_field',
            ['sanitize_callback' => [$this, 'sanitize_callback_checkbox']]
        );
        */

        //field
        add_settings_field(
            $this->option_prefix . 'external_blank',
            __('Open external links in new window', 'vp-seo-hide'),
            function () {
                echo '<input name="' . $this->option_prefix . 'external_blank' . '" type="checkbox" '
                    . checked(1, get_option($this->option_prefix . 'external_blank'), false) .
                    ' value="1" class="code" />';
            },
            $this->settings_page,
            $this->settings_section
        );

        register_setting(
            $this->settings_page,
            $this->option_prefix . 'external_blank',
            ['sanitize_callback' => [$this, 'sanitize_callback_checkbox']]
        );

        //field
        add_settings_field(
            $this->option_prefix . 'white_list',
            __('White list', 'vp-seo-hide'),
            function () {
                $value = esc_textarea(get_option($this->option_prefix . 'white_list', ''));
                echo "<textarea name='{$this->option_prefix}white_list'>{$value}</textarea>";
            },
            $this->settings_page,
            $this->settings_section
        );

        register_setting(
            $this->settings_page,
            $this->option_prefix . 'white_list',
            ['sanitize_callback' => 'sanitize_textarea_field']
        );

        //field
        add_settings_field(
            $this->option_prefix . 'black_list',
            __('Black list', 'vp-seo-hide'),
            function () {
                $value = esc_textarea(get_option($this->option_prefix . 'black_list', ''));
                echo "<textarea name='{$this->option_prefix}black_list'>{$value}</textarea>";
            },
            $this->settings_page,
            $this->settings_section
        );

        register_setting(
            $this->settings_page,
            $this->option_prefix . 'black_list',
            ['sanitize_callback' => 'sanitize_textarea_field']
        );

    }


    public function label_text()
    {
        echo __('<p>Settings appear in different parts of the reference site</p>', 'vp-seo-hide');
    }

    public function sanitize_callback_checkbox($var)
    {
        if ($var == '') {
            $var = 0;
        }
        return absint($var);
    }

}
