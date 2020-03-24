<?php

class Seohide_Metabox
{
    protected $version;
    protected $text_domain;
    protected $post_type;
    protected $meta_box_id;

    public function __construct($text_domain, $version, $base_path_url)
    {

        $this->base_path_url = $base_path_url;
        $this->version = $version;
        $this->text_domain = $text_domain;
        $this->post_type = 'post';
        $this->meta_box_id = 'seo_hide_metabox';
        $this->post_prefix = 'seo_hide';
        add_action('add_meta_boxes', [$this, 'fields'], 1);
        add_action('save_post', [$this, 'update_meta_box'], 0);
        add_action('admin_enqueue_scripts', [$this, 'add_admin_scripts'], 10);
    }

    public function add_admin_scripts()
    {
        wp_register_script(
            'seo-hide-admin-meta-box-js',
            $this->base_path_url . 'public/js/seo-hide-meta-box.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_enqueue_script('seo-hide-admin-meta-box-css');

        wp_register_style(
            'seo-hide-admin-meta-box-css',
            $this->base_path_url . 'public/css/seo-hide-meta-box.css',
            [],
            $this->version
        );

        wp_enqueue_style('seo-hide-admin-meta-box-css');
    }

    public function fields()
    {
        add_meta_box(
            $this->meta_box_id,
            __('Seo hide settings', $this->text_domain),
            [$this, 'get_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );
    }

    public function get_meta_box($post)
    {

        $type_hide = get_post_meta($post->ID, '_seo_hide-type', 1);
        $list = get_post_meta($post->ID, '_seo_hide-type-help-list', 1);

        if (empty($type_hide)) {
            $type_hide = 'default';
        }

        ?>
        <div class="block__seo-hide-meta-box">
            <div class="block__seo-hide-meta-box-item radio">
                <label>
                    <input type="radio"
                           name="<?php echo $this->post_prefix; ?>[_seo_hide-type]"
                        <?php checked($type_hide, 'default'); ?>
                           value="default">
                    <?php _e('Default settings', $this->text_domain); ?>
                </label>

                <label>
                    <input type="radio"
                           name="<?php echo $this->post_prefix; ?>[_seo_hide-type]"
                        <?php checked($type_hide, 'hide_all_links_on_post'); ?>
                           value="hide_all_links_on_post">
                    <?php _e('Hide all links on post', $this->text_domain); ?>
                </label>

                <label>
                    <input type="radio"
                           name="<?php echo $this->post_prefix; ?>[_seo_hide-type]"
                        <?php checked($type_hide, 'show_all_links_on_post'); ?>
                           value="show_all_links_on_post">
                    <?php _e('Show all links on post', $this->text_domain); ?>
                </label>

                <label>
                    <input type="radio"
                           name="<?php echo $this->post_prefix; ?>[_seo_hide-type]"
                        <?php checked($type_hide, 'black_list_pattern'); ?>
                           value="black_list_pattern">
                    <?php _e('Black list pattern', $this->text_domain); ?>
                </label>

                <label>
                    <input type="radio"
                           name="<?php echo $this->post_prefix; ?>[_seo_hide-type]"
                        <?php checked($type_hide, 'white_list_pattern'); ?>
                           value="white_list_pattern">
                    <?php _e('White list pattern', $this->text_domain); ?>
                </label>
            </div>
            <div class="block__seo-hide-meta-box-item list">
                <textarea
                        name="<?php echo $this->post_prefix; ?>[_seo_hide-type-help-list]"><?php echo $list; ?></textarea>
            </div>
        </div>


        <input type="hidden" name="<?php echo $this->post_prefix; ?>_nonce"
               value="<?php echo wp_create_nonce(__FILE__); ?>"/>
        <?php
    }

    public function update_meta_box($post_id)
    {
        if (!wp_verify_nonce($_POST[$this->post_prefix . '_nonce'], __FILE__)) {
            return false;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }

        if (!isset($_POST[$this->post_prefix])) {
            return false;
        }

        $_POST[$this->post_prefix] = array_map('trim', $_POST[$this->post_prefix]);
        foreach ($_POST[$this->post_prefix] as $key => $value) {
            if (empty($value)) {
                delete_post_meta($post_id, $key);
                continue;
            }
            update_post_meta($post_id, $key, $value);
        }
        return $post_id;
    }

}
