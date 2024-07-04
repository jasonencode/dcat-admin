<?php

namespace Dcat\Admin\Form\Concerns;

use Closure;
use Dcat\Admin\Form\Row;

trait HasRows
{
    /**
     * Field rows in form.
     *
     * @var Row[]
     */
    protected array $rows = [];

    /**
     * Add a row in form.
     *
     * @param  Closure  $callback
     * @return $this
     */
    public function row(Closure $callback): static
    {
        $this->rows[] = new Row($callback, $this);

        return $this;
    }

    /**
     * @return Row[]
     */
    public function rows()
    {
        return $this->rows;
    }
}
