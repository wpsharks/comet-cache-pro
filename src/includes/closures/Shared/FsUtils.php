<?php
namespace WebSharks\ZenCache\Pro;

/*
 * Normalizes directory/file separators.
 *
 * @since 150422 Rewrite.
 *
 * @param string  $dir_file Directory/file path.
 *
 * @param boolean $allow_trailing_slash Defaults to FALSE.
 *    If TRUE; and `$dir_file` contains a trailing slash; we'll leave it there.
 *
 * @return string Normalized directory/file path.
 */
$self->nDirSeps = function ($dir_file, $allow_trailing_slash = false) use ($self) {
    $dir_file = (string) $dir_file;

    if (!isset($dir_file[0])) {
        return ''; // Catch empty string.
    }
    if (strpos($dir_file, '://' !== false)) {
        if (preg_match('/^(?P<stream_wrapper>[a-zA-Z0-9]+)\:\/\//', $dir_file, $stream_wrapper)) {
            $dir_file = preg_replace('/^(?P<stream_wrapper>[a-zA-Z0-9]+)\:\/\//', '', $dir_file);
        }
    }
    if (strpos($dir_file, ':' !== false)) {
        if (preg_match('/^(?P<drive_letter>[a-zA-Z])\:[\/\\\\]/', $dir_file)) {
            $dir_file = preg_replace_callback('/^(?P<drive_letter>[a-zA-Z])\:[\/\\\\]/', create_function('$m', 'return strtoupper($m[0]);'), $dir_file);
        }
    }
    $dir_file = preg_replace('/\/+/', '/', str_replace(array(DIRECTORY_SEPARATOR, '\\', '/'), '/', $dir_file));
    $dir_file = ($allow_trailing_slash) ? $dir_file : rtrim($dir_file, '/'); // Strip trailing slashes.

    if (!empty($stream_wrapper[0])) {
        $dir_file = strtolower($stream_wrapper[0]).$dir_file;
    }
    return $dir_file; // Normalized now.
};

/*
 * Acquires system tmp directory path.
 *
 * @since 150422 Rewrite.
 *
 * @return string System tmp directory path; else an empty string.
 */
$self->getTmpDir = function () use ($self) {
    if (!is_null($dir = &$self->staticKey('getTmpDir'))) {
        return $dir; // Already cached this.
    }
    $possible_dirs = array(); // Initialize.

    if (defined('WP_TEMP_DIR')) {
        $possible_dirs[] = (string) WP_TEMP_DIR;
    }
    if ($self->functionIsPossible('sys_get_temp_dir')) {
        $possible_dirs[] = (string) sys_get_temp_dir();
    }
    $possible_dirs[] = (string) ini_get('upload_tmp_dir');

    if (!empty($_SERVER['TEMP'])) {
        $possible_dirs[] = (string) $_SERVER['TEMP'];
    }
    if (!empty($_SERVER['TMPDIR'])) {
        $possible_dirs[] = (string) $_SERVER['TMPDIR'];
    }
    if (!empty($_SERVER['TMP'])) {
        $possible_dirs[] = (string) $_SERVER['TMP'];
    }
    if (stripos(PHP_OS, 'win') === 0) {
        $possible_dirs[] = 'C:/Temp';
    }
    if (stripos(PHP_OS, 'win') !== 0) {
        $possible_dirs[] = '/tmp';
    }
    if (defined('WP_CONTENT_DIR')) {
        $possible_dirs[] = (string) WP_CONTENT_DIR;
    }
    foreach ($possible_dirs as $_key => $_dir) {
        if (($_dir = trim((string) $_dir)) && @is_dir($_dir) && @is_writable($_dir)) {
            return ($dir = $self->nDirSeps($_dir));
        }
    }
    unset($_key, $_dir); // Housekeeping.

    return ($dir = '');
};

/*
 * Finds absolute server path to `/wp-config.php` file.
 *
 * @since 150422 Rewrite.
 *
 * @return string Absolute server path to `/wp-config.php` file;
 *    else an empty string if unable to locate the file.
 */
$self->findWpConfigFile = function () use ($self) {
    if (!is_null($file = &$self->staticKey('findWpConfigFile'))) {
        return $file; // Already cached this.
    }
    $file = ''; // Initialize.

    if (is_file($abspath_wp_config = ABSPATH.'wp-config.php')) {
        $file = $abspath_wp_config;
    } elseif (is_file($dirname_abspath_wp_config = dirname(ABSPATH).'/wp-config.php')) {
        $file = $dirname_abspath_wp_config;
    }
    return $file;
};

