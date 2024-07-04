<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Grid\LazyRenderable as LazyGrid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasHtmlAttributes;
use Dcat\Admin\Traits\HasVariables;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;

/**
 * @method $this class(array|string $class, bool $append = false)
 * @method $this style(string $style, bool $append = true)
 * @method $this id(string $id = null)
 */
abstract class Widget implements Renderable
{
    use HasHtmlAttributes;
    use HasVariables;

    /**
     * @var array
     */
    public static array $css = [];

    /**
     * @var array
     */
    public static array $js = [];

    /**
     * @var string
     */
    protected string $view = '';

    /**
     * @var string
     */
    protected string $script = '';

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @var string
     */
    protected string $elementClass = '';

    /**
     * @var bool
     */
    protected bool $runScript = true;

    /**
     * @param  mixed  ...$params
     * @return static
     */
    public static function make(...$params): static
    {
        return new static(...$params);
    }

    /**
     * 符合条件则执行.
     *
     * @param  mixed  $value
     * @param  callable  $callback
     * @return mixed
     */
    public function when(mixed $value, callable $callback): mixed
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * 批量设置选项.
     *
     * @param  array  $options
     * @return $this
     */
    public function options(array $options = []): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * 设置或获取配置选项.
     *
     * @param  string  $key
     * @param  mixed|null  $value
     * @return $this
     */
    public function option(string $key, mixed $value = null): static
    {
        if ($value === null) {
            return Arr::get($this->options, $key);
        }

        Arr::set($this->options, $key, $value);

        return $this;
    }

    /**
     * 获取所有选项.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 获取视图变量.
     *
     * @return array
     */
    public function defaultVariables(): array
    {
        return [
            'attributes' => $this->formatHtmlAttributes(),
            'options'    => $this->options,
            'class'      => $this->getElementClass(),
            'selector'   => $this->getElementSelector(),
        ];
    }

    /**
     * 收集静态资源.
     */
    public static function requireAssets(): void
    {
        static::$js && Admin::js(static::$js);
        static::$css && Admin::css(static::$css);
    }

    /**
     * 运行JS.
     */
    protected function withScript(): void
    {
        if ($this->runScript && $this->script) {
            Admin::script($this->script);
        }
    }

    /**
     * @param $value
     * @return string
     */
    protected function toString($value): string
    {
        return Helper::render($value);
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function render(): string
    {
        static::requireAssets();

        $this->class($this->getElementClass(), true);

        $html = $this->html();

        $this->withScript();

        return $html;
    }

    /**
     * 获取元素选择器.
     *
     * @return string
     */
    public function getElementSelector(): string
    {
        return '.'.$this->getElementClass();
    }

    /**
     * @param  string  $elementClass
     * @return $this
     */
    public function setElementClass(string $elementClass): static
    {
        $this->elementClass = $elementClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getElementClass(): string
    {
        return $this->elementClass ?: str_replace('\\', '_', static::class);
    }

    /**
     * 渲染HTML.
     *
     * @return string
     * @throws \Throwable
     */
    public function html(): string
    {
        if (! $this->view) {
            return '';
        }

        $result = Admin::resolveHtml(view($this->view, $this->variables()), ['runScript' => $this->runScript]);

        $this->script .= $result['script'];

        return $result['html'];
    }

    /**
     * 自动调用render方法.
     *
     * @return void
     */
    protected function autoRender(): void
    {
        Content::composed(function () {
            if ($results = Helper::render($this->render())) {
                Admin::html($results);
            }
        });
    }

    /**
     * 设置模板.
     *
     * @param  string  $view
     */
    public function view(string $view): void
    {
        $this->view = $view;
    }

    /**
     * 设置是否执行JS代码.
     *
     * @param  bool  $run
     * @return $this
     */
    public function runScript(bool $run = true): static
    {
        $this->runScript = $run;

        return $this;
    }

    /**
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * @param  mixed  $content
     * @return Lazy|LazyTable|mixed
     */
    protected function formatRenderable(mixed $content): mixed
    {
        if ($content instanceof LazyGrid) {
            return LazyTable::make($content);
        }

        if ($content instanceof LazyRenderable) {
            return Lazy::make($content);
        }

        return $content;
    }

    /**
     * @param $method
     * @param $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        if ($method === 'style' || $method === 'class') {
            $value  = $parameters[0] ?? null;
            $append = $parameters[1] ?? ! ($method === 'class');

            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            if ($append) {
                $original = $this->htmlAttributes[$method] ?? '';

                $de = $method === 'style' ? ';' : ' ';

                $value = $original.$de.$value;
            }

            return $this->setHtmlAttribute($method, $value);
        }

        // 获取属性
        if (count($parameters) === 0 || $parameters[0] === null) {
            return $this->getHtmlAttribute($method);
        }

        // 设置属性
        $this->setHtmlAttribute($method, $parameters[0]);

        return $this;
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->htmlAttributes[$key] ?? null;
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set(string $key, mixed $value)
    {
        $this->htmlAttributes[$key] = $value;
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
