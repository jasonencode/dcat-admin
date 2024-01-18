<?php

namespace Dcat\Admin\Form\Field;

class Time extends Date
{
    protected string $format = 'HH:mm:ss';

    public function render(): string
    {
        $this->prepend('<i class="fa fa-clock-o fa-fw"></i>')
            ->defaultAttribute('style', 'width: 200px;flex:none');

        return parent::render();
    }
}
