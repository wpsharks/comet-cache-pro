<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Add template blocks to `/.htaccess` file.
 *
 * @since 15xxxx Adding `.htaccess` tweaks.
 *
 * @return boolean True if added successfully.
 */
$self->addWpHtaccess = function () use ($self) {
    global $is_apache;

    if (!$is_apache) {
        return false; // Not running the Apache web server.
    }
    if (!$self->options['enable']) {
        return false; // Nothing to do.
    }
    if (!$self->removeWpHtaccess()) {
        return false; // Unable to remove.
    }
    if (!($htaccess_file = $self->findHtaccessFile())) {
        if (!is_writable($self->wpHomePath()) || file_put_contents($htaccess_file = $self->wpHomePath().'.htaccess', '') === false) {
            return false; // Unable to find and/or create `.htaccess`.
        } // If it doesn't exist, we create the `.htaccess` file here.
    }
    if (!is_readable($htaccess_file)) {
        return false; // Not possible.
    }
    if (($htaccess_file_contents = file_get_contents($htaccess_file)) === false) {
        return false; // Failure; could not read file.
    }
    $template_blocks = '# BEGIN '.NAME."\n"; // Initialize.

    foreach (array('etags.txt', 'expires.txt', 'cdn-filters.txt', 'gzip.txt', 'cache.txt') as $_template) {
        if (!is_file($_template_file = dirname(dirname(dirname(__FILE__))).'/templates/htaccess/'.$_template)) {
            continue; // Template file missing; bypass.
        } // ↑ Some files might be missing in the lite version.
        elseif (!($_template_file_contents = trim(file_get_contents($_template_file)))) {
            continue; // Template file empty; bypass.
        } // ↑ Some files might be empty in the lite version.

        switch ($_template) {
            case 'etags.txt':
                if ($self->options['htaccess_etags_enable']) {
                    $template_blocks .= $_template_file_contents."\n";
                }
                break;

            case 'expires.txt':
                if ($self->options['htaccess_expires_enable']) {
                    $template_blocks .= $_template_file_contents."\n";
                }
                break;

            /*[pro strip-from="lite"]*/
            case 'cdn-filters.txt':
                if ($self->options['cdn_enable']) {
                    $template_blocks .= $_template_file_contents."\n";
                } // Only if CDN filters are enabled at this time.
                break;
            /*[/pro]*/

            case 'gzip.txt':
                if ($self->options['htaccess_gzip_enable']) {
                    $template_blocks .= $_template_file_contents."\n";
                }
                break;

            case 'cache.txt':
                if ($self->options['htaccess_cache_enable']) {
                    $_template_block = $_template_file_contents; // Working copy.
                    $_template_block = str_ireplace('%%cache_dir%%', $self->cacheDir(), $_template_block);
                    $template_blocks .= $_template_block."\n";
                }
                break;
        }
    } unset($_template, $_template_file, $_template_file_contents, $_template_block); // Housekeeping.

    $template_blocks        = trim($template_blocks)."\n".'# END '.NAME;
    $htaccess_file_contents = $template_blocks."\n\n".$htaccess_file_contents;

    if (stripos($htaccess_file_contents, NAME) === false) {
        return false; // Failure; unexpected file contents.
    }
    if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
        return false; // We may NOT edit any files.
    }
    if (!is_writable($htaccess_file)) {
        return false; // Not possible.
    }
    if (file_put_contents($htaccess_file, $htaccess_file_contents) === false) {
        return false; // Failure; could not write changes.
    }
    return true; // Added successfully.
};

/*
 * Remove template blocks from `/.htaccess` file.
 *
 * @since 15xxxx Adding `.htaccess` tweaks.
 *
 * @return boolean True if removed successfully.
 */
$self->removeWpHtaccess = function () use ($self) {
    global $is_apache;

    if (!$is_apache) {
        return false; // Not running the Apache web server.
    }
    if (!($htaccess_file = $self->findHtaccessFile())) {
        return true; // File does not exist.
    }
    if (!is_readable($htaccess_file)) {
        return false; // Not possible.
    }
    if (($htaccess_file_contents = file_get_contents($htaccess_file)) === false) {
        return false; // Failure; could not read file.
    }
    if (stripos($htaccess_file_contents, NAME) === false) {
        return true; // Template blocks are already gone.
    }
    $regex                  = '/#\s*BEGIN\s+'.preg_quote(NAME, '/').'\b.*?#\s*END\s+'.preg_quote(NAME, '/').'\s*/is';
    $htaccess_file_contents = preg_replace($regex, '', $htaccess_file_contents);

    if (stripos($htaccess_file_contents, NAME) !== false) {
        return false; // Failure; unexpected file contents.
    }
    if (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS) {
        return false; // We may NOT edit any files.
    }
    if (!is_writable($htaccess_file)) {
        return false; // Not possible.
    }
    if (file_put_contents($htaccess_file, $htaccess_file_contents) === false) {
        return false; // Failure; could not write changes.
    }
    return true; // Removed successfully.
};

/*
 * Finds absolute server path to `/.htaccess` file.
 *
 * @since 15xxxx Adding `.htaccess` tweaks.
 *
 * @return string Absolute server path to `/.htaccess` file;
 *    else an empty string if unable to locate the file.
 */
$self->findHtaccessFile = function () use ($self) {
    $file      = ''; // Initialize.
    $home_path = $self->wpHomePath();

    if (is_file($htaccess_file = $home_path.'.htaccess')) {
        $file = $htaccess_file;
    }
    return $file;
};
