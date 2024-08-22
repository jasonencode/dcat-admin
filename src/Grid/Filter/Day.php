<?php

namespace Dcat\Admin\Grid\Filter;

class Day extends Date
{
    protected string $query = 'whereDay';

    /**
     * @var string
     */
    protected string $fieldName = 'day';
}
