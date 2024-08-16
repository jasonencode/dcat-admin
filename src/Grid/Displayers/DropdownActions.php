<?php

namespace Dcat\Admin\Grid\Displayers;

use Dcat\Admin\Actions\Action;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;

class DropdownActions extends Actions
{
    protected string $view = 'admin::grid.dropdown-actions';

    /**
     * @var array
     */
    protected array $default = [];

    public function prepend(Renderable|Htmlable|Action|string $action): static
    {
        return $this->append($action);
    }

    /**
     * @param  mixed  $action
     * @return string
     */
    protected function prepareAction(mixed &$action): string
    {
        parent::prepareAction($action);

        return $action = $this->wrapCustomAction($action);
    }

    /**
     * @param  mixed  $action
     * @return string
     */
    protected function wrapCustomAction(mixed $action): string
    {
        $action = Helper::render($action);

        if (mb_strpos($action, '</a>') === false) {
            return "<a>$action</a>";
        }

        return $action;
    }

    /**
     * Prepend default `edit` `view` `delete` actions.
     */
    protected function prependDefaultActions(): void
    {
        foreach ($this->actions as $action => $enable) {
            if (! $enable) {
                continue;
            }

            $this->default[] = $this->{'render'.ucfirst($action)}();
        }
    }

    /**
     * @param  array  $callbacks
     * @return string
     */
    public function display(array $callbacks = []): string
    {
        $this->resetDefaultActions();

        $this->call($callbacks);

        $this->prependDefaultActions();

        $actions = [
            'default'  => $this->default,
            'custom'   => $this->appends,
            'selector' => ".{$this->grid->getRowName()}-checkbox",
        ];

        return view($this->view, $actions);
    }

    protected function getViewLabel(): string
    {
        return '';
    }

    protected function getEditLabel(): string
    {
        return '';
    }

    protected function getQuickEditLabel(): string
    {
        return '';
    }

    protected function getDeleteLabel(): string
    {
        return '';
    }
}
