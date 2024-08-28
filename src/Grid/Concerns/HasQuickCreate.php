<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid\Tools\QuickCreate;
use Throwable;

trait HasQuickCreate
{
    /**
     * @var QuickCreate|null
     */
    protected ?QuickCreate $quickCreate = null;

    /**
     * @param  Closure  $callback
     * @return $this
     */
    public function quickCreate(Closure $callback): static
    {
        $this->quickCreate = new QuickCreate($this);

        call_user_func($callback, $this->quickCreate);

        return $this;
    }

    /**
     * Indicates grid has quick-create.
     *
     * @return bool
     */
    public function hasQuickCreate(): bool
    {
        return ! is_null($this->quickCreate);
    }

    /**
     * Render quick-create form.
     *
     * @return string
     * @throws Throwable
     */
    public function renderQuickCreate(): string
    {
        $columnCount = $this->columns->count();

        return $this->quickCreate->render($columnCount);
    }
}
