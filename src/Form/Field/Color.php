<?php

namespace Dcat\Admin\Form\Field;

class Color extends Text
{
    protected $view = 'admin::form.color';

    /**
     * Use `hex` format.
     *
     * @return $this
     */
    public function hex(): static
    {
        return $this->mergeOptions(['format' => 'hex']);
    }

    /**
     * Use `rgb` format.
     *
     * @return $this
     */
    public function rgb(): static
    {
        return $this->mergeOptions(['format' => 'rgb']);
    }

    /**
     * Use `rgba` format.
     *
     * @return $this
     */
    public function rgba(): static
    {
        return $this->mergeOptions(['format' => 'rgba']);
    }

    /**
     * Render this filed.
     *
     * @return string
     */
    public function render(): string
    {
        $this->defaultAttribute('style', 'width: 160px;flex:none');

        return parent::render();
    }
}
