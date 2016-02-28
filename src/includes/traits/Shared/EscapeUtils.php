<?php
namespace WebSharks\CometCache\Pro\Traits\Shared;

use WebSharks\CometCache\Pro\Classes;

trait EscapeUtils {
    /**
     * Escape single quotes.
     *
     * @since 150422 Rewrite.
     *
     * @param string  $string Input string to escape.
     * @param integer $times Optional. Defaults to one escape char; e.g. `\'`.
     *    If you need to escape more than once, set this to something > `1`.
     *
     * @return string Escaped string; e.g. `Raam\'s the lead developer`.
     */
    public function escSq($string, $times = 1)
    {
        return str_replace("'", str_repeat('\\', abs($times))."'", (string) $string);
    }
}
