<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid;

trait HasActions
{
    /**
     * Callback for grid actions.
     *
     * @var Closure[]
     */
    protected array $actionsCallback = [];

    /**
     * @param  string  $actionClass
     * @return $this
     */
    public function setActionClass(string $actionClass): static
    {
        $this->options['actions_class'] = $actionClass;

        return $this;
    }

    /**
     * Get action display class.
     *
     * @return string
     */
    public function getActionClass(): string
    {
        if ($this->options['actions_class']) {
            return $this->options['actions_class'];
        }

        if ($class = config('admin.grid.grid_action_class')) {
            return $class;
        }

        return Grid\Displayers\Actions::class;
    }

    /**
     * Notes   : 设置操作按钮
     *
     * @Date   : 2024/8/15 17:07
     * @Author : <Jason.C>
     * @param  \Closure|array  $callback
     * @return $this
     */
    public function actions(Closure|array $callback): static
    {
        if (! $callback instanceof Closure) {
            $action = $callback;

            $callback = function (Grid\Displayers\Actions $actions) use (&$action) {
                if (! is_array($action)) {
                    $action = [$action];
                }

                foreach ($action as $v) {
                    $actions->append(clone $v);
                }
            };
        }

        $this->actionsCallback[] = $callback;

        return $this;
    }

    /**
     * Add `actions` column for grid.
     *
     * @return void
     */
    protected function appendActionsColumn(): void
    {
        if (! $this->options['actions']) {
            return;
        }

        $attributes = ['class' => 'grid__actions__'];

        $this->addColumn(Grid\Column::ACTION_COLUMN_NAME, trans('admin.action'))
            ->setHeaderAttributes($attributes)
            ->setAttributes($attributes)
            ->displayUsing($this->getActionClass(), [$this->actionsCallback]);
    }

    /**
     * 禁用所有操作
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableActions(bool $disable = true): static
    {
        $this->option('actions', ! $disable);
        return $this;
    }

    /**
     * Notes   : 显示操作。。。
     *
     * @Date   : 2024/8/15 17:05
     * @Author : <Jason.C>
     * @param  bool  $val
     * @return $this
     */
    public function showActions(bool $val = true): static
    {
        return $this->disableActions(! $val);
    }

    /**
     * 禁用编辑按钮
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableEditButton(bool $disable = true): static
    {
        $this->options['edit_button'] = ! $disable;
        return $this;
    }

    /**
     * 显示编辑按钮
     *
     * @param  bool  $val
     * @return $this
     */
    public function showEditButton(bool $val = true): static
    {
        return $this->disableEditButton(! $val);
    }

    /**
     * 禁用快捷编辑
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableQuickEditButton(bool $disable = true): static
    {
        $this->options['quick_edit_button'] = ! $disable;
        return $this;
    }

    /**
     * 显示快捷编辑
     *
     * @param  bool  $val
     * @return $this
     */
    public function showQuickEditButton(bool $val = true): static
    {
        return $this->disableQuickEditButton(! $val);
    }

    /**
     * 禁用详情按钮
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableViewButton(bool $disable = true): static
    {
        $this->options['view_button'] = ! $disable;
        return $this;
    }

    /**
     * 显示详情按钮
     *
     * @param  bool  $val
     * @return $this
     */
    public function showViewButton(bool $val = true): static
    {
        return $this->disableViewButton(! $val);
    }

    /**
     * 禁用删除按钮
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableDeleteButton(bool $disable = true): static
    {
        $this->options['delete_button'] = ! $disable;
        return $this;
    }

    /**
     * 显示删除按钮
     *
     * @param  bool  $val
     * @return $this
     */
    public function showDeleteButton(bool $val = true): static
    {
        return $this->disableDeleteButton(! $val);
    }
}
