<?php

namespace Dcat\Admin\Contracts\Grid;

use Dcat\Admin\Grid;

interface ColumnSelectorStore
{
    /**
     * @param  Grid  $grid
     * @return static
     */
    public function setGrid(Grid $grid): static;

    /**
     * 存储数据.
     *
     * @param  array  $input
     * @return void
     */
    public function store(array $input): void;

    /**
     * 获取数据.
     *
     * @return array|null
     */
    public function get(): ?array;

    /**
     * 移除.
     *
     * @return void
     */
    public function forget(): void;
}
