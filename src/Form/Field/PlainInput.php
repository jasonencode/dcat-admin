<?php

namespace Dcat\Admin\Form\Field;

trait PlainInput
{
    protected string $prepend = '';

    protected string $append = '';

    public function prepend(string $string): static
    {
        $this->prepend = $string;

        return $this;
    }

    public function append(string $string): static
    {
        $this->append = $string;

        return $this;
    }

    protected function initPlainInput(): void
    {
        if (empty($this->view)) {
            $this->view = 'admin::form.input';
        }
    }
}