/*
 * Adds a tmp name suffix to a directory/file path.
 *
 * @since 150422 Rewrite.
 *
 * @param string $dir_file An input directory or file path.
 *
 * @return string The original `$dir_file` with a tmp name suffix.
 */
$self->addTmpSuffix = function ($dir_file) use ($self) {
    $dir_file = (string) $dir_file;
    $dir_file = rtrim($dir_file, DIRECTORY_SEPARATOR.'\\/');

    return $dir_file.'-'.str_replace('.', '', uniqid('', true)).'-tmp';
};

/*
 * Recursive directory iterator based on a regex pattern.
 *
 * @since 150422 Rewrite.
 *
 * @param string $dir An absolute server directory path.
 * @param string $regex A regex pattern; compares to each full file path.
 *
 * @return \RegexIterator Navigable with {@link \foreach()}; where each item
 *    is a {@link \RecursiveDirectoryIterator}.
 */
$self->dirRegexIteration = function ($dir, $regex = '') use ($self) {
    $dir   = (string) $dir;
    $regex = (string) $regex;

    $dir_iterator      = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
    $iterator_iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST);

    if ($regex && $regex !== '/.*/' && $regex !== '/.+/') { // Apply regex filter?
        // @TODO Optimize calls to this method in order to avoid the regex iterator when not necessary.
        return new \RegexIterator($iterator_iterator, $regex, \RegexIterator::MATCH, \RegexIterator::USE_KEY);
    }
    return $iterator_iterator; // Iterate everything.
};

/*
 * Abbreviated byte notation for file sizes.
 *
 * @since 15xxxx Adding a few statistics.
 *
 * @param float   $bytes File size in bytes. A (float) value.
 * @param integer $precision Number of decimals to use.
 *
 * @return string Byte notation.
 */
$self->bytesAbbr = function ($bytes, $precision = 2) use ($self) {
    $bytes     = max(0.0, (float) $bytes);
    $precision = max(0, (integer) $precision);
    $units     = array('bytes', 'kbs', 'MB', 'GB', 'TB');

    $power      = floor(($bytes ? log($bytes) : 0) / log(1024));
    $abbr_bytes = round($bytes / pow(1024, $power), $precision);
    $abbr       = $units[min($power, count($units) - 1)];

    if ($abbr_bytes === (float) 1 && $abbr === 'bytes') {
        $abbr = 'byte'; // Quick fix.
    } elseif ($abbr_bytes === (float) 1 && $abbr === 'kbs') {
        $abbr = 'kb'; // Quick fix.
    }
    return $abbr_bytes.' '.$abbr;
};

/*
 * Converts an abbreviated byte notation into bytes.
 *
 * @since 15xxxx Adding a few statistics.
 *
 * @param string $string A string value in byte notation.
 *
 * @return float A float indicating the number of bytes.
 */
$self->abbrBytes = function ($string) use ($self) {
    $string = (string) $string;
    $regex  = '/^(?P<value>[0-9\.]+)\s*(?P<modifier>bytes|byte|kbs|kb|k|mb|m|gb|g|tb|t)$/i';

    if (!preg_match($regex, $string, $_m)) {
        return (float) 0;
    }
    $value    = (float) $_m['value'];
    $modifier = strtolower($_m['modifier']);
    unset($_m); // Housekeeping.

    switch ($modifier) {
        case 't':
        case 'tb':
            $value *= 1024;
            // Fall through.
        case 'g':
        case 'gb':
            $value *= 1024;
            // Fall through.
        case 'm':
        case 'mb':
            $value *= 1024;
            // Fall through.
        case 'k':
        case 'kb':
        case 'kbs':
            $value *= 1024;
    }
    return (float) $value;
};

/*
 * Apache `.htaccess` rules that deny public access to the contents of a directory.
 *
 * @since 150422 Rewrite.
 *
 * @var string `.htaccess` fules.
 */
$self->htaccess_deny = "<IfModule authz_core_module>\n\tRequire all denied\n</IfModule>\n<IfModule !authz_core_module>\n\tdeny from all\n</IfModule>";
