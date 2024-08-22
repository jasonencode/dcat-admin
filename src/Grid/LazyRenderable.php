<?php

namespace Dcat\Admin\Grid;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\LazyRenderable as Renderable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

abstract class LazyRenderable extends Renderable
{
    const SIMPLE_NAME = '_simple_';

    const ROW_SELECTOR_COLUMN_NAME = '_row_columns_';

    /**
     * 是否启用简化模式.
     *
     * @var bool
     */
    protected bool $simple = false;

    /**
     * 创建表格.
     *
     * @return Grid
     */
    abstract public function grid(): Grid;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function render(): string
    {
        $this->addStyle();

        $class = $this->allowSimpleMode() ? 'simple-grid' : null;

        return <<<HTML
            <div class="$class">{$this->prepare($this->grid())->render()}</div>
            HTML;
    }

    protected function addStyle(): void
    {
        Admin::style('.select2-container--open{z-index:29891015}');
    }

    /**
     * 是否启用简化模式.
     *
     * @param  bool  $value
     * @return $this
     */
    public function simple(bool $value = true): static
    {
        return $this->payload([static::SIMPLE_NAME => $value]);
    }

    /**
     * @param  Grid  $grid
     * @return Grid
     */
    protected function prepare(Grid $grid): Grid
    {
        if (!$grid->getName()) {
            $grid->setName($this->getDefaultName());
        }

        if ($this->allowSimpleMode()) {
            $grid->disableCreateButton();
            $grid->disablePerPages();
            $grid->disableBatchDelete();
            $grid->disableRefreshButton();

            $grid->filter()
                ->panel()
                ->view('admin::filter.simple-container');

            $grid->rowSelector()->click();
        }

        if (!empty($this->payload[static::ROW_SELECTOR_COLUMN_NAME])) {
            [$key, $visibleColumn] = $this->payload[static::ROW_SELECTOR_COLUMN_NAME];

            $key && $grid->rowSelector()->idColumn($key);

            $visibleColumn && $grid->rowSelector()->titleColumn($visibleColumn);
        }

        return $grid->async(false);
    }

    /**
     * 判断是否启用简化模式.
     *
     * @return bool
     */
    public function allowSimpleMode(): bool
    {
        return $this->simple || $this->_simple_;
    }

    /**
     * 获取默认名称.
     *
     * @return string
     */
    protected function getDefaultName(): string
    {
        return strtolower(str_replace('\\', '-', static::class));
    }
}
