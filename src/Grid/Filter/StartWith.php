<?php

namespace Dcat\Admin\Grid\Filter;

use Illuminate\Support\Arr;

class StartWith extends AbstractFilter
{
    protected string $type = 'like';

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

        return $this->buildCondition($this->column, $this->type, "$this->value%");
    }

    public function ilike(): static
    {
        $this->type = 'ilike';

        return $this;
    }
}
