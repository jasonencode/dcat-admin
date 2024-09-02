<?php

namespace Dcat\Admin\Form\Field;

use Closure;

class Url extends Text
{
    protected Closure|array $rules = ['nullable', 'url'];

    public function render(): string
    {
        $this->prepend('<i class="fa fa-internet-explorer fa-fw"></i>')
            ->defaultAttribute('type', 'url');

        return parent::render();
    }
}
