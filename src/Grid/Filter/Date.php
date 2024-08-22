<?php

namespace Dcat\Admin\Grid\Filter;

class Date extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected string $query = 'whereDate';

    /**
     * @var string
     */
    protected string $fieldName = 'date';

    /**
     * {@inheritdoc}
     */
    public function __construct($column, $label = '')
    {
        parent::__construct($column, $label);

        $this->{$this->fieldName}();
    }
}
