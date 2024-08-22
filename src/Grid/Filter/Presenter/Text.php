<?php

namespace Dcat\Admin\Grid\Filter\Presenter;

use Dcat\Admin\Admin;

class Text extends Presenter
{
    /**
     * @var string
     */
    protected string $placeholder = '';

    /**
     * @var string
     */
    protected string $icon = 'pencil';

    /**
     * @var string
     */
    protected string $type = 'text';

    /**
     * Text constructor.
     *
     * @param  string  $placeholder
     */
    public function __construct(string $placeholder = '')
    {
        $this->placeholder($placeholder);
    }

    /**
     * Get variables for field template.
     *
     * @return array
     */
    public function defaultVariables(): array
    {
        return [
            'placeholder' => $this->placeholder,
            'icon'        => $this->icon,
            'type'        => $this->type,
            'group'       => $this->filter->group,
        ];
    }

    /**
     * Set input placeholder.
     *
     * @param  string  $placeholder
     * @return $this
     */
    public function placeholder(string $placeholder = ''): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return Text
     */
    public function url(): static
    {
        return $this->inputmask(['alias' => 'url'], 'internet-explorer');
    }

    /**
     * @return Text
     */
    public function email(): static
    {
        return $this->inputmask(['alias' => 'email'], 'envelope');
    }

    /**
     * @return Text
     */
    public function integer(): static
    {
        return $this->inputmask(['alias' => 'integer']);
    }

    /**
     * @param  array  $options
     *
     * @return Text
     *@see https://github.com/RobinHerbots/Inputmask/blob/4.x/README_numeric.md
     *
     */
    public function decimal(array $options = []): static
    {
        return $this->inputmask(array_merge($options, ['alias' => 'decimal']));
    }

    /**
     * @param  array  $options
     *
     * @return Text
     *@see https://github.com/RobinHerbots/Inputmask/blob/4.x/README_numeric.md
     *
     */
    public function currency(array $options = []): static
    {
        return $this->inputmask(array_merge($options, [
            'alias'              => 'currency',
            'prefix'             => '',
            'removeMaskOnSubmit' => true,
        ]));
    }

    /**
     * @param  array  $options
     *
     * @return Text
     *@see https://github.com/RobinHerbots/Inputmask/blob/4.x/README_numeric.md
     *
     */
    public function percentage(array $options = []): static
    {
        $options = array_merge(['alias' => 'percentage'], $options);

        return $this->inputmask($options);
    }

    /**
     * @return Text
     */
    public function ip(): static
    {
        return $this->inputmask(['alias' => 'ip'], 'laptop');
    }

    /**
     * @return Text
     */
    public function mac(): static
    {
        return $this->inputmask(['alias' => 'mac'], 'laptop');
    }

    /**
     * @param  string  $mask
     * @return Text
     */
    public function mobile(string $mask = '19999999999'): static
    {
        return $this->inputmask(compact('mask'), 'phone');
    }

    /**
     * @param  array  $options
     * @param  string  $icon
     * @return $this
     */
    public function inputmask(array $options = [], string $icon = 'pencil'): static
    {
        Admin::js('@jquery.inputmask');

        $options['rightAlign'] = false;

        $options = json_encode($options);

        Admin::script("$('#{$this->filter->parent()->filterID()} input.{$this->filter->getId()}').inputmask($options);");

        $this->icon = $icon;

        return $this;
    }
}
