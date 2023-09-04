<?php

namespace Dcat\Admin\Grid\Displayers;

class Textarea extends Editable
{
    protected ?string $type = 'textarea';

    protected ?string $view = 'admin::grid.displayer.editinline.textarea';

    public function defaultOptions(): array
    {
        return [
            'rows' => 5,
        ];
    }
}
