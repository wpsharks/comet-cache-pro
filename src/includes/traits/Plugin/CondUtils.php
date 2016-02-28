<?php
namespace WebSharks\CometCache\Pro\Traits\Plugin;

trait CondUtils {
    /*
     * Is pro preview?
     *
     * @since 150511 Rewrite.
     *
     * @return bool `TRUE` if it's a pro preview.
     */
    public function isProPreview()
    {
        return !empty($_REQUEST[GLOBAL_NS.'_pro_preview']);
    }
}
