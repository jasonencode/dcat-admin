<?php

namespace Dcat\Admin\Grid;

use Dcat\Admin\Actions\Action;
use Dcat\Admin\Grid;

/**
 * Class GridAction.
 */
abstract class GridAction extends Action
{
    /**
     * @var Grid|null
     */
    protected ?Grid $parent = null;

    /**
     * @param  Grid  $grid
     * @return $this
     */
    public function setGrid(Grid $grid): static
    {
        $this->parent = $grid;

        return $this;
    }

    /**
     * Get url path of current resource.
     *
     * @return string
     */
    public function resource(): string
    {
        return $this->parent->resource();
    }
}
