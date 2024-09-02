<?php

namespace Dcat\Admin\Form\Field;

use Closure;

class Ip extends Text
{
    protected Closure|array $rules = ['nullable', 'ip'];

    /**
     * @see https://github.com/RobinHerbots/Inputmask#options
     *
     * @var array
     */
    protected array $options = [
        'alias' => 'ip',
    ];

    public function render(): string
    {
        $this->inputmask($this->options);

        $this->defaultAttribute('style', 'width: 160px;flex:none');

        $this->prepend('<i class="fa fa-laptop fa-fw"></i>')
            ->defaultAttribute('style', 'width: 200px');

        return parent::render();
    }
}
