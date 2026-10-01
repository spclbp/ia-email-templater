<?php

if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
    }
}

if (!function_exists('add_meta_box')) {
    function add_meta_box($id, $title, $callback, $screen, $context = 'advanced', $priority = 'default', $callback_args = null)
    {
    }
}

if (!function_exists('register_rest_field')) {
    function register_rest_field($object_type, $attribute, $args = array())
    {
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false)
    {
        return $single ? '' : array();
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value)
    {
    }
}

if (!function_exists('delete_post_meta')) {
    function delete_post_meta($post_id, $meta_key)
    {
    }
}

if (!function_exists('wp_nonce_field')) {
    function wp_nonce_field($action = -1, $name = '_wpnonce', $referer = true, $echo = true)
    {
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1)
    {
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return true;
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text)
    {
        return $text;
    }
}

if (!function_exists('add_menu_page')) {
    function add_menu_page(
        $page_title,
        $menu_title,
        $capability,
        $menu_slug,
        $callback = '',
        $icon_url = '',
        $position = null
    ) {
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file)
    {
        return __DIR__ . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file)
    {
        return '';
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(
        $handle,
        $src = '',
        $deps = array(),
        $ver = false,
        $media = 'all'
    ) {
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(
        $handle,
        $src = '',
        $deps = array(),
        $ver = false,
        $args = array()
    ) {
    }
}

if (!function_exists('wp_enqueue_media')) {
    function wp_enqueue_media()
    {
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback)
    {
    }
}

if (!function_exists('wp_editor')) {
    function wp_editor($content, $editor_id, $settings = array())
    {
    }
}

if (!function_exists('wp_get_attachment_image_url')) {
    function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail', $icon = false)
    {
        return '';
    }
}

if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url($attachment_id = 0)
    {
        return '';
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return $text;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return $text;
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return $url;
    }
}

if (!defined('DB_NAME')) {
    define('DB_NAME', '');
}

if (!function_exists('get_the_title')) {
    function get_the_title($post = 0)
    {
        return '';
    }
}

if (!function_exists('tribe_get_event')) {
    function tribe_get_event($event = null, $output = OBJECT, $filter = 'raw', $parent = null)
    {
        return null;
    }
}

if (!function_exists('tribe_get_start_date')) {
    function tribe_get_start_date($event = null, $display_time = true, $date_format = '', $timezone = null)
    {
        return '';
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct($code = '', $message = '', $data = '')
        {
        }

        public function get_error_message($code = '')
        {
            return '';
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing)
    {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('register_post_type')) {
    function register_post_type($post_type, $args = array())
    {
    }
}

if (!function_exists('flush_rewrite_rules')) {
    function flush_rewrite_rules($hard = true)
    {
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($content, $allowed_html, $allowed_protocols = array())
    {
        return $content;
    }
}

if (!function_exists('wpautop')) {
    function wpautop($text, $br = true)
    {
        return $text;
    }
}

if (!function_exists('wp_slash')) {
    function wp_slash($value)
    {
        return $value;
    }
}

if (!function_exists('wp_insert_post')) {
    function wp_insert_post($postarr, $wp_error = false, $fire_after_hooks = true)
    {
        return 0;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default_value = false)
    {
        return $default_value;
    }
}

if (!function_exists('date_i18n')) {
    function date_i18n($format, $timestamp_with_offset = false, $gmt = false)
    {
        return '';
    }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link($post = 0, $context = 'display')
    {
        return '';
    }
}
