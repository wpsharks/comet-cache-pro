<?php
/*[pro strip-from="lite"]*/
namespace WebSharks\ZenCache\Pro;

/*
 * Wipe (i.e., eval) custom code.
 *
 * @since 15xxxx Enhancing eval support.
 *
 * @param bool $manually True if wiping is done manually.
 * @param boolean $maybe Defaults to a true value.
 *
 * @return string Result from custom code.
 */
$self->wipeEvalCode = function ($manually = false, $maybe = true) use ($self) {
    $result = ''; // Initialize result.

    if ($maybe && !$self->options['cache_clear_eval_code']) {
        return $result; // Not enabled at this time.
    }
    if (!$self->options['cache_clear_eval_code']) {
        return $result; // Nothing to eval.
    }
    if (!$self->functionIsPossible('eval')) {
        return $result; // Not possible.
    }
    ob_start(); // Buffer output from PHP code.
    eval('?>'.$self->options['cache_clear_eval_code'].'<?php ');

    return ($result = ob_get_clean());
};

/*
 * Clear (i.e., eval) custom code.
 *
 * @since 15xxxx Enhancing eval support.
 *
 * @param bool $manually True if wiping is done manually.
 * @param boolean $maybe Defaults to a true value.
 *
 * @return string Result from custom code.
 */
$self->clearEvalCode = function ($manually = false, $maybe = true) use ($self) {
    return $self->wipeEvalCode($manually, $maybe);
};
/*[/pro]*/
