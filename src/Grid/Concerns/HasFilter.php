<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Collection;
use Illuminate\View\View;

trait HasFilter
{
    /**
     * The grid Filter.
     *
     * @var Grid\Filter
     */
    protected Grid\Filter $filter;

    /**
     * 初始化过滤器
     *
     * @return void
     */
    protected function setUpFilter(): void
    {
        $this->filter = new Grid\Filter($this->model());
    }

    /**
     * Process the grid filter.
     *
     * @return Collection
     * @throws \Exception
     */
    public function processFilter(): Collection
    {
        $this->callBuilder();
        $this->handleExportRequest();

        $this->applyQuickSearch();
        $this->applyColumnFilter();
        $this->applySelectorQuery();

        return $this->filter->execute();
    }

    /**
     * Get or set the grid filter.
     *
     * @param \Closure|null $callback
     * @return $this|Grid\Filter
     */
    public function filter(Closure $callback = null): Grid\Filter|static
    {
        if ($callback === null) {
            return $this->filter;
        }

        call_user_func($callback, $this->filter);

        return $this;
    }

    /**
     * Render the grid filter.
     *
     * @throws \Throwable
     */
    public function renderFilter(): string|View
    {
        if (!$this->options['filter']) {
            return '';
        }

        return $this->filter->render();
    }

    /**
     * Expand filter.
     *
     * @return $this
     */
    public function expandFilter(): static
    {
        $this->filter->expand();

        return $this;
    }

    /**
     * Disable grid filter.
     *
     * @return $this
     */
    public function disableFilter(bool $disable = true): static
    {
        $this->filter->disableCollapse($disable);

        return $this->option('filter', !$disable);
    }

    /**
     * Show grid filter.
     *
     * @param bool $val
     * @return $this
     */
    public function showFilter(bool $val = true): static
    {
        return $this->disableFilter(!$val);
    }

    /**
     * Disable filter button.
     *
     * @param bool $disable
     * @return $this
     */
    public function disableFilterButton(bool $disable = true): static
    {
        $this->tools->disableFilterButton($disable);

        return $this;
    }

    /**
     * Show filter button.
     *
     * @param bool $val
     * @return $this
     */
    public function showFilterButton(bool $val = true): static
    {
        return $this->disableFilterButton(!$val);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function addFilterScript(): void
    {
        if (!$this->isAsyncRequest()) {
            return;
        }

        Admin::script(
            <<<JS
var count = {$this->filter()->countConditions()};

$('.async-{$this->getTableId()}').find('.filter-count').text(count > 0 ? ('('+count+')') : '');
JS
        );

        $url = Helper::urlWithoutQuery($this->filter()->urlWithoutFilters(), ['_pjax', static::ASYNC_NAME]);

        Admin::script("$('.grid-filter-form').attr('action', '$url');", true);
    }
}
