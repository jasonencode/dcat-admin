<?php

namespace Dcat\Admin\Grid\Column;

use Closure;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Model;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;

/**
 * @property Grid $grid
 */
trait HasHeader
{
    /**
     * @var Filter|null
     */
    public ?Filter $filter = null;

    /**
     * @var array
     */
    protected array $headers = [];

    /**
     * Add contents to column header.
     *
     * @param  string|Htmlable|Renderable  $header
     * @return $this
     */
    public function addHeader(Renderable|Htmlable|string $header): static
    {
        if ($header instanceof Filter) {
            $header->setParent($this);
            $this->filter = $header;
        }

        $this->headers[] = $header;

        return $this;
    }

    /**
     * Add a column sortable to column header.
     *
     * @param  string|null  $columnName
     * @param  string|null  $cast
     * @return $this
     */
    public function sortable(string $columnName = null, string $cast = null): static
    {
        $sorter = new Sorter($this->grid, $columnName ?: $this->getName(), $cast);

        return $this->addHeader($sorter);
    }

    /**
     * Set column filter.
     *
     * @param  null  $filter
     * @return $this
     * @throws \Dcat\Admin\Exception\RuntimeException
     * @example
     *      $grid->username()->filter();
     *
     *      $grid->user()->filter('user.id');
     *
     *      $grid->user()->filter(function () {
     *          return $this->user['id'];
     *      });
     *
     *      $grid->username()->filter(
     *          Grid\Column\Filter\StartWith::make(__('admin.username'))
     *      );
     *
     *      $grid->created_at()->filter(
     *          Grid\Column\Filter\Equal::make(__('admin.created_at'))->date()
     *      );
     *
     */
    public function filter($filter = null): static
    {
        $valueKey = is_string($filter) || $filter instanceof Closure ? $filter : null;

        if (! $filter || $valueKey) {
            $filter = Grid\Column\Filter\Equal::make()->valueFilter($valueKey);
        }

        if (! $filter instanceof Grid\Column\Filter) {
            throw new RuntimeException('The "$filter" must be a type of '.Grid\Column\Filter::class.'.');
        }

        return $this->addHeader($filter);
    }

    /**
     * @param  null  $valueKey
     * @return $this
     * @throws \Dcat\Admin\Exception\RuntimeException
     */
    public function filterByValue($valueKey = null): static
    {
        return $this->filter(
            Grid\Column\Filter\Equal::make()
                ->valueFilter($valueKey)
                ->hide()
        );
    }

    /**
     * Add a help tooltip to column header.
     *
     * @param  string|\Closure  $message
     * @param  null|string  $style  'green', 'blue', 'red', 'purple'
     * @param  null|string  $placement  'bottom', 'left', 'right', 'top'
     * @return $this
     */
    public function help(string|Closure $message, ?string $style = null, ?string $placement = null): static
    {
        return $this->addHeader(new Help($message, $style, $placement));
    }

    /**
     * Add a binding based on filter to the model query.
     *
     * @param  Model  $model
     */
    public function bindFilterQuery(Model $model): void
    {
        $this->filter?->addBinding($this->filter->value(), $model);
    }

    /**
     * Render Column header.
     *
     * @return string
     */
    public function renderHeader(): string
    {
        if (! $this->headers) {
            return '';
        }
        $headers = implode(
            '',
            array_map(
                [Helper::class, 'render'],
                $this->headers
            )
        );

        return "<span class='grid-column-header'>$headers</span>";
    }
}
