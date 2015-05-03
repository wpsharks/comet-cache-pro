<?php
namespace WebSharks\ZenCache\Pro;

/**
 * Abstract Base for Advanced Cache and Plugin.
 *
 * @since 150422 Rewrite.
 */
abstract class AbsBaseAp extends AbsBase
{
    /**
     * Identifies pro.
     *
     * @since 150422 Rewrite.
     *
     * @type bool `TRUE` for pro.
     */
    public $is_pro;

    /**
     * Plugin file path.
     *
     * @since 150422 Rewrite.
     *
     * @type string Plugin file path.
     */
    public $file;

    /**
     * Plugin slug.
     *
     * @since 150422 Rewrite.
     *
     * @type string Plugin slug.
     */
    public $slug;

    /**
     * Text domain.
     *
     * @since 150422 Rewrite.
     *
     * @type string Text domain.
     */
    public $text_domain;

    /**
     * Short name.
     *
     * @since 150422 Rewrite.
     *
     * @type string Short name.
     */
    public $short_name = SHORT_NAME;

    /**
     * Plugin name.
     *
     * @since 150422 Rewrite.
     *
     * @type string Plugin name.
     */
    public $name = NAME;

    /**
     * Domain name.
     *
     * @since 150422 Rewrite.
     *
     * @type string Domain name.
     */
    public $domain = DOMAIN;

    /**
     * Current version.
     *
     * @since 150422 Rewrite.
     *
     * @type string Current version.
     */
    public $version = VERSION;

    /**
     * Class constructor.
     *
     * @since 150422 Rewrite.
     */
    public function __construct()
    {
        parent::__construct();

        $ns_path      = str_replace('\\', '/', __NAMESPACE__);
        $this->is_pro = strtolower(basename($ns_path)) === 'pro';
        $this->file   = dirname(dirname(dirname(dirname(__FILE__)))).'/plugin.php';
        $this->slug   = $this->text_domain   = str_replace('_', '-', GLOBAL_NS);

        $closures_dir = dirname(dirname(__FILE__)).'/closures/both';
        $self         = $this; // Reference for closures.

        foreach (scandir($closures_dir) as $_closure) {
            if (substr($_closure, -4) === '.php') {
                require $closures_dir.'/'.$_closure;
            }
        }
        unset($_closure); // Housekeeping.
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
        throw new \Exception(sprintf(__('Undefined method/closure: `%1$s`.', $this->text_domain), $closure));
    }
}
