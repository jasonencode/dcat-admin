<?php

namespace Dcat\Admin\Grid\ColumnSelector;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\Grid\ColumnSelectorStore;
use Dcat\Admin\Grid;

class SessionStore implements ColumnSelectorStore
{
    /**
     * @var Grid
     */
    protected Grid $grid;

    public function setGrid(Grid $grid): void
    {
        $this->grid = $grid;
    }

    public function store(array $input): void
    {
        session()->put($this->getKey(), $input);
    }

    public function get()
    {
        return session()->get($this->getKey());
    }

    public function forget(): void
    {
        session()->remove($this->getKey());
    }

    protected function getKey(): string
    {
        return $this->grid->getName().'/'.request()->path().'/'.Admin::user()->getKey();
    }
}
