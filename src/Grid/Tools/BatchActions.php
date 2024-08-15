<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Traits\HasVariables;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

class BatchActions extends AbstractTool
{
    use Macroable;
    use HasVariables;

    protected string $view = 'admin::grid.batch-actions';

    /**
     * @var Collection
     */
    protected Collection $actions;

    /**
     * @var bool
     */
    protected bool $enableDelete = true;

    /**
     * @var bool
     */
    protected bool $isHoldSelectAllCheckbox = false;

    /**
     * BatchActions constructor.
     */
    public function __construct()
    {
        $this->actions = new Collection();

        $this->appendDefaultAction();
    }

    /**
     * Append default action(batch delete action).
     *
     * return void
     */
    protected function appendDefaultAction(): void
    {
        $this->add($this->makeBatchDelete(), '_delete_');
    }

    protected function makeBatchDelete()
    {
        $class = config('admin.grid.actions.batch_delete') ?: BatchDelete::class;

        return new $class(trans('admin.delete'));
    }

    /**
     * Disable delete.
     *
     * @return $this
     */
    public function disableDelete(bool $disable = true): static
    {
        $this->enableDelete = ! $disable;

        return $this;
    }

    public function divider(): BatchActions|static
    {
        return $this->add(new ActionDivider());
    }

    /**
     * Disable delete And Hide SelectAll Checkbox.
     *
     * @return $this
     */
    public function disableDeleteAndHideSelectAll(): static
    {
        $this->enableDelete = false;

        $this->isHoldSelectAllCheckbox = true;

        return $this;
    }

    /**
     * Add a batch action.
     *
     * @param  BatchAction  $action
     * @param  ?string  $key
     * @return $this
     */
    public function add(BatchAction $action, ?string $key = null): static
    {
        $action->selectorPrefix = '.grid-batch-action-'.$this->actions->count();

        if ($key) {
            $this->actions->put($key, $action);
        } else {
            $this->actions->push($action);
        }

        return $this;
    }

    /**
     * Prepare batch actions.
     *
     * @return void
     */
    protected function prepareActions(): void
    {
        foreach ($this->actions as $action) {
            $action->setGrid($this->parent);
        }
    }

    protected function defaultVariables(): array
    {
        return [
            'actions'                 => $this->actions,
            'selectAllName'           => $this->parent->getSelectAllName(),
            'isHoldSelectAllCheckbox' => $this->isHoldSelectAllCheckbox,
            'parent'                  => $this->parent,
        ];
    }

    /**
     * Render BatchActions button groups.
     *
     * @return string
     * @throws \Throwable
     */
    public function render(): string
    {
        if (! $this->enableDelete) {
            $this->actions->forget('_delete_');
        }

        if ($this->actions->isEmpty()) {
            return '';
        }

        $this->prepareActions();

        return Admin::view($this->view, $this->variables());
    }
}
