<?php

namespace Dcat\Admin\Traits;

use Dcat\Admin\Layout\Asset;

trait HasAssets
{
    /**
     * @return \Dcat\Admin\Layout\Asset
     */
    public static function asset(): Asset
    {
        return app('admin.asset');
    }

    /**
     * @param  array|string  $name
     * @param  array  $params
     * @return void
     */
    public static function requireAssets(array|string $name, array $params = []): void
    {
        static::asset()->require($name, $params);
    }

    /**
     * Add css.
     *
     * @param  array|string|null  $css
     * @return void
     */
    public static function css(array|string|null $css): void
    {
        static::asset()->css($css);
    }

    /**
     * Set base css.
     *
     * @param  array  $css
     * @param  bool  $merge
     */
    public static function baseCss(array $css, bool $merge = true): void
    {
        static::asset()->baseCss($css, $merge);
    }

    /**
     * Add js.
     *
     * @param  array|string|null  $js
     * @return void
     */
    public static function js(array|string|null $js): void
    {
        static::asset()->js($js);
    }

    /**
     * Add js.
     *
     * @param  array|string|null  $js
     * @param  bool  $merge
     * @return void
     */
    public static function headerJs(array|string|null $js, bool $merge = true): void
    {
        static::asset()->headerJs($js, $merge);
    }

    /**
     * Set base js.
     *
     * @param  array  $js
     * @param  bool  $merge
     */
    public static function baseJs(array $js, bool $merge = true): void
    {
        static::asset()->baseJs($js, $merge);
    }

    /**
     * @param  array|string|null  $script
     * @param  bool  $direct
     * @return void
     */
    public static function script(array|string|null $script, bool $direct = false): void
    {
        static::asset()->script($script, $direct);
    }

    /**
     * @param  string|null  $style
     * @return void
     */
    public static function style(?string $style): void
    {
        static::asset()->style($style);
    }

    /**
     * @param  array|string  $font
     * @return void
     */
    public static function fonts(array|string $font): void
    {
        static::asset()->fonts = $font;
    }
}
