<?php

namespace Dcat\Admin\Grid\Actions;

use Dcat\Admin\Grid\RowAction;

class Edit extends RowAction
{
    /**
     * @return string
     */
    public function title(): string
    {
        if ($this->title) {
            return $this->title;
        }

        return '<i class="feather icon-edit-1"></i> '.__('admin.edit').' &nbsp;&nbsp;';
    }

    /**
     * @return string
     */
    public function href(): string
    {
        return $this->parent->getEditUrl($this->getKey());
    }
}
