<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

class Range extends Field
{
    /**
     * Column name.
     *
     * @var string|array
     */
    protected string|array $column = [];

    public function __construct($column, $arguments)
    {
        $this->column['start'] = $column;
        $this->column['end'] = $arguments[0];

        array_shift($arguments);
        $this->label = $this->formatLabel($arguments);
    }

    protected function prepareInputValue(mixed $value): mixed
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationMessages(): array
    {
        // Default validation message.
        $messages = parent::getValidationMessages();

        $result = [];
        foreach ($messages as $key => $message) {
            $column = explode('.', $key);
            $rule = array_pop($column);
            $column = implode('.', $column);

            if ($this->column['start'] === $column) {
                $result[$column.'start.'.$rule] = $message;
            } else {
                $result[$key] = $message;
            }
        }

        return $result;
    }
}
