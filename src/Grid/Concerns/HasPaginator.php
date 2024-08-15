<?php

namespace Dcat\Admin\Grid\Concerns;

use Dcat\Admin\Grid\Tools;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

trait HasPaginator
{
    /**
     * @var \Dcat\Admin\Grid\Tools\Paginator|null
     */
    protected Tools\Paginator|null $paginator = null;

    /**
     * Per-page options.
     *
     * @var array
     */
    protected array $perPages = [10, 20, 30, 50, 100, 200];

    /**
     * Default items count per-page.
     *
     * @var int
     */
    protected int $perPage = 20;

    /**
     * Paginate the grid.
     *
     * @param  int  $perPage
     * @return void
     */
    public function paginate(int $perPage = 20): void
    {
        $this->perPage = $perPage;

        $this->model()->setPerPage($perPage);
    }

    /**
     * 是否使用 simplePaginate 方法分页.
     *
     * @param  bool  $value
     * @return $this
     */
    public function simplePaginate(bool $value = true): static
    {
        $this->model()->simple($value);

        return $this;
    }

    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @param  string  $paginator
     * @return $this
     */
    public function setPaginatorClass(string $paginator): static
    {
        $this->options['paginator_class'] = $paginator;

        return $this;
    }

    /**
     * Get the grid paginator.
     *
     * @return \Dcat\Admin\Grid\Tools\Paginator
     */
    public function paginator(): Tools\Paginator
    {
        if (! $this->paginator) {
            $paginatorClass = $this->options['paginator_class'] ?: (config('admin.grid.paginator_class') ?: Tools\Paginator::class);

            $this->paginator = new $paginatorClass($this);
        }

        return $this->paginator;
    }

    /**
     * If this grid use pagination.
     *
     * @return bool
     */
    public function allowPagination(): bool
    {
        return $this->options['pagination'];
    }

    /**
     * Set per-page options.
     *
     * @param  array  $perPages
     * @return $this
     */
    public function perPages(array $perPages): static
    {
        $this->perPages = $perPages;

        return $this;
    }

    /**
     * @return $this
     */
    public function disablePerPages(): static
    {
        return $this->perPages([]);
    }

    /**
     * Get per-page options.
     *
     * @return array
     */
    public function getPerPages(): array
    {
        return $this->perPages;
    }

    /**
     * Disable grid pagination.
     *
     * @return $this
     */
    public function disablePagination(bool $disable = true): static
    {
        $this->model->usePaginate(! $disable);

        return $this->option('pagination', ! $disable);
    }

    /**
     * Show grid pagination.
     *
     * @param  bool  $val
     * @return $this
     */
    public function showPagination(bool $val = true): static
    {
        return $this->disablePagination(! $val);
    }

    /**
     * Notes   : 渲染分页
     *
     * @Date   : 2024/8/15 17:11
     * @Author : <Jason.C>
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function renderPagination(): Factory|View
    {
        return view('admin::grid.table-pagination', ['grid' => $this]);
    }
}
