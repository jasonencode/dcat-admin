<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Illuminate\Support\Collection;

class Number extends Text
{
    protected string $view = 'admin::form.number';

    protected Collection|Closure|array $options = [
        'upClass'   => 'primary shadow-0',
        'downClass' => 'light shadow-0',
        'center'    => true,
        'disabled'  => false,
    ];

    /**
     * Set min value of number field.
     *
     * @param  int  $value
     * @return $this
     */
    public function min(int $value): static
    {
        $this->attribute('min', $value);

        return $this;
    }

    /**
     * Set max value of number field.
     *
     * @param  int  $value
     * @return $this
     */
    public function max(int $value): static
    {
        $this->attribute('max', $value);

        return $this;
    }

    /**
     * Set increment and decrement button to disabled.
     *
     * @param  bool  $value
     * @return $this
     */
    public function disable(bool $value = true): static
    {
        parent::disable($value);

        $this->options['disabled'] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInputValue(mixed $value): mixed
    {
        return empty($value) ? 0 : $value;
    }

    /**
     * {@inheritDoc}
     */
    public function value(mixed $value = null): string|null|static
    {
        if (is_null($value)) {
            return (string) parent::value();
        }

        return parent::value($value);
    }

    public function render(): string
    {
        $this->defaultAttribute('style', 'width: 140px;flex:none');

        $this->prepend('');

        return parent::render();
    }
}
