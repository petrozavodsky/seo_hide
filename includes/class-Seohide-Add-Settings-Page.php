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
        add_action('admin_init', array($this, 'register_settings_api_init'));

    }


    public function register_settings_api_init()
    {

        add_settings_section(
            $this->settings_section,
            __('Seohide settings', 'vp-seo-hide'),
            array($this, 'label_text'),
            $this->settings_page
        );

        add_settings_field(
            $this->option_prefix . 'comment',
            __('Hide links comment body', 'vp-seo-hide'),
            array($this, 'comment'),
            $this->settings_page,
            $this->settings_section
        );
        register_setting($this->settings_page, $this->option_prefix . 'comment', array($this, 'sanitize_callback'));

        add_settings_field(
            $this->option_prefix . 'comment_site_field',
            __('Hide link comment author', 'vp-seo-hide'),
            array($this, 'comment_site_field'),
            $this->settings_page,
            $this->settings_section
        );
        register_setting($this->settings_page, $this->option_prefix . 'comment_site_field', array($this, 'sanitize_callback'));


        add_settings_field(
            $this->option_prefix . 'external_blank',
            __('Open external links in new window', 'vp-seo-hide'),
            array($this, 'external_blank'),
            $this->settings_page,
            $this->settings_section
        );
        register_setting($this->settings_page, $this->option_prefix . 'external_blank', array($this, 'sanitize_callback'));

    }


    public function label_text()
    {
        echo __('<p>Settings appear in different parts of the reference site</p>', 'vp-seo-hide');
    }

    public function external_blank()
    {
        echo '<input name="' . $this->option_prefix . 'external_blank' . '" type="checkbox" '
            . checked(1, get_option($this->option_prefix . 'external_blank'), false) .
            ' value="1" class="code" />';
    }

    public function comment()
    {
        echo '<input name="' . $this->option_prefix . 'comment' . '" type="checkbox" '
            . checked(1, get_option($this->option_prefix . 'comment'), false) .
            ' value="1" class="code" />';
    }


    public function comment_site_field()
    {
        echo '<input name="' . $this->option_prefix . 'comment_site_field' . '" type="checkbox"
		' . checked(1, get_option($this->option_prefix . 'comment_site_field'), false) . '
		value="1" class="code" 	/>';
    }

    public function sanitize_callback($var)
    {
        if ($var == '') {
            $var = 0;
        }
        return $var;
    }

}
