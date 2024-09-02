<?php

namespace Dcat\Admin\Form\Field;

use DateTimeZone;

class Timezone extends Select
{
    protected string $view = 'admin::form.select';

    public function render(): string
    {
        $this->options = collect(DateTimeZone::listIdentifiers())->mapWithKeys(function ($timezone) {
            return [$timezone => $timezone];
        })->toArray();

        return parent::render();
    }
}
