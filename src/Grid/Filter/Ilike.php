<?php

namespace Dcat\Admin\Grid\Filter;

use Illuminate\Support\Arr;

class Ilike extends AbstractFilter
{
    /**
     * Get condition of this filter.
     *
     * @param  array  $inputs
     * @return array|string|void
     */
    public function condition(array $inputs)
    {
        $value = Arr::get($inputs, $this->column);

        if ($value === null) {
            return;
        }

        $this->value = $value;

        return $this->buildCondition($this->column, 'ilike', "%$this->value%");
    }
}
