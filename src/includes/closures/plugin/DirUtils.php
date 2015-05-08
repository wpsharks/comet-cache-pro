<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Utilities.
 *
 * @since 150422 Rewrite.
 */
class DirUtils extends AbsBase
{
    /**
     * This constructs an absolute server directory path (no trailing slashes);
     *    which is always nested into {@link \WP_CONTENT_DIR} and the configured `base_dir` option value.
     *
     * @since 140605 Moving to a base directory structure.
     *
     * @param string $rel_dir_file A sub-directory or file; relative location please.
     *
     * @throws \exception If `base_dir` is empty when this method is called upon;
     *                    i.e. if you attempt to call upon this method before {@link setup()} runs.
     *
     * @return string The full absolute server path to `$rel_dir_file`.
     */
    public function wp_content_base_dir_to($rel_dir_file)
    {
        $rel_dir_file = trim((string) $rel_dir_file, '\\/'." \t\n\r\0\x0B");

        if (empty($this->options) || !is_array($this->options) || empty($this->options['base_dir'])) {
            throw new \Exception(__('Doing it wrong! Missing `base_dir` option value. MUST call this method after `setup()`.', $this->text_domain));
        }

        $wp_content_base_dir_to = WP_CONTENT_DIR.'/'.$this->options['base_dir'];

        if (isset($rel_dir_file[0])) {
            // Do we have this also?
            $wp_content_base_dir_to .= '/'.$rel_dir_file;
        }

        return $this->apply_wp_filters(__METHOD__, $wp_content_base_dir_to, get_defined_vars());
    }

    /**
     * This constructs a relative/base directory path (no leading/trailing slashes).
     *    Always relative to {@link \WP_CONTENT_DIR}. Depends on the configured `base_dir` option value.
     *
     * @since 140605 Moving to a base directory structure.
     *
     * @param string $rel_dir_file A sub-directory or file; relative location please.
     *
     * @throws \exception If `base_dir` is empty when this method is called upon;
     *                    i.e. if you attempt to call upon this method before {@link setup()} runs.
     *
     * @return string The relative/base directory path to `$rel_dir_file`.
     */
    public function base_path_to($rel_dir_file)
    {
        $rel_dir_file = trim((string) $rel_dir_file, '\\/'." \t\n\r\0\x0B");

        if (empty($this->options) || !is_array($this->options) || empty($this->options['base_dir'])) {
            throw new \Exception(__('Doing it wrong! Missing `base_dir` option value. MUST call this method after `setup()`.', $this->text_domain));
        }

        $base_path_to = $this->options['base_dir'];

        if (isset($rel_dir_file[0])) {
            // Do we have this also?
            $base_path_to .= '/'.$rel_dir_file;
        }

        return $this->apply_wp_filters(__METHOD__, $base_path_to, get_defined_vars());
    }
}
