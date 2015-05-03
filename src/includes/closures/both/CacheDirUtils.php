<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Cache directory path.
 *
 * @since 150422 Rewrite
 *
 * @param string $rel_path Relative path inside cache directory.
 *
 * @return string Absolute path to cache directory.
 */
$self->cacheDir($rel_path = '') use($self)
{
    if (method_exists($self, 'wpContentBaseDirTo') && isset($self->cache_sub_dir)) {
        $cache_dir = $self->wpContentBaseDirTo($self->cache_sub_dir);
    } elseif (defined('ZENCACHE_DIR') && ZENCACHE_DIR) {
        $cache_dir = ZENCACHE_DIR;
    }
    if (empty($cache_dir)) {
        throw new \Exception(__('Unable to determine cache directory location.', $self->text_domain));
    }
    return $cache_dir.($rel_path ? '/'.ltrim((string) $rel_path) : '');
};
