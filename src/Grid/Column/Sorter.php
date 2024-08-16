<?php

namespace Dcat\Admin\Grid\Column;

use Dcat\Admin\Grid;
use Illuminate\Contracts\Support\Renderable;

class Sorter implements Renderable
{
    protected Grid $grid;

    protected array $sort;

    protected string $cast;

    protected string $columnName;

    /**
     * Sorter constructor.
     *
     * @param  Grid  $grid
     * @param  string  $columnName
     * @param  string  $cast
     */
    public function __construct(Grid $grid, string $columnName, string $cast)
    {
        $this->grid       = $grid;
        $this->columnName = $columnName;
        $this->cast       = $cast;
    }

    /**
     * Determine if this column is currently sorted.
     *
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function isSorted(): bool
    {
        $this->sort = app('request')->get($this->getSortName());

        if (empty($this->sort)) {
            return false;
        }

        return isset($this->sort['column']) && $this->sort['column'] == $this->columnName;
    }

    protected function getSortName(): string
    {
        return $this->grid->model()->getSortName();
    }

    /**
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function render(): string
    {
        $type   = 'desc';
        $icon   = 'down';
        $active = '';

        if ($this->isSorted()) {
            $type   = $this->sort['type'] == 'desc' ? 'asc' : 'desc';
            $active = 'active';

            if ($this->sort['type'] === 'asc') {
                $icon = 'up';
            }
        }

        $sort = ['column' => $this->columnName, 'type' => $type];

        if ($this->cast) {
            $sort['cast'] = $this->cast;
        }

        if (! $this->isSorted() || $this->sort['type'] != 'asc') {
            $url = request()->fullUrlWithQuery([
                $this->getSortName() => $sort,
            ]);
        } else {
            $url = request()->fullUrlWithQuery([
                $this->getSortName() => [],
            ]);
        }

        return "&nbsp;<a href='$url' class='grid-sort feather icon-arrow-$icon $active'></a>";
    }
}
