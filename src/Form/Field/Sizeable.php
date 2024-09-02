<?php

namespace Dcat\Admin\Form\Field;

trait Sizeable
{
    /**
     * @var string
     */
    protected string $size = '';

    /**
     * 设置为小尺寸.
     *
     * @return $this
     */
    public function small(): static
    {
        return $this->size('sm');
    }

    /**
     * 设置为大尺寸.
     *
     * @return $this
     */
    public function large(): static
    {
        return $this->size('lg');
    }

    public function size(?string $size): static
    {
        $this->size = $size;

        return $this;
    }

    protected function initSize(): void
    {
        if ($this->size) {
            $this->addElementClass('form-control-'.$this->size);
            $this->setLabelClass('control-label-'.$this->size);
        }
    }
}
