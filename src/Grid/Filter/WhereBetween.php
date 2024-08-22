<?php

namespace Dcat\Admin\Grid\Filter;

use Closure;
use Illuminate\Support\Arr;

class WhereBetween extends Between
{
    /**
     * {@inheritdoc}
     */
    protected string $view = 'admin::filter.between';

    /**
     * Query closure.
     *
     * @var Closure
     */
    protected Closure $where;

    /**
     * Input value from presenter.
     *
     * @var mixed
     */
    public mixed $input;

    /**
     * Where constructor.
     *
     * @param  string  $column
     * @param  Closure  $query
     * @param  string  $label
     */
    public function __construct(string $column, Closure $query, string $label = '')
    {
        $this->where = $query;
        $this->column = $column;
        $this->label = $this->formatLabel($label);
    }

    /**
     * Get condition of this filter.
     *
     * @param  array  $inputs
     * @return array|string|void
     */
    public function condition(array $inputs)
    {
        $value = Arr::get($inputs, $this->column) ?: [];

        if (
            ! $value
            || (! isset($value['start']) && ! isset($value['end']))
        ) {
            return;
        }

        $this->input = $this->value = $value;

        return $this->buildCondition($this->where->bindTo($this));
    }
}
