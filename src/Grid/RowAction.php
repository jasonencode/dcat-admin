<?php

namespace Dcat\Admin\Grid;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Fluent;

abstract class RowAction extends GridAction
{
    /**
     * @var Model|Fluent|null
     */
    protected Model|Fluent|null $row = null;

    /**
     * @var Column|null
     */
    protected Column|null $column = null;

    /**
     * Get primary key value of current row.
     *
     * @return mixed
     */
    public function getKey(): mixed
    {
        if ($this->row) {
            return $this->row->{$this->parent->getKeyName()};
        }

        return parent::getKey();
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
