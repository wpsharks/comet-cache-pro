<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Automatically clears cache file for a WooCommerce Product when its stock is changed.
 *
 * @since 15xxxx Improving WooCommerce Compatibility.
 *
 * @attaches-to `woocommerce_product_set_stock` hook.
 *
 * @param \WC_Product $product A WooCommerce WC_Product object
 */
$self->autoClearPostCacheOnWooCommerceSetStock = function ($product) use ($self) {
    $counter = 0; // Initialize.

    if (!is_null($done = &$self->cacheKey('autoClearPostCacheOnWooCommerceSetStock'))) {
        return $counter; // Already did this.
    }
    $done = true; // Flag as having been done.

    if(empty($args['is_preview']) && class_exists('\\WooCommerce')) {
        $counter += $self->autoClearPostCache($product->id);
    }
};
