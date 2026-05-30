<?php
/**
 * Plugin Name: HTML Whitespace Cleaner
 * Description: Minifies HTML for Page Speed — strips whitespace between tags, collapses runs of spaces,
 *              minifies inline &lt;style&gt; CSS, compact JSON-LD, optional light JS cleanup, inline style="" attributes.
 *              Preserves pre/textarea. Inline JS bodies are not minified (only JSON-LD / importmap JSON) to avoid syntax errors.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Strip block comments and redundant whitespace inside inline CSS (and style="" values). */
if (!defined('HTML_OPTIMIZER_MINIFY_STYLE_BLOCKS')) {
    define('HTML_OPTIMIZER_MINIFY_STYLE_BLOCKS', true);
}

/** Compact application/ld+json script bodies. */
if (!defined('HTML_OPTIMIZER_MINIFY_LD_JSON')) {
    define('HTML_OPTIMIZER_MINIFY_LD_JSON', true);
}

/**
 * Remove /* ... *\/ inside generic JS (can break if a comment delimiter appears inside a string).
 * Set false if something breaks.
 */
if (!defined('HTML_OPTIMIZER_JS_STRIP_BLOCK_COMMENTS')) {
    define('HTML_OPTIMIZER_JS_STRIP_BLOCK_COMMENTS', true);
}

/**
 * Join inline script to one line: strip CR/LF after other cleanup.
 * Relies on explicit semicolons / brace style (typical theme jQuery/IIFE). Disable if a script breaks (ASI edge cases).
 */
if (!defined('HTML_OPTIMIZER_JS_COLLAPSE_NEWLINES')) {
    define('HTML_OPTIMIZER_JS_COLLAPSE_NEWLINES', true);
}

/**
 * Minify inline style="..." after external blocks are extracted (safe pass: no script bodies in HTML yet).
 */
if (!defined('HTML_OPTIMIZER_MINIFY_INLINE_STYLE_ATTRS')) {
    define('HTML_OPTIMIZER_MINIFY_INLINE_STYLE_ATTRS', true);
}

/**
 * @param string $css
 * @return string
 */
function html_optimizer_minify_css($css)
{
    if (!is_string($css) || $css === '') {
        return $css;
    }

    $css = preg_replace('#/\*[\s\S]*?\*/#', '', $css);
    if (!is_string($css)) {
        return '';
    }

    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
    $css = preg_replace('/;}/', '}', $css);
    return trim($css);
}

/**
 * @param string $json
 * @return string
 */
function html_optimizer_minify_ld_json($json)
{
    if (!is_string($json) || $json === '') {
        return $json;
    }

    $trimmed = trim($json);
    $data = json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    return preg_replace('/\s+/', ' ', $trimmed);
}

/**
 * Inline JS cleanup: strip block comments, squeeze spaces, optionally remove newlines for a single-line output.
 *
 * @param string $js
 * @return string
 */
function html_optimizer_minify_inline_js_light($js)
{
    if (!is_string($js) || $js === '') {
        return $js;
    }

    $js = trim($js);

    if (HTML_OPTIMIZER_JS_STRIP_BLOCK_COMMENTS) {
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
        if (!is_string($js)) {
            return '';
        }
    }

    // Collapse runs of spaces/tabs on the same line only (keep newlines for now).
    $js = preg_replace('/[ \t]{2,}/', ' ', $js);
    // Drop lines that are only whitespace.
    $js = preg_replace('/^\s*\R/m', '', $js);

    if (HTML_OPTIMIZER_JS_COLLAPSE_NEWLINES) {
        $js = preg_replace('/\R+/', '', $js);
        if (!is_string($js)) {
            return '';
        }
        $js = preg_replace('/[ \t]{2,}/', ' ', $js);
    }

    return trim($js);
}

/**
 * @param string $open Opening tag including attributes.
 * @param string $inner Tag inner HTML/text.
 * @return string
 */
function html_optimizer_process_script_inner($open, $inner)
{
    if (!is_string($inner)) {
        return '';
    }

    $type = '';
    if (preg_match('/\btype\s*=\s*(["\'])([^"\']*)\1/i', $open, $tm)) {
        $type = strtolower(trim($tm[2]));
    } elseif (preg_match('/\btype\s*=\s*([^\s>]+)/i', $open, $tm)) {
        $type = strtolower(trim($tm[2]));
    }

    if (HTML_OPTIMIZER_MINIFY_LD_JSON && ($type === 'application/ld+json' || strpos($type, 'ld+json') !== false)) {
        return html_optimizer_minify_ld_json($inner);
    }

    if ($type === 'text/template' || strpos($type, 'template') !== false) {
        return $inner;
    }

    if ($type === 'importmap' || (strpos($type, 'json') !== false && $type !== 'application/javascript')) {
        return html_optimizer_minify_ld_json($inner);
    }

    // Do not rewrite generic inline JS: stripping /* */ and joining lines breaks real code
    // (e.g. comments near path + domain, RegExp, ASI). JSON-LD / importmap handled above.
    return $inner;
}

/**
 * Minify CSS inside [gallery] shortcode style block.
 */
add_filter('gallery_style', function ($gallery_style_html) {
    return preg_replace_callback(
        '/<style\b[^>]*>(.*?)<\/style>/is',
        function ($m) {
            return '<style>' . html_optimizer_minify_css($m[1]) . '</style>';
        },
        $gallery_style_html
    );
}, 99);

add_action('template_redirect', function () {

    if (is_admin()) {
        return;
    }

    ob_start(function ($html) {

        if (!is_string($html) || stripos($html, '<html') === false) {
            return $html;
        }

        $blocks  = [];
        $pattern = '%(</?(?:pre|textarea|script|style)\b[^>]*>)(.*?)(</(?:pre|textarea|script|style)>)%is';

        $html = preg_replace_callback($pattern, function ($m) use (&$blocks) {
            $open  = $m[1];
            $inner = $m[2];
            $close = $m[3];

            if (preg_match('/<style\b/i', $open)) {
                if (HTML_OPTIMIZER_MINIFY_STYLE_BLOCKS) {
                    $inner = html_optimizer_minify_css($inner);
                }
            } elseif (preg_match('/<script\b/i', $open)) {
                $inner = html_optimizer_process_script_inner($open, $inner);
            }
            // pre / textarea: leave $inner unchanged.

            $placeholder = '__HTMLOPT_BLOCK_' . count($blocks) . '__';
            $blocks[]    = $open . $inner . $close;
            return $placeholder;
        }, $html);

        if (!is_string($html)) {
            return '';
        }

        if (HTML_OPTIMIZER_MINIFY_INLINE_STYLE_ATTRS) {
            $html = preg_replace_callback(
                '/\sstyle\s*=\s*(["\'])(.*?)\1/is',
                static function ($m) {
                    return ' style=' . $m[1] . html_optimizer_minify_css($m[2]) . $m[1];
                },
                $html
            );
        }

        // Remove HTML comments (keep IE conditional comments).
        $html = preg_replace('/<!--(?!\s*\[if\b).*?-->/is', '', $html);

        // Remove whitespace between tags.
        $html = preg_replace('/>\s+</', '><', $html);

        // Collapse runs of whitespace (outside restored blocks).
        $html = preg_replace('/\s{2,}/', ' ', $html);

        foreach ($blocks as $i => $block) {
            $html = str_replace('__HTMLOPT_BLOCK_' . $i . '__', $block, $html);
        }

        return $html;
    });
});
