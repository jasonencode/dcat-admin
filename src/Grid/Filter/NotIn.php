<?php

namespace Dcat\Admin\Grid\Filter;

class NotIn extends In
{
    /**
     * {@inheritdoc}
     */
    protected string $query = 'whereNotIn';
}
