<?php

namespace Dcat\Admin\Grid;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Fluent;

abstract class RowAction extends GridAction
{
    /**
     * @var Model|Fluent
     */
    protected Model|Fluent $row;

    /**
     * @var Column
     */
    protected Column $column;

    /**
     * Get primary key value of current row.
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        return $this->row->{$this->parent->getKeyName()};
    }

    /**
     * Set row model.
     *
     * @param  mixed|null  $key
     * @return Model|mixed
     */
    public function row(mixed $key = null): mixed
    {
        if (func_num_args() == 0) {
            return $this->row;
        }

        return $this->row->{$key};
    }

    /**
     * Set row model.
     *
     * @param  Model|Fluent  $row
     * @return $this
     */
    public function setRow(Model|Fluent $row): RowAction
    {
        $this->row = $row;

        return $this;
    }

    public function getRow(): Model
    {
        return $this->row;
    }

    /**
     * @param  Column  $column
     * @return $this
     */
    public function setColumn(Column $column): static
    {
        $this->column = $column;

        return $this;
    }
}
