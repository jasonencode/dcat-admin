<?php

namespace Dcat\Admin\Tree;

use Dcat\Admin\Actions\Action;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Tree;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;

class Actions implements Renderable
{
    /**
     * @var Tree
     */
    protected Tree $parent;

    /**
     * @var Model
     */
    public Model $row;

    /**
     * @var array
     */
    protected array $appends = [];

    /**
     * @var array
     */
    protected array $prepends = [];

    /**
     * @var array
     */
    protected array $actions = [
        'delete'    => true,
        'quickEdit' => true,
        'edit'      => false,
    ];

    /**
     * @var array
     */
    protected array $defaultActions = [
        'edit'      => Tree\Actions\Edit::class,
        'quickEdit' => Tree\Actions\QuickEdit::class,
        'delete'    => Tree\Actions\Delete::class,
    ];

    /**
     * @param  string|Action|Htmlable|Renderable  $action
     * @return $this
     */
    public function append(Renderable|Htmlable|Action|string $action): static
    {
        $this->prepareAction($action);

        $this->appends[] = $action;

        return $this;
    }

    /**
     * @param  string|Action|Htmlable|Renderable  $action
     * @return $this
     */
    public function prepend(Renderable|Htmlable|Action|string $action): static
    {
        $this->prepareAction($action);

        array_unshift($this->prepends, $action);

        return $this;
    }

    public function getKey()
    {
        return $this->row->{$this->parent()->getKeyName()};
    }

    public function quickEdit(bool $value = true): static
    {
        $this->actions['quickEdit'] = $value;

        return $this;
    }

    public function disableQuickEdit(bool $value = true): Actions|static
    {
        return $this->quickEdit(! $value);
    }

    public function edit(bool $value = true): static
    {
        $this->actions['edit'] = $value;

        return $this;
    }

    public function disableEdit(bool $value = true): Actions|static
    {
        return $this->edit(! $value);
    }

    public function delete(bool $value = true): static
    {
        $this->actions['delete'] = $value;

        return $this;
    }

    public function disableDelete(bool $value = true): Actions|static
    {
        return $this->delete(! $value);
    }

    public function render(): string
    {
        $this->prependDefaultActions();

        $toString = [Helper::class, 'render'];

        $prepends = array_map($toString, $this->prepends);
        $appends = array_map($toString, $this->appends);

        return implode('', array_merge($prepends, $appends));
    }

    protected function prepareAction($action): void
    {
        if ($action instanceof RowAction) {
            $action->setParent($this);
            $action->setRow($this->row);
        }
    }

    protected function prependDefaultActions(): void
    {
        foreach ($this->actions as $action => $enable) {
            if (! $enable) {
                continue;
            }

            $action = new $this->defaultActions[$action]();

            $this->prepareAction($action);

            $this->prepend($action);
        }
    }

    public function parent(): Tree
    {
        return $this->parent;
    }

    public function setParent(Tree $tree): void
    {
        $this->parent = $tree;
    }

    public function getRow(): Model
    {
        return $this->row;
    }

    public function setRow($row): void
    {
        $this->row = $row;
    }
}
