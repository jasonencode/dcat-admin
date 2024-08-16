<?php

namespace Dcat\Admin\Grid\Filter;

class Newline extends AbstractFilter
{
    public function __construct()
    {
    }

    public function condition(array $inputs)
    {
    }

    public function render(): string
    {
        return '<div class="col-md-12"></div>';
    }
}
