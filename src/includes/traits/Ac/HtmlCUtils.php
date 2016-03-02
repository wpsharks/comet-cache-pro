<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Ac;

use WebSharks\CometCache\Pro\Classes;

trait HtmlCUtils {
    /**
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
    public function maybeCompressHtml($cache)
    {
        if (!$this->content_url) {
            return $cache; // Not possible.
        }
        if (!COMET_CACHE_HTMLC_ENABLE) {
            return $cache; // Nothing to do here.
        }
        if ($this->is_user_logged_in && !COMET_CACHE_HTMLC_WHEN_LOGGED_IN) {
            return $cache; // Nothing to do here.
        }
        // Deals with multisite base & sub-directory installs.
        // e.g. `htmlc/cache/public/www-example-com` (standard WP installation).
        // e.g. `htmlc/cache/public/[[/base]/child1]/www-example-com` (multisite network).
        // Note that `www-example-com` (current host slug) is appended by the HTML compressor.

        $host_base_dir_tokens = $this->hostBaseDirTokens(true); // Dashify this.

        $cache_dir_public     = COMET_CACHE_HTMLC_CACHE_DIR_PUBLIC.rtrim($host_base_dir_tokens, '/');
        $cache_dir_url_public = $this->content_url.str_replace(WP_CONTENT_DIR, '', $cache_dir_public);

        $cache_dir_private     = COMET_CACHE_HTMLC_CACHE_DIR_PRIVATE.rtrim($host_base_dir_tokens, '/');
        $cache_dir_url_private = $this->content_url.str_replace(WP_CONTENT_DIR, '', $cache_dir_private);

        $benchmark     = COMET_CACHE_DEBUGGING_ENABLE >= 2 ? 'details' : COMET_CACHE_DEBUGGING_ENABLE;
        $product_title = sprintf(__('%1$s HTML Compressor', SLUG_TD), NAME);

        $html_compressor_options = [
            'benchmark'     => $benchmark,
            'product_title' => $product_title,

            'cache_dir_public'     => $cache_dir_public,
            'cache_dir_url_public' => $cache_dir_url_public,

            'cache_dir_private'     => $cache_dir_private,
            'cache_dir_url_private' => $cache_dir_url_private,

            'regex_css_exclusions' => COMET_CACHE_HTMLC_CSS_EXCLUSIONS,
            'regex_js_exclusions'  => COMET_CACHE_HTMLC_JS_EXCLUSIONS,
            'regex_uri_exclusions' => COMET_CACHE_HTMLC_URI_EXCLUSIONS,

            'cache_expiration_time' => COMET_CACHE_HTMLC_CACHE_EXPIRATION_TIME,

            'compress_combine_head_body_css' => COMET_CACHE_HTMLC_COMPRESS_COMBINE_HEAD_BODY_CSS,
            'compress_combine_head_js'       => COMET_CACHE_HTMLC_COMPRESS_COMBINE_HEAD_JS,
            'compress_combine_footer_js'     => COMET_CACHE_HTMLC_COMPRESS_COMBINE_FOOTER_JS,
            'compress_combine_remote_css_js' => COMET_CACHE_HTMLC_COMPRESS_COMBINE_REMOTE_CSS_JS,
            'compress_inline_js_code'        => COMET_CACHE_HTMLC_COMPRESS_INLINE_JS_CODE,
            'compress_css_code'              => COMET_CACHE_HTMLC_COMPRESS_CSS_CODE,
            'compress_js_code'               => COMET_CACHE_HTMLC_COMPRESS_JS_CODE,
            'compress_html_code'             => COMET_CACHE_HTMLC_COMPRESS_HTML_CODE,
        ];
        try {
            $html_compressor  = new \WebSharks\HtmlCompressor\Core($html_compressor_options);
            $compressed_cache = $html_compressor->compress($cache);
        } catch (\Exception $exception) {
            $compressed_cache = $cache; // Fail softly.
            if (COMET_CACHE_DEBUGGING_ENABLE >= 2) { // Leave a note in the source code?
                $compressed_cache .= "\n".'<!-- '.htmlspecialchars($product_title.' '.sprintf(__('Failure: %1$s', SLUG_TD), $exception->getMessage())).' -->';
            }
        }
        return $compressed_cache;
    }
}
/*[/pro]*/
