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

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eliminar el HTML con la clase 'author-avatar' en todas las páginas
 * excepto la página principal (home).
 */
add_action('template_redirect', function () {

    // Solo continuar si estamos en el dominio principal
    if (strpos(home_url(), 'lasillavacia.com') === false) {
        return;
    }

    // No hacer nada en la página principal
    if (is_front_page() || is_home()) {
        return;
    }

    // Iniciar el buffer de salida para modificar el HTML antes de enviarlo
    ob_start(function ($buffer) {
        // Expresión regular para eliminar cualquier elemento con class="author-avatar"
        // Incluye div, span, img u otros contenedores HTML
        $pattern = '/<[^>]*class=["\'][^"\']*author-avatar[^"\']*["\'][^>]*>.*?<\/[^>]+>/is';
        $buffer = preg_replace($pattern, '', $buffer);
        return $buffer;
    });
});


/**
 * Optimizar scripts con defer/async (solo en el front-end)
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {

    // No aplicar optimización en el área de administración
    if (is_admin()) {
        return $tag;
    }

    // Scripts que NO deben tener defer/async (necesitan cargarse inmediatamente)
    $exclude_scripts = array(
        'jquery',
        'jquery-core',
        'jquery-migrate'
    );

    if (in_array($handle, $exclude_scripts)) {
        return $tag;
    }

    // Scripts que deben cargarse con DEFER (mantienen orden de ejecución)
    $defer_scripts = array(
        'wp-polyfill',
        'wp-polyfill-inert',
        'regenerator-runtime',
        'wp-block-library',
        'jquery-ui-core',
        'jquery-ui-widget',
        'wp-hooks',
        'wp-i18n',
        'wp-element',
        'wp-components',
        'wp-compose',
        'wp-blocks',
        'wp-editor',
        'wp-data',
        'wp-api-fetch',
        'wp-dom-ready',
        'wp-edit-post',
        'wp-plugins',
        'wp-primitives',
        'underscore',
        'wp-util',
        'wp-url'
    );

    // Scripts que pueden cargarse con ASYNC (no importa el orden)
    $async_scripts = array(
        'google-analytics',
        'ga',
        'gtag',
        'social-logos',
        'sharing-js',
        'comment-reply',
        'stats-js',
        'twitter-widgets',
        'facebook-sdk'
    );

    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }

    if (in_array($handle, $async_scripts)) {
        return str_replace(' src', ' async src', $tag);
    }

    return $tag;
}, 10, 3);

/**
 * Resource hints (preconnect / dns-prefetch)
 */
add_filter('wp_resource_hints', function ($urls, $relation_type) {
    // No aplicar optimización en el área de administración
    if (is_admin()) {
        return $urls;
    }

    // --- PRECONNECT ---
    if ('preconnect' === $relation_type) {
        $urls[] = array(
            'href' => 'https://i0.wp.com',
            'crossorigin' => 'anonymous'
        );
        $urls[] = array(
            'href' => 'https://cd.wp.com',
            'crossorigin' => 'anonymous'
        );
        $urls[] = array(
            'href' => 'https://fonts.googleapis.com',
            'crossorigin' => 'anonymous'
        );

        // ✅ Agregar preconnect dinámico a la imagen destacada
        if (is_single() && has_post_thumbnail()) {
            $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if ($thumb_url) {
                $parsed = wp_parse_url($thumb_url);
                if (!empty($parsed['scheme']) && !empty($parsed['host'])) {
                    $origin = $parsed['scheme'] . '://' . $parsed['host'];
                    $urls[] = array(
                        'href' => esc_url($origin),
                        'crossorigin' => 'anonymous'
                    );
                }
            }
        }
    }

    // --- DNS PREFETCH ---
    if ('dns-prefetch' === $relation_type) {
        $urls[] = '//www.lasillavacia.com';
        $urls[] = '//s0.wp.com';
        $urls[] = '//stats.wp.com';
    }

    return $urls;
}, 10, 2);


/**
 * Carga no bloqueante de todos los CSS (ya que hay CSS crítico inline).
 * Basado en las recomendaciones de Google Web.dev
 */
add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
    // No aplicar en el área de administración
    if (is_admin()) return $html;

    // Convierte todos los estilos en preload no bloqueante
    return sprintf(
        "<link rel='preload' as='style' href='%s' media='%s' onload=\"this.onload=null;this.rel='stylesheet'\">",
        esc_url($href),
        esc_attr($media)
    );
}, 10, 4);


/**
 * Fallback para navegadores sin JavaScript
 * (en caso de que no se ejecute el evento onload del preload)
 */
add_action('wp_head', function () {
    // No aplicar en el admin
    if (is_admin()) return;

    global $wp_styles;
    if (!empty($wp_styles->registered)) {
        foreach ($wp_styles->registered as $handle => $style) {
            if (!empty($style->src)) {
                $href = $style->src;
                echo '<noscript><link rel="stylesheet" href="' . esc_url($href) . '"></noscript>' . "\n";
            }
        }
    }
}, 99);


/**
 * Agrega resource hints para mejorar el rendimiento (preconnect/dns-prefetch)
 */
add_action('wp_head', function () {
    if (is_admin()) return;
    ?>
    <!-- Resource Hints -->
    <link rel="preconnect" href="https://i0.wp.com" crossorigin>
    <link rel="preconnect" href="https://c0.wp.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="//www.lasillavacia.com">
    <link rel="dns-prefetch" href="//s0.wp.com">
    <link rel="dns-prefetch" href="//stats.wp.com">
    <?php
}, 1);


/**
 * Aplica lazy loading a todos los iframes automáticamente.
 */
add_filter('the_content', function ($content) {
    // No aplicar en el área de administración
    if (is_admin()) {
        return $content;
    }

    // Añadir atributo loading="lazy" a todos los iframes que no lo tengan
    $content = preg_replace_callback('/<iframe\s+([^>]+)>/i', function ($matches) {
        $iframe_tag = $matches[0];

        // Si ya tiene loading=lazy o loading=eager, no modificar
        if (strpos($iframe_tag, 'loading=') !== false) {
            return $iframe_tag;
        }

        // Insertar loading="lazy" después de <iframe
        return str_replace('<iframe', '<iframe loading="lazy"', $iframe_tag);
    }, $content);

    return $content;
});


/**
 * Aplica lazy loading global incluso fuera del contenido (en todo el HTML).
 */
add_action('template_redirect', function () {
    if (is_admin()) return;

    ob_start(function ($html) {
        // Lazy load para <img> en todo el HTML de salida
        $html = preg_replace_callback('/<img\s+([^>]+)>/i', function ($matches) {
            $img_tag = $matches[0];

            // Si ya tiene loading, no lo modificamos
            if (strpos($img_tag, 'loading=') !== false) {
                return $img_tag;
            }

            return str_replace('<img', '<img loading="lazy"', $img_tag);
        }, $html);

        return $html;
    });
});



