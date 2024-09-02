<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Support\Helper;

class MultipleSelect extends Select
{
    protected function formatFieldData(array $data): array
    {
        return Helper::array($this->getValueFromData($data));
    }

    protected function prepareInputValue(mixed $value): array
    {
        return Helper::array($value);
    }
}
