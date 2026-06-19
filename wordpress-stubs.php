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