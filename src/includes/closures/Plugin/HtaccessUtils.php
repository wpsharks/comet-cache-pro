<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Add template blocks to `/.htaccess` file.
 *
 * @since 151114 Adding `.htaccess` tweaks.
 *
 * @return boolean True if added successfully.
 */
$self->addWpHtaccess = function () use ($self) {
    global $is_apache;
    $htaccess_marker = 'WmVuQ2FjaGU'; // Unique comment marker used to identify rules added by this plugin

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
    if (!is_readable($htaccess_file) || !is_writable($htaccess_file) || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)) {
        return false; // Not possible.
    }

    if (!(($_fp = fopen($htaccess_file,'rb+')) && flock($_fp, LOCK_EX))) {
        fclose($_fp); // Just in case we opened it before failing to obtain a lock.
        return false; // Failure; could not open file and obtain an exclusive lock.
    }

    if ($htaccess_file_contents = fread($_fp, filesize($htaccess_file))) {
        $template_blocks = '# BEGIN '.NAME.' '.$htaccess_marker.' (the '.$htaccess_marker.' marker is required for '.NAME.'; do not remove)'."\n"; // Initialize.
        if (is_dir($templates_dir = dirname(dirname(dirname(__FILE__))).'/templates/htaccess')) {
            foreach (scandir($templates_dir) as $_template_file) {
                switch ($_template_file) {
                    /*[pro strip-from="lite"]*/
                    case 'cdn-filters.txt':
                        if ($self->options['cdn_enable']) {
                            $template_blocks .= trim(file_get_contents($templates_dir.'/'.$_template_file))."\n";
                        } // Only if CDN filters are enabled at this time.
                        break;
                    /*[/pro]*/
                }
            }
            unset($_template_file); // Housekeeping.
        }
        $template_blocks        = trim($template_blocks)."\n".'# END '.NAME.' '.$htaccess_marker;
        $htaccess_file_contents = $template_blocks."\n\n".$htaccess_file_contents;
    } else { // Failure; could not read file
        flock($_fp, LOCK_UN);
        fclose($_fp);
        return false;
    }

    $_have_marker = stripos($htaccess_file_contents, $htaccess_marker);

    // Note: rewind() necessary here because we fread() above.
    if (!($_have_marker !== false && rewind($_fp) && ftruncate($_fp, 0) && fwrite($_fp, $htaccess_file_contents))) {
        flock($_fp, LOCK_UN);
        fclose($_fp);
        return false; // Failure; unexpected file contents or could not write changes.
    }

    fflush($_fp);
    flock($_fp, LOCK_UN);
    fclose($_fp);
    return true; // Added successfully.
};

/*
 * Remove template blocks from `/.htaccess` file.
 *
 * @since 151114 Adding `.htaccess` tweaks.
 *
 * @return boolean True if removed successfully.
 */
$self->removeWpHtaccess = function () use ($self) {
    global $is_apache;
    $htaccess_marker = 'WmVuQ2FjaGU'; // Unique comment marker used to identify rules added by this plugin

    if (!$is_apache) {
        return false; // Not running the Apache web server.
    }
    if (!($htaccess_file = $self->findHtaccessFile())) {
        return true; // File does not exist.
    }
    if (!is_readable($htaccess_file) || !is_writable($htaccess_file) || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)) {
        return false; // Not possible.
    }

    if (!(($_fp = fopen($htaccess_file,'rb+')) && flock($_fp, LOCK_EX))) {
        return false; // Failure; could not open file and obtain an exclusive lock.
    }

    if (($htaccess_file_contents = fread($_fp, filesize($htaccess_file))) && ($htaccess_file_contents === wp_check_invalid_utf8($htaccess_file_contents))) {
        if (stripos($htaccess_file_contents, NAME) === false) {
            flock($_fp, LOCK_UN);
            fclose($_fp);
            return true; // Template blocks are already gone.
        }
        $regex                  = '/#\s*BEGIN\s+'.preg_quote(NAME, '/').'\s+'.$htaccess_marker.'.*?#\s*END\s+'.preg_quote(NAME, '/').'\s+'.$htaccess_marker.'\s*/is';
        $htaccess_file_contents = preg_replace($regex, '', $htaccess_file_contents);
    } else { // Failure; could not read file or invalid UTF8 encounted, file may be corrupt.
        flock($_fp, LOCK_UN);
        fclose($_fp);
        return false;
    }

    $_have_marker = stripos($htaccess_file_contents, $htaccess_marker);

    // Note: rewind() necessary here because we fread() above.
    if (!($_have_marker === false && rewind($_fp) && ftruncate($_fp, 0) && fwrite($_fp, $htaccess_file_contents))) {
        flock($_fp, LOCK_UN);
        fclose($_fp);
        return false; // Failure; could not write changes.
    }

    fflush($_fp);
    flock($_fp, LOCK_UN);
    fclose($_fp);
    return true; // Removed successfully.
};

/*
 * Finds absolute server path to `/.htaccess` file.
 *
 * @since 151114 Adding `.htaccess` tweaks.
 *
 * @return string Absolute server path to `/.htaccess` file;
 *    else an empty string if unable to locate the file.
 */
$self->findHtaccessFile = function () use ($self) {
    $file = ''; // Initialize.
    $home_path = $self->wpHomePath();

    if (is_file($htaccess_file = $home_path.'.htaccess')) {
        $file = $htaccess_file;
    }
    return $file;
};
