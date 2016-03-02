<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\CometCache\Pro\Traits\Plugin;

use WebSharks\CometCache\Pro\Classes;

trait WcpEvalUtils {

    /**
     * Wipe (i.e., eval) custom code.
     *
     * @since 151002 Enhancing eval support.
     *
     * @param bool $manually True if wiping is done manually.
     * @param boolean $maybe Defaults to a true value.
     *
     * @return string Result from custom code.
     */
    public function wipeEvalCode($manually = false, $maybe = true)
    {
        $result = ''; // Initialize result.

        if ($maybe && !$this->options['cache_clear_eval_code']) {
            return $result; // Not enabled at this time.
        }
        if (!$this->options['cache_clear_eval_code']) {
            return $result; // Nothing to eval.
        }
        if (!$this->functionIsPossible('eval')) {
            return $result; // Not possible.
        }
        ob_start(); // Buffer output from PHP code.
        eval('?>'.$this->options['cache_clear_eval_code'].'<?php ');

        return ($result = ob_get_clean());
    }

    /**
     * Clear (i.e., eval) custom code.
     *
     * @since 151002 Enhancing eval support.
     *
     * @param bool $manually True if wiping is done manually.
     * @param boolean $maybe Defaults to a true value.
     *
     * @return string Result from custom code.
     */
    public function clearEvalCode($manually = false, $maybe = true)
    {
        return $this->wipeEvalCode($manually, $maybe);
    }
}
/*[/pro]*/
