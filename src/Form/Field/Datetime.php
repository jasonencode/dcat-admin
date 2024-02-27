<?php

namespace Dcat\Admin\Form\Field;

class Datetime extends Date
{
    protected string $format = 'YYYY-MM-DD HH:mm:ss';

    public function render(): string
    {
        $this->defaultAttribute('style', 'width: 200px;flex:none');

        return parent::render();
    }
}
