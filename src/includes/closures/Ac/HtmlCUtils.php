<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\IntelliCache\Pro;

/*
 * Runs HTML Compressor (if applicable).
 *
 * @since 150422 Rewrite. Updated 151002 w/ multisite compat. improvements.
 *
 * @param string $cache Input cache file we want to compress.
 *
 * @return string The `$cache` with HTML compression applied (if applicable).
 *
 * @see https://github.com/websharks/html-compressor
 */
$self->maybeCompressHtml = function ($cache) use ($self) {
    if (!$self->content_url) {
        return $cache; // Not possible.
    }
    if (!INTELLICACHE_HTMLC_ENABLE) {
        return $cache; // Nothing to do here.
    }
    // Deals with multisite base & sub-directory installs.
    // e.g. `htmlc/cache/public/www-example-com` (standard WP installation).
    // e.g. `htmlc/cache/public/[[/base]/child1]/www-example-com` (multisite network).
    // Note that `www-example-com` (current host slug) is appended by the HTML compressor.

    $host_base_dir_tokens = $self->hostBaseDirTokens(true); // Dashify this.

    $cache_dir_public     = INTELLICACHE_HTMLC_CACHE_DIR_PUBLIC.rtrim($host_base_dir_tokens, '/');
    $cache_dir_url_public = $self->content_url.str_replace(WP_CONTENT_DIR, '', $cache_dir_public);

    $cache_dir_private     = INTELLICACHE_HTMLC_CACHE_DIR_PRIVATE.rtrim($host_base_dir_tokens, '/');
    $cache_dir_url_private = $self->content_url.str_replace(WP_CONTENT_DIR, '', $cache_dir_private);

    $benchmark     = INTELLICACHE_DEBUGGING_ENABLE >= 2 ? 'details' : INTELLICACHE_DEBUGGING_ENABLE;
    $product_title = sprintf(__('%1$s HTML Compressor', SLUG_TD), NAME);

    $html_compressor_options = array(
        'benchmark'     => $benchmark,
        'product_title' => $product_title,

        'cache_dir_public'     => $cache_dir_public,
        'cache_dir_url_public' => $cache_dir_url_public,

        'cache_dir_private'     => $cache_dir_private,
        'cache_dir_url_private' => $cache_dir_url_private,

        'regex_css_exclusions' => INTELLICACHE_HTMLC_CSS_EXCLUSIONS,
        'regex_js_exclusions'  => INTELLICACHE_HTMLC_JS_EXCLUSIONS,
        'regex_uri_exclusions'  => INTELLICACHE_HTMLC_URI_EXCLUSIONS,

        'cache_expiration_time' => INTELLICACHE_HTMLC_CACHE_EXPIRATION_TIME,

        'compress_combine_head_body_css' => INTELLICACHE_HTMLC_COMPRESS_COMBINE_HEAD_BODY_CSS,
        'compress_combine_head_js'       => INTELLICACHE_HTMLC_COMPRESS_COMBINE_HEAD_JS,
        'compress_combine_footer_js'     => INTELLICACHE_HTMLC_COMPRESS_COMBINE_FOOTER_JS,
        'compress_combine_remote_css_js' => INTELLICACHE_HTMLC_COMPRESS_COMBINE_REMOTE_CSS_JS,
        'compress_inline_js_code'        => INTELLICACHE_HTMLC_COMPRESS_INLINE_JS_CODE,
        'compress_css_code'              => INTELLICACHE_HTMLC_COMPRESS_CSS_CODE,
        'compress_js_code'               => INTELLICACHE_HTMLC_COMPRESS_JS_CODE,
        'compress_html_code'             => INTELLICACHE_HTMLC_COMPRESS_HTML_CODE,
    );
    try {
        $html_compressor  = new \WebSharks\HtmlCompressor\Core($html_compressor_options);
        $compressed_cache = $html_compressor->compress($cache);
    } catch (\Exception $exception) {
        $compressed_cache = $cache; // Fail softly.
        if (INTELLICACHE_DEBUGGING_ENABLE >= 2) { // Leave a note in the source code?
            $compressed_cache .= "\n".'<!-- '.htmlspecialchars($product_title.' '.sprintf(__('Failure: %1$s', SLUG_TD), $exception->getMessage())).' -->';
        }
    }
    return $compressed_cache;
};
/*[/pro]*/
