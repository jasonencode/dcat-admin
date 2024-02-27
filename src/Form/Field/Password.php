<?php

namespace Dcat\Admin\Form\Field;

class Password extends Text
{
    public function render(): string
    {
        $this->prepend('<i class="feather icon-eye"></i>')
            ->defaultAttribute('type', 'password');

        return parent::render();
    }
}
