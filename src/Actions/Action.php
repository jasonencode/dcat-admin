<?php

namespace Dcat\Admin\Actions;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasHtmlAttributes;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;

/**
 * Class Action.
 *
 * @method string href
 */
abstract class Action implements Renderable
{
    use HasHtmlAttributes;
    use HasActionHandler;

    /**
     * @var array|string
     */
    protected array|string $primaryKey;

    /**
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * @var string
     */
    protected string $selector = '';

    /**
     * @var string
     */
    protected string $method = 'POST';

    /**
     * @var string
     */
    protected string $event = 'click';

    /**
     * @var bool
     */
    protected bool $disabled = false;

    /**
     * @var bool
     */
    protected bool $allowHandler = true;

    /**
     * @var array
     */
    protected array $htmlClasses = [];

    /**
     * Action constructor.
     *
     * @param  string|null  $title
     */
    public function __construct(?string $title = null)
    {
        if ($title) {
            $this->title = $title;
        }
    }

    /**
     * 是否禁用动作.
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disable(bool $disable = true): static
    {
        $this->disabled = $disable;

        return $this;
    }

    /**
     * @return bool
     */
    public function allowed(): bool
    {
        return ! $this->disabled;
    }

    /**
     * Get primary key value of action.
     *
     * @return array|string
     */
    public function getKey(): array|string
    {
        return $this->primaryKey;
    }

    /**
     * 设置主键.
     *
     * @param  mixed  $key
     * @return $this
     */
    public function setKey(mixed $key): static
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * @return string
     */
    protected function getElementClass(): string
    {
        return ltrim($this->selector(), '.');
    }

    /**
     * 获取动作标题.
     *
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function selector(): string
    {
        return $this->selector ?: ($this->selector = $this->makeSelector());
    }

    /**
     * 生成选择器.
     *
     * @return string
     */
    public function makeSelector(): string
    {
        return '.act-'.Str::random();
    }

    /**
     * @param  array|string  $class
     * @return $this
     */
    public function addHtmlClass(array|string $class): static
    {
        $this->htmlClasses = array_merge($this->htmlClasses, (array) $class);

        return $this;
    }

    /**
     * 需要执行的JS代码.
     *
     * @return string
     */
    protected function script(): string
    {
        return <<<JS
JS;
    }

    /**
     * @return string
     */
    protected function html(): string
    {
        $this->defaultHtmlAttribute('href', 'javascript:void(0)');

        return <<<HTML
<a {$this->formatHtmlAttributes()}>{$this->title()}</a>
HTML;
    }

    /**
     * @return void
     */
    protected function prepareHandler(): void
    {
        if (
            ! $this->allowHandler
            || ! method_exists($this, 'handle')
        ) {
            return;
        }

        $this->addHandlerScript();
    }

    /**
     * @return string
     */
    public function render(): string
    {
        if (! $this->allowed()) {
            return '';
        }

        $this->prepareHandler();

        $this->setUpHtmlAttributes();

        if ($script = $this->script()) {
            Admin::script($script);
        }

        return $this->html();
    }

    /**
     * @return string
     */
    protected function formatHtmlClasses(): string
    {
        return implode(' ', array_unique($this->htmlClasses));
    }

    /**
     * @return void
     */
    protected function setUpHtmlAttributes(): void
    {
        $this->addHtmlClass($this->getElementClass());

        $attributes = [
            'class' => $this->formatHtmlClasses(),
        ];

        if (method_exists($this, 'href') && ($href = $this->href())) {
            $this->allowHandler = false;

            $attributes['href'] = $href;
        }

        $this->defaultHtmlAttribute('style', 'cursor: pointer;');
        $this->setHtmlAttribute($attributes);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return Helper::render($this->render());
    }

    /**
     * @param  mixed  ...$params
     * @return $this
     */
    public static function make(...$params): static
    {
        return new static(...$params);
    }
}
