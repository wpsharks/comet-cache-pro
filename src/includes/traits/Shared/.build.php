<?php
/*
 * Generate use-list for Trait files in current directory
 *
 * When run inside a directory called `Plugin/`, with a PHP file inside `Plugin/` called `ActionUtils.php`,
 * this script will generate `use Traits\Plugin\ActionUtils;`.
 *
 * @TODO Automatically insert this list into appropriate PHP files.
 */
if ($_handle = opendir(__DIR__)) {
    while (false !== ($_file = readdir($_handle))) {
        if ($_file != '.' && $_file != '..' && $_file != '.build.php' && stristr($_file, '.php') !== false) {
            echo 'use Traits\\'.basename(__DIR__).'\\'.basename($_file, '.php').';'.PHP_EOL;
        }
    }
    closedir($_handle);
    unset($_file, $_files);
}
