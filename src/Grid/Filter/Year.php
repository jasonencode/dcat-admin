<?php

namespace Dcat\Admin\Grid\Filter;

class Year extends Date
{
    /**
     * {@inheritdoc}
     */
    protected string $query = 'whereYear';

    /**
     * @var string
     */
    protected string $fieldName = 'year';
}
