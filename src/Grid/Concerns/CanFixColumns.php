<?php

namespace Dcat\Admin\Grid\Concerns;

use Dcat\Admin\Grid\Displayers\Actions;
use Dcat\Admin\Grid\Displayers\DropdownActions;
use Dcat\Admin\Grid\FixColumns;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait CanFixColumns
{
    /**
     * @var FixColumns|null
     */
    protected ?FixColumns $fixColumns = null;

    /**
     * @param  int  $head
     * @param  int  $tail
     * @return FixColumns
     */
    public function fixColumns(int $head, int $tail = -1): FixColumns
    {
        $this->fixColumns = new FixColumns($this, $head, $tail);

        $this->resetActions();

        return $this->fixColumns;
    }

    public function hasFixColumns(): ?FixColumns
    {
        return $this->fixColumns;
    }

    protected function resetActions(): void
    {
        $actions = $this->getActionClass();

        if ($actions === DropdownActions::class) {
            $this->setActionClass(Actions::class);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function applyFixColumns(): void
    {
        if ($this->fixColumns) {
            if (! $this->options['bordered'] && ! $this->options['table_collapse']) {
                $this->tableCollapse();
            }

            $this->fixColumns->apply();
        }
    }

    /**
     * @return Collection
     */
    public function leftVisibleColumns(): Collection
    {
        return $this->fixColumns->leftColumns();
    }

    /**
     * @return Collection
     */
    public function rightVisibleColumns(): Collection
    {
        return $this->fixColumns->rightColumns();
    }

    /**
     * @return Collection
     */
    public function leftVisibleComplexColumns(): Collection
    {
        return $this->fixColumns->leftComplexColumns();
    }

    /**
     * @return Collection
     */
    public function rightVisibleComplexColumns(): Collection
    {
        return $this->fixColumns->rightComplexColumns();
    }
}
