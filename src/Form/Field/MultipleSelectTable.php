<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Support\Helper;

class MultipleSelectTable extends SelectTable
{
    public static $css = [
        '@select2',
    ];

    protected $view = 'admin::form.selecttable';

    /**
     * @var int
     */
    protected int $max = 0;

    /**
     * 设置最大选择数量.
     *
     * @param  int  $max
     * @return $this
     */
    public function max(int $max): static
    {
        $this->max = $max;

        return $this;
    }

    /**
     * 转化为数组格式保存.
     *
     * @param  mixed  $value
     * @return array
     */
    public function prepareInputValue($value): array
    {
        return Helper::array($value);
    }

    public function render(): string
    {
        $this->addVariables(['max' => $this->max]);

        return parent::render();
    }
}
