<?php

namespace Dcat\Admin\Tree;

use Dcat\Admin\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class RowAction extends Action
{
    /**
     * @var Actions;
     */
    protected Actions $actions;

    /**
     * @var Model
     */
    protected Model $row;

    /**
     * 获取主键值.
     *
     * @return array|string
     */
    public function getKey(): array|string
    {
        if ($key = parent::getKey()) {
            return $key;
        }

        return $this->row->{$this->actions->parent()->getKeyName()};
    }

    /**
     * 获取行数据.
     *
     * @return Model
     */
    public function getRow(): Model
    {
        return $this->row;
    }

    /**
     * 获取资源路径.
     *
     * @return string
     */
    public function resource(): string
    {
        return $this->actions->parent()->resource();
    }

    public function getActions(): Actions
    {
        return $this->actions;
    }

    public function setParent(Actions $actions): void
    {
        $this->actions = $actions;
    }

    public function setRow($row): void
    {
        $this->row = $row;
    }
}
