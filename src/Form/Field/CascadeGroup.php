<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

class CascadeGroup extends Field
{
    /**
     * @var array
     */
    protected array $dependency;

    /**
     * @var string
     */
    protected string $hide = 'd-none';

    /**
     * CascadeGroup constructor.
     *
     * @param  array  $dependency
     */
    public function __construct(array $dependency)
    {
        $this->dependency = $dependency;
    }

    /**
     * @param  Field  $field
     * @return bool
     */
    public function dependsOn(Field $field): bool
    {
        return $this->dependency['column'] == $field->column();
    }

    /**
     * @return int
     */
    public function index(): int
    {
        return $this->dependency['index'];
    }

    /**
     * @return void
     */
    public function visiable(): void
    {
        $this->hide = '';
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return <<<HTML
            <div class="cascade-group {$this->dependency['class']} $this->hide">
            HTML;
    }

    /**
     * @return string
     */
    public function end(): string
    {
        return '</div>';
    }
}
