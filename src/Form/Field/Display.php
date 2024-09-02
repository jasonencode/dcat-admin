<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Dcat\Admin\Form\Field;

class Display extends Field
{
    protected ?Closure $callback = null;

    public function with(Closure $callback): void
    {
        $this->callback = $callback;
    }

    public function render(): string
    {
        if ($this->callback instanceof Closure) {
            $this->value = $this->callback->call($this->values(), $this->value());
        }

        return parent::render();
    }
}
