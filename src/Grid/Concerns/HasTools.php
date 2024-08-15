<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Grid\Tools;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;

trait HasTools
{
    /**
     * Header tools.
     *
     * @var Tools
     */
    protected Tools $tools;

    /**
     * 初始化工具栏.
     */
    public function setUpTools(): void
    {
        $this->tools = new Tools($this);
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function toolsWithOutline(bool $value = true): static
    {
        $this->tools->withOutline($value);

        return $this;
    }

    /**
     * Get or setup grid tools.
     *
     * @param  Closure|array|Action|Tools\AbstractTool|Renderable|Htmlable|string|null  $value
     * @return $this|Tools
     */
    public function tools(Renderable|Htmlable|Closure|array|Action|string|Tools\AbstractTool $value = null
    ): Tools|static {
        if ($value === null) {
            return $this->tools;
        }

        if ($value instanceof Closure) {
            $value($this->tools);

            return $this;
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $tool) {
            $this->tools->append($tool);
        }

        return $this;
    }

    /**
     * Set grid batch-action callback.
     *
     * @param  Closure|BatchAction|BatchAction[]  $value
     * @return $this
     */
    public function batchActions(array|BatchAction|Closure $value): static
    {
        $this->tools(function (Tools $tools) use ($value) {
            $tools->batch($value);
        });

        return $this;
    }

    /**
     * Render custom tools.
     *
     * @return string
     */
    public function renderTools(): string
    {
        return $this->tools->render();
    }

    /**
     * @param  bool  $val
     * @return mixed
     */
    public function disableToolbar(bool $val = true): mixed
    {
        return $this->option('toolbar', ! $val);
    }

    /**
     * @param  bool  $val
     * @return mixed
     */
    public function showToolbar(bool $val = true): mixed
    {
        return $this->disableToolbar(! $val);
    }

    /**
     * Disable batch actions.
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableBatchActions(bool $disable = true): static
    {
        $this->tools->disableBatchActions($disable);

        return $this;
    }

    /**
     * Show batch actions.
     *
     * @param  bool  $val
     * @return $this
     */
    public function showBatchActions(bool $val = true): static
    {
        return $this->disableBatchActions(! $val);
    }

    /**
     * Disable batch delete.
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableBatchDelete(bool $disable = true): static
    {
        $this->tools->batch(function ($action) use ($disable) {
            $action->disableDelete($disable);
        });

        return $this;
    }

    /**
     * Show batch delete.
     *
     * @param  bool  $val
     * @return $this
     */
    public function showBatchDelete(bool $val = true): static
    {
        return $this->disableBatchDelete(! $val);
    }

    /**
     * Disable refresh button.
     *
     * @param  bool  $disable
     * @return $this
     */
    public function disableRefreshButton(bool $disable = true): static
    {
        $this->tools->disableRefreshButton($disable);

        return $this;
    }

    /**
     * Show refresh button.
     *
     * @param  bool  $val
     * @return $this
     */
    public function showRefreshButton(bool $val = true): static
    {
        return $this->disableRefreshButton(! $val);
    }

    /**
     * If grid show toolbar.
     *
     * @return bool
     */
    public function allowToolbar(): bool
    {
        if (
            $this->option('toolbar')
            && (
                $this->tools()->has()
                || $this->allowExporter()
                || $this->allowCreateButton()
                || ! empty($this->variables['title'])
            )
        ) {
            return true;
        }

        return false;
    }
}
