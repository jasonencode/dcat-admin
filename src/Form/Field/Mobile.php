<?php

namespace Dcat\Admin\Form\Field;

class Mobile extends Text
{
    /**
     * @see https://github.com/RobinHerbots/Inputmask#options
     *
     * @var array
     */
    protected array $options = [
        'mask' => '99999999999',
    ];

    public function render(): string
    {
        $this->inputmask($this->options);

        $this->defaultAttribute('style', 'width: 160px;flex:none');

        $this->prepend('<i class="feather icon-smartphone"></i>');

        return parent::render();
    }
}
