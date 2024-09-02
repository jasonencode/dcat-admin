<?php

namespace Dcat\Admin\Form\Concerns;

use Closure;
use Dcat\Admin\Form\Layout;

trait HasLayout
{
    /**
     * @var ?Layout
     */
    protected ?Layout $layout = null;

    /**
     * @param  float|int  $width
     * @param  Closure  $callback
     * @return $this
     */
    public function column(float|int $width, Closure $callback): static
    {
        $this->layout()->onlyColumn($width, function () use ($callback) {
            $callback($this);
        });

        return $this;
    }

    /**
     * @return Layout
     */
    public function layout(): Layout
    {
        return $this->layout ?: ($this->layout = new Layout($this));
    }
}
