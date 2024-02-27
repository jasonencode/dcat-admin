<?php

namespace Dcat\Admin\Form\Field;

class Tel extends Text
{
    public function render(): string
    {
        $this->prepend('<i class="fa fa-phone fa-fw"></i>')
            ->defaultAttribute('type', 'tel');

        return parent::render();
    }
}
