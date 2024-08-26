<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use Throwable;

class Dropdown extends Widget
{
    const DIVIDER = '_divider';

    /**
     * @var string
     */
    protected static string $dividerHtml = '<li class="dropdown-divider"></li>';

    protected string $view = 'admin::widgets.dropdown';

    /**
     * @var array
     */
    protected array $button = [
        'text'  => null,
        'class' => 'btn btn-sm btn-white waves-effect',
        'style' => null,
    ];

    /**
     * @var string
     */
    protected string $buttonId;

    /**
     * @var Closure
     */
    protected Closure $builder;

    /**
     * @var bool
     */
    protected bool $divider;

    /**
     * @var bool
     */
    protected bool $click = false;

    /**
     * @var string
     */
    protected string $direction = 'down';

    public function __construct(array $options = [])
    {
        $this->options($options);
    }

    /**
     * Set the options of dropdown menus.
     *
     * @param  array|Arrayable  $options
     * @param  string|null  $title
     * @return $this
     */
    public function options(array|Arrayable $options = [], ?string $title = null): static
    {
        if (! $options) {
            return $this;
        }

        $this->options[] = [$title, Helper::array($options)];

        return $this;
    }

    /**
     * Set the button text.
     *
     * @param  string|null  $text
     * @return $this
     */
    public function button(?string $text): static
    {
        $this->button['text'] = $text;

        return $this;
    }

    /**
     * Set the button class.
     *
     * @param  string|null  $class
     * @return $this
     */
    public function buttonClass(?string $class): static
    {
        $this->button['class'] = $class;

        return $this;
    }

    /**
     * Set the button style.
     *
     * @param  string|null  $style
     * @return $this
     */
    public function buttonStyle(?string $style): static
    {
        $this->button['style'] = $style;

        return $this;
    }

    public function direction(string $direction = 'down'): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function up(): Dropdown|static
    {
        return $this->direction('up');
    }

    public function down(): Dropdown|static
    {
        return $this->direction();
    }

    /**
     * Show divider.
     *
     * @return $this
     */
    public function divider(): static
    {
        $this->divider = true;

        return $this;
    }

    /**
     * Applies the callback to the elements of the options.
     *
     * @param  Closure  $builder
     * @return $this
     */
    public function map(Closure $builder): static
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * Add click event listener.
     *
     * @param  string|null  $defaultLabel
     * @return $this
     */
    public function click(?string $defaultLabel = null): static
    {
        $this->click = true;

        $this->buttonId = 'dropd-'.Str::random(8);

        if ($defaultLabel !== null) {
            $this->button($defaultLabel);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getButtonId(): string
    {
        return $this->buttonId;
    }

    /**
     * @return string
     */
    protected function renderOptions(): string
    {
        $html = '';

        foreach ($this->options as $items) {
            [$title, $options] = $items;

            if ($title) {
                $html .= "<li class='dropdown-header'>$title</li>";
            }

            foreach ($options as $key => $val) {
                $html .= $this->renderOption($key, $val);
            }
        }

        return $html;
    }

    /**
     * @param  mixed  $k
     * @param  mixed  $v
     * @return string
     */
    protected function renderOption(mixed $k, mixed $v): string
    {
        if ($v === static::DIVIDER) {
            return static::$dividerHtml;
        }

        if ($builder = $this->builder) {
            $v = $builder->call($this, $v, $k);
        }

        $v = mb_strpos($v, '</a>') ? $v : "<a href='javascript:void(0)'>$v</a>";
        $v = "<li class='dropdown-item'>$v</li>";

        if ($this->divider) {
            $v .= static::$dividerHtml;
            $this->divider = null;
        }

        return $v;
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function render(): string
    {
        $this->addVariables([
            'options'   => $this->renderOptions(),
            'button'    => $this->button,
            'buttonId'  => $this->buttonId,
            'click'     => $this->click,
            'direction' => $this->direction,
        ]);

        return parent::render();
    }
}
