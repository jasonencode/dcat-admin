<?php

namespace Dcat\Admin\Show;

class Newline extends Field
{
    public function render(): string
    {
        return '<div class="col-sm-12"></div>';
    }
}
