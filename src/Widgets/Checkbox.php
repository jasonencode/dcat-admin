<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Support\Helper;
use Illuminate\Support\Arr;

class Checkbox extends Radio
{
    protected string $view = 'admin::widgets.checkbox';
    protected string $type = 'checkbox';
    protected $checked = [];

    /**
     * 设置选中的的选项.
     *
     * @param  string|array  $option
     * @return $this
     */
    public function check($option): static
    {
        $this->checked = Helper::array($option);

        return $this;
    }

    /**
     * 选中所有选项.
     *
     * @param  array|string  $excepts
     * @return $this
     */
    public function checkAll(array|string $excepts = []): static
    {
        return $this->check(
            array_keys(Arr::except($this->options, $excepts))
        );
    }
}
