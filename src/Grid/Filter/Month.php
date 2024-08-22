<?php

namespace Dcat\Admin\Grid\Filter;

class Month extends Date
{
    /**
     * {@inheritdoc}
     */
    protected string $query = 'whereMonth';

    /**
     * @var string
     */
    protected string $fieldName = 'month';
}
