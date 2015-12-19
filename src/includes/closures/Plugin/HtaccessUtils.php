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

    if ($htaccess = $self->readHtaccessFile($htaccess_file, $htaccess_marker)) {
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
        $htaccess_file_contents = $template_blocks."\n\n".$htaccess['file_contents'];
    } else {
        return false; // Failure; could not read file or invalid UTF8 encountered, file may be corrupt.
    }

    if (!$self->writeHtaccessFile($htaccess['fp'], $htaccess_file_contents, $htaccess_marker, true)) {
        return false; // Failure; could not write changes.
    }

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

    if ($htaccess = $self->readHtaccessFile($htaccess_file, $htaccess_marker)) {
        if ($htaccess['marker_exists'] === false) {
            flock($htaccess['fp'], LOCK_UN);
            fclose($htaccess['fp']);
            return true; // Template blocks are already gone.
        }
        $regex                  = '/#\s*BEGIN\s+'.preg_quote(NAME, '/').'\s+'.$htaccess_marker.'.*?#\s*END\s+'.preg_quote(NAME, '/').'\s+'.$htaccess_marker.'\s*/is';
        $htaccess_file_contents = preg_replace($regex, '', $htaccess['file_contents']);
    } else {
        return false; // Failure; could not read file or invalid UTF8 encountered, file may be corrupt.
    }

    if (!$self->writeHtaccessFile($htaccess['fp'], $htaccess_file_contents, $htaccess_marker, false)) {
        return false; // Failure; could not write changes.
    }

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

/*
 * Gets contents of `/.htaccess` file with exclusive lock to read+write.
 *
 * @since 15xxxx Improving `.htaccess` utils.
 *
 * @param string $htaccess_file     Absolute path to the htaccess file
 * @param string $htaccess_marker   Unique comment marker used to identify rules added by this plugin
 *
 * @return array|bool Returns an array with data necessary to call $self->writeHtaccessFile():
 *               `fp` a file pointer resource, `file_contents` a string, and
 *               `marker_exists` a boolean indicating if the $htaccess_marker was found in
 *               htaccess contents. Returns `false` on failure.
 */
$self->readHtaccessFile = function ($htaccess_file, $htaccess_marker) use ($self) {

    if (!is_readable($htaccess_file) || !is_writable($htaccess_file) || (defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)) {
        return false; // Not possible.
    }

    if (!($fp = fopen($htaccess_file, 'rb+')) || !flock($fp, LOCK_EX)) {
        fclose($fp); // Just in case we opened it before failing to obtain a lock.
        return false; // Failure; could not open file and obtain an exclusive lock.
    }

    if (($file_contents = fread($fp, filesize($htaccess_file))) && ($file_contents === wp_check_invalid_utf8($file_contents))) {
        $marker_exists = stripos($file_contents, $htaccess_marker);
        return compact('fp', 'file_contents', 'marker_exists');
    } else { // Failure; could not read file or invalid UTF8 encountered, file may be corrupt.
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }
};

/*
 * Writes to `/.htaccess` file using provided file pointer.
 *
 * @since 15xxxx Improving `.htaccess` utils.
 *
 * @param resource $fp File pointer reource created by $self->readHtaccessFile()
 * @param string $htaccess_file_contents Contents to write to htaccess file
 * @param string $htaccess_marker   Unique comment marker used to identify rules added by this plugin
 * @param bool $require_marker Whether or not to require the marker be present in contents before writing
 *
 * @return bool True on success, false on failure.
 */
$self->writeHtaccessFile = function ($fp, $htaccess_file_contents, $htaccess_marker, $require_marker) use ($self) {

    if (!is_resource($fp)) {
        return false;
    }

    $_have_marker = stripos($htaccess_file_contents, $htaccess_marker);

    // Note: rewind() necessary here because we fread() above.
    if (($require_marker && $_have_marker === false) || !rewind($fp) || !ftruncate($fp, 0) || !fwrite($fp, $htaccess_file_contents)) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false; // Failure; could not write changes.
    }

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
};
