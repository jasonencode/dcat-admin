<?php

namespace Dcat\Admin\Form\Field;

class Rate extends Text
{
    public function render(): string
    {
        $this->prepend('%')->defaultAttribute('placeholder', 0);

        return parent::render();
    }
}
