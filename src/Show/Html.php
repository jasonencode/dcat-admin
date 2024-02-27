<?php

namespace Dcat\Admin\Show;

use Dcat\Admin\Support\Helper;

class Html extends Field
{
    public $html;

    public function __construct($html)
    {
        $this->html = $html;
    }

    public function render(): string
    {
        return Helper::render($this->html, [$this->value()], $this->parent->model());
    }
}
