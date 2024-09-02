<?php

namespace Dcat\Admin\Form\Field;

use Closure;

class Email extends Text
{
    protected Closure|array $rules = ['nullable', 'email'];

    public function render(): string
    {
        $this->prepend('<i class="feather icon-mail"></i>')
            ->type('email');

        return parent::render();
    }
}
