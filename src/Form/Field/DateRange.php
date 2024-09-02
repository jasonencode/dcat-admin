<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

class DateRange extends Field
{
    protected string $format = 'YYYY-MM-DD';

    protected string|array $column = [];

    public function __construct($column, $arguments)
    {
        $this->column['start'] = $column;
        $this->column['end'] = $arguments[0];

        array_shift($arguments);
        $this->label = $this->formatLabel($arguments);

        $this->options(['format' => $this->format]);
    }

    protected function prepareInputValue(mixed $value): mixed
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }

    public function render(): string
    {
        $this->options['locale'] = config('app.locale');

        $this->addVariables(['options' => $this->options]);

        return parent::render();
    }

    /**
     * {@inheritDoc}
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

            if ($this->column['start'] == $column) {
                $result[$column.'start.'.$rule] = $message;
            } else {
                $result[$key] = $message;
            }
        }

        return $result;
    }
}
