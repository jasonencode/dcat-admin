<?php

namespace Dcat\Admin\Grid\Filter;

use Illuminate\Support\Arr;

class In extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected string $query = 'whereIn';

    /**
     * @var int
     */
    protected int $width = 12;

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

        $this->value = is_array($value) ? $value : explode(',', $value);

        return $this->buildCondition($this->column, $this->value);
    }
}
