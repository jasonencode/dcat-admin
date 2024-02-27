<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

class Textarea extends Field
{
    /**
     * Default rows of textarea.
     *
     * @var int
     */
    protected int $rows = 5;

    /**
     * Set rows of textarea.
     *
     * @param  int  $rows
     * @return $this
     */
    public function rows(int $rows = 5): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): string
    {
        if (is_array($this->value)) {
            $this->value = json_encode($this->value, JSON_PRETTY_PRINT);
        }

        $this->addVariables(['rows' => $this->rows]);

        return parent::render();
    }
}
