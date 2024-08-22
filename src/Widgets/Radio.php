<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Throwable;

class Radio extends Widget
{
    protected string $view = 'admin::widgets.radio';
    protected $type = 'radio';
    protected string $style = 'primary';
    protected string $right = '16px';
    protected $checked;
    protected array $disabledValues = [];
    protected $size;
    protected bool $inline = false;

    public function __construct(
        ?string $name = null,
        array $options = [],
        string $style = 'primary'
    ) {
        $this->name($name);
        $this->options($options);
        $this->style($style);
    }

    /**
     * 设置表单 "name" 属性.
     *
     * @param  string|null  $name
     * @return $this
     */
    public function name(?string $name): static
    {
        return $this->setHtmlAttribute('name', $name);
    }

    /**
     * 尺寸设置.
     *
     * "sm", "lg"
     *
     * @param  string  $size
     * @return $this
     */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * 是否排成一行.
     *
     * @param  bool  $inline
     * @return $this
     */
    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * 设置禁选的选项.
     *
     * @param  array|string|null  $values
     * @return $this
     */
    public function disable(array|string $values = null): static
    {
        if ($values) {
            $this->disabledValues = (array) $values;

            return $this;
        }

        return $this->setHtmlAttribute('disabled', 'disabled');
    }

    /**
     * 设置 "margin-right" 样式.
     *
     * @param  string  $value
     * @return $this
     */
    public function right(string $value): static
    {
        $this->right = $value;

        return $this;
    }

    /**
     * 设置选中的选项.
     *
     * @param $option
     * @return $this
     */
    public function check($option): static
    {
        $this->checked = $option;

        return $this;
    }

    /**
     * 设置选项的名称和值.
     *
     * eg: $opts = [
     *         1 => 'foo',
     *         2 => 'bar',
     *         ...
     *     ]
     *
     * @param  array|Collection  $options
     * @return $this
     */
    public function options(array|Collection $options = []): static
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }
        $this->options = $options;

        return $this;
    }

    /**
     * 设置样式.
     *
     * 支持 "info", "primary", "danger", "success".
     *
     * @param  string  $style
     * @return $this
     */
    public function style(string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return array
     */
    public function defaultVariables(): array
    {
        return [
            'style' => $this->style,
            'options' => $this->options,
            'attributes' => $this->formatHtmlAttributes(),
            'checked' => $this->checked,
            'disabled' => $this->disabledValues,
            'right' => $this->right,
            'size' => $this->size,
            'inline' => $this->inline,
        ];
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function render(): string
    {
        $this->setHtmlAttribute('type', $this->type);

        return parent::render();
    }
}
