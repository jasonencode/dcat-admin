<?php

namespace Dcat\Admin\Grid\Column;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Column;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ValueFilter
{
    /**
     * @var Filter
     */
    protected Filter $filter;

    /**
     * @var string|\Closure
     */
    protected string|Closure $valueKey;

    public function __construct(Filter $filter, $valueKey)
    {
        $this->filter = $filter;
        $this->valueKey = $valueKey;

        $this->addStyle();
    }

    protected function addStyle(): void
    {
        Admin::style('.value-filter .dashed{border-bottom:1px dashed}.value-filter:hover+a{opacity:1!important}');
    }

    protected function column(): Column
    {
        return $this->filter->parent();
    }

    public function getQueryName(): string
    {
        return $this->filter->getQueryName();
    }

    public function value(): array|string|Request
    {
        return $this->filter->value();
    }

    protected function originalValue()
    {
        if (! $this->valueKey) {
            return $this->column()->getOriginal();
        }

        $row = $this->column()->getOriginalModel();

        if ($this->valueKey instanceof Closure) {
            return $this->valueKey->call(
                $row,
                $this->column()->getName()
            );
        }

        return Arr::get(
            $row->toArray(),
            $this->valueKey,
            $this->column()->getOriginal()
        );
    }

    protected function wrap($value)
    {
        if (! preg_match('/<[^>]+>(.*)<\/[^>]+>/', $value)) {
            return "<span>$value</span>";
        }

        return $value;
    }

    public function render($value): string
    {
        $pageName = $this->column()->grid()->model()->getPageName();

        $url = request()->fullUrlWithQuery([
            $this->getQueryName() => $this->originalValue(),
            $pageName          => null,
        ]);

        return "<a class='value-filter' href='$url'>{$this->wrap($value)}</a> &nbsp;<a href='#' style='opacity:0;' class='feather icon-search'></a>";
    }
}
