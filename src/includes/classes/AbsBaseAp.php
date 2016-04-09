<?php
namespace WebSharks\CometCache\Pro\Classes;

use WebSharks\CometCache\Pro\Traits;
use WebSharks\CometCache\Pro\Interfaces;

/**
 * Abstract Base for Advanced Cache and Plugin.
 *
 * @since 150422 Rewrite.
 */
abstract class AbsBaseAp extends AbsBase implements Interfaces\Shared\NcDebugConsts, Interfaces\Shared\CachePathConsts
{
    /*[.build.php-auto-generate-use-Traits]*/
    /**
     * Class constructor.
     *
     * @since 150422 Rewrite.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Magic/overload property setter.
     *
     * @param string $property Property to set.
     * @param mixed  $value    The value for this property.
     *
     * @see http://php.net/manual/en/language.oop5.overloading.php
     */
    public function __set($property, $value)
    {
        $property          = (string) $property;
        $this->{$property} = $value;
    }

    /**
     * Closure overloading.
     *
     * @since 150422 Rewrite.
     */
    public function __call($closure, $args)
    {
        $closure = (string) $closure;

        if (isset($this->{$closure}) && is_callable($this->{$closure})) {
            return call_user_func_array($this->{$closure}, $args);
        }
        throw new \Exception(sprintf(__('Undefined method/closure: `%1$s`.', SLUG_TD), $closure));
    }
}
