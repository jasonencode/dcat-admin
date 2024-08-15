<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Pagination\LengthAwarePaginator;

class Paginator implements Renderable
{
    /**
     * @var Grid
     */
    protected Grid $grid;

    /**
     * @var \Illuminate\Pagination\LengthAwarePaginator|null
     */
    public ?LengthAwarePaginator $paginator = null;

    /**
     * Create a new Paginator instance.
     *
     * @param  Grid  $grid
     * @throws \Exception
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;

        $this->initPaginator();
    }

    /**
     * Initialize work for Paginator.
     *
     * @return void
     * @throws \Exception
     */
    protected function initPaginator(): void
    {
        $this->paginator = $this->grid->model()->paginator();

        if ($this->paginator instanceof LengthAwarePaginator) {
            $this->paginator->appends(request()->all());
        }
    }

    /**
     * Get Pagination links.
     *
     * @return string
     */
    protected function paginationLinks(): string
    {
        return $this->paginator->render('admin::grid.pagination');
    }

    /**
     * Get per-page selector.
     *
     * @return string
     */
    protected function perPageSelector(): string
    {
        if (! $this->grid->getPerPages()) {
            return '';
        }

        return (new PerPageSelector($this->grid))->render();
    }

    /**
     * Get range infomation of paginator.
     */
    protected function paginationRanger(): string
    {
        $parameters = [
            'first' => $this->paginator->firstItem(),
            'last'  => $this->paginator->lastItem(),
            'total' => method_exists($this->paginator, 'total') ? $this->paginator->total() : '...',
        ];

        $parameters = collect($parameters)->flatMap(function ($parameter, $key) {
            return [$key => "<b>$parameter</b>"];
        });

        $color = Admin::color()->dark80();

        return "<span class='d-none d-sm-inline' style=\"line-height:33px;color:{$color}\">".trans('admin.pagination.range', $parameters->all()).'</span>';
    }

    /**
     * Render Paginator.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->paginationRanger().
            $this->paginationLinks().
            $this->perPageSelector();
    }
}
