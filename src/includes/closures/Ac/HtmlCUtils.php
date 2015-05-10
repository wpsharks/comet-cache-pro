<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Runs HTML Compressor (if applicable).
 *
 * @since 150422 Rewrite.
 *
 * @param string $cache Input cache file we want to compress.
 *
 * @return string The `$cache` with HTML compression applied (if applicable).
 *
 * @see https://github.com/websharks/HTML-Compressor
 */
$self->maybeCompressHtml = function ($cache) use ($self) {
    if (!$self->content_url) {
        return $cache; // Not possible.
    }
    if (!ZENCACHE_HTMLC_ENABLE) {
        return $cache; // Nothing to do here.
    }
    if (($host_dir_token = $self->hostDirToken(true)) === '/') {
        $host_dir_token = ''; // Not necessary.
    }
    // Deals with multisite sub-directory installs.
    // e.g. `wp-content/cache/zencache/htmlc/cache/public/www-example-com` (main site)
    // e.g. `wp-content/cache/zencache/htmlc/cache/public/sub/www-example-com`
    $cache_dir_public     = ZENCACHE_HTMLC_CACHE_DIR_PUBLIC.$host_dir_token;
    $cache_dir_private    = ZENCACHE_HTMLC_CACHE_DIR_PRIVATE.$host_dir_token;
    $cache_dir_url_public = $self->content_url.str_replace(WP_CONTENT_DIR, '', $cache_dir_public);
    $benchmark            = ZENCACHE_DEBUGGING_ENABLE >= 2 ? 'details' : ZENCACHE_DEBUGGING_ENABLE;
    $product_title        = sprintf(__('%1$s HTML Compressor', SLUG_TD), NAME);

    $html_compressor_options = array(
        'benchmark'                      => $benchmark,
        'product_title'                  => $product_title,

        'cache_dir_public'               => $cache_dir_public,
        'cache_dir_url_public'           => $cache_dir_url_public,
        'cache_dir_private'              => $cache_dir_private,

        'cache_expiration_time'          => ZENCACHE_HTMLC_CACHE_EXPIRATION_TIME,

        'regex_css_exclusions'           => ZENCACHE_HTMLC_CSS_EXCLUSIONS,
        'regex_js_exclusions'            => ZENCACHE_HTMLC_JS_EXCLUSIONS,

        'compress_combine_head_body_css' => ZENCACHE_HTMLC_COMPRESS_COMBINE_HEAD_BODY_CSS,
        'compress_combine_head_js'       => ZENCACHE_HTMLC_COMPRESS_COMBINE_HEAD_JS,
        'compress_combine_footer_js'     => ZENCACHE_HTMLC_COMPRESS_COMBINE_FOOTER_JS,
        'compress_combine_remote_css_js' => ZENCACHE_HTMLC_COMPRESS_COMBINE_REMOTE_CSS_JS,
        'compress_inline_js_code'        => ZENCACHE_HTMLC_COMPRESS_INLINE_JS_CODE,
        'compress_css_code'              => ZENCACHE_HTMLC_COMPRESS_CSS_CODE,
        'compress_js_code'               => ZENCACHE_HTMLC_COMPRESS_JS_CODE,
        'compress_html_code'             => ZENCACHE_HTMLC_COMPRESS_HTML_CODE,
    );
    $html_compressor  = new \WebSharks\HtmlCompressor\Core($html_compressor_options);
    $compressed_cache = $html_compressor->compress($cache);

    return $compressed_cache;
};
