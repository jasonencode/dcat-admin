<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Widgets\Checkbox;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ColumnSelector extends AbstractTool
{
    const SELECT_COLUMN_NAME = '_columns_';

    /**
     * @var Grid
     */
    protected Grid $grid;

    /**
     * @var array
     */
    protected array $ignoredColumns = [
        Grid\Column::SELECT_COLUMN_NAME,
        Grid\Column::ACTION_COLUMN_NAME,
    ];

    /**
     * Create a new Export button instance.
     *
     * @param  Grid  $grid
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function render(): string
    {
        $show = $this->getVisibleColumnNames();
        $all = $this->getGridColumns();

        $visibleColumnNames = $this->grid->getVisibleColumnsFromQuery();

        $list = Checkbox::make()
            ->class('column-select-item')
            ->options($all)
            ->check($visibleColumnNames);

        $selectAll = Checkbox::make('_all_', [1 => trans('admin.all')])->check(
            $all->count() === count($show) ? 1 : null
        );

        return Admin::view('admin::grid.column-selector', [
            'checkbox' => $list,
            'defaults' => $visibleColumnNames,
            'selectAll' => $selectAll,
            'columnName' => $this->grid->getColumnSelectorQueryName(),
        ]);
    }

    /**
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getVisibleColumnNames(): array
    {
        $visible = $this->grid->getVisibleColumnsFromQuery();

        $columns = $this->grid->getComplexHeaderNames() ?: $this->grid->getColumnNames();

        if (!empty($visible)) {
            array_push($visible, Grid\Column::SELECT_COLUMN_NAME, Grid\Column::ACTION_COLUMN_NAME);

            $columns = collect($columns)->filter(function ($column) use ($visible) {
                return in_array($column, $visible);
            })->toArray();
        }

        return array_filter($columns, function ($v) {
            return !in_array($v, [Grid\Column::SELECT_COLUMN_NAME, Grid\Column::ACTION_COLUMN_NAME]);
        });
    }

    /**
     * @return Collection
     */
    protected function getGridColumns(): Collection
    {
        $columns = $this->grid->getComplexHeaders() ?: $this->grid->columns();

        return $columns->map(function ($column) {
            $name = $column->getName();

            if ($this->isColumnIgnored($name)) {
                return;
            }

            return [$name => $column->getLabel()];
        })->filter()->collapse();
    }

    /**
     * Is column ignored in column selector.
     *
     * @param  string  $name
     * @return bool
     */
    protected function isColumnIgnored(string $name): bool
    {
        return in_array($name, $this->ignoredColumns);
    }

    /**
     * Ignore a column to display in column selector.
     *
     * @param  array|string  $name
     * @return $this
     */
    public function ignore(array|string $name): static
    {
        $this->ignoredColumns = array_merge($this->ignoredColumns, (array) $name);

        return $this;
    }
}
