<?php

namespace Dcat\Admin\Grid\Displayers;

use Closure;
use Dcat\Admin\Admin;
use Throwable;

class SwitchDisplay extends AbstractDisplayer
{
    protected ?string $color = null;

    public function color($color): void
    {
        $this->color = Admin::color()->get($color);
    }

    /**
     * @throws Throwable
     */
    public function display(string $color = '', $refresh = false): string
    {
        if ($color instanceof Closure) {
            $color->call($this->row, $this);
        } else {
            $this->color($color);
        }

        $column  = $this->column->getName();
        $checked = $this->value ? 'checked' : '';
        $color   = $this->color ?: Admin::color()->primary();
        $url     = $this->url();

        return Admin::view(
            'admin::grid.displayer.switch',
            compact('column', 'color', 'refresh', 'checked', 'url')
        );
    }

    protected function url(): string
    {
        return $this->resource().'/'.$this->getKey();
    }
}
