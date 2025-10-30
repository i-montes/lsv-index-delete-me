<?php
/**
 * Plugin Name: My Custom Fixes
 * Plugin URI: https://tusitio.com/
 * Description: Plugin minimalista para colocar snippets o correcciones personalizadas en WordPress.
 * Version: 1.1.0
 * Author: imontes
 * Author URI: https://tusitio.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-custom-fixes
 */

defined('ABSPATH') || exit;

const LSV_CB_HANDLE = 'lsv-critical-boost';

function lsv_cb_enqueue_assets() {
        if (is_admin()) {
                return;
        }

        $plugin_url = plugin_dir_url(__FILE__);
        $plugin_path = plugin_dir_path(__FILE__);

        wp_enqueue_style(
                LSV_CB_HANDLE . '-critical',
                $plugin_url . 'assets/css/critical.css',
                [],
                filemtime($plugin_path . 'assets/css/critical.css')
        );

        wp_enqueue_script(
                LSV_CB_HANDLE . '-skeleton',
                $plugin_url . 'assets/js/skeleton.js',
                [],
                filemtime($plugin_path . 'assets/js/skeleton.js'),
                true
        );

        if (function_exists('wp_script_add_data')) {
                wp_script_add_data(LSV_CB_HANDLE . '-skeleton', 'strategy', 'defer');
        }
}
add_action('wp_enqueue_scripts', 'lsv_cb_enqueue_assets', 5);

function lsv_cb_add_skeleton_attribute($html) {
        if (!is_string($html) || $html === '') {
                return $html;
        }

        if (strpos($html, 'data-skeleton') !== false) {
                return $html;
        }

        $updated = preg_replace('/<img\b(?![^>]*data-skeleton)([^>]*)>/i', '<img data-skeleton="true"$1>', $html, 1);

        return $updated === null ? $html : $updated;
}
add_filter('get_custom_logo', 'lsv_cb_add_skeleton_attribute');

function lsv_cb_thumbnail_skeleton($html, $post_id, $post_thumbnail_id, $size, $attr) {
        return lsv_cb_add_skeleton_attribute($html);
}
add_filter('post_thumbnail_html', 'lsv_cb_thumbnail_skeleton', 10, 5);

function lsv_cb_content_skeleton($content) {
        if (!is_string($content) || strpos($content, '<img') === false) {
                return $content;
        }

        $updated = preg_replace('/<img\b(?![^>]*data-skeleton)([^>]*)>/i', '<img data-skeleton="true"$1>', $content, 1);

        return $updated === null ? $content : $updated;
}
add_filter('the_content', 'lsv_cb_content_skeleton', 9);

