<?php

namespace Dcat\Admin\Grid\Filter;

use Illuminate\Support\Arr;

class Ngt extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected string $view = 'admin::filter.gt';

    /**
     * Get condition of this filter.
     *
     * @param  array  $inputs
     * @return array|mixed|void
     */
    public function condition(array $inputs)
    {
        $value = Arr::get($inputs, $this->column);

        if ($value === null) {
            return;
        }

        $this->value = $value;

        return $this->buildCondition($this->column, '<=', $this->value);
    }
}
