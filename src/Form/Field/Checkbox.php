<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Checkbox as WidgetCheckbox;
use Illuminate\Support\Collection;

class Checkbox extends MultipleSelect
{
    use CanCascadeFields;
    use CanLoadFields;
    use Sizeable;

    protected string $style = 'primary';

    protected string $cascadeEvent = 'change';

    protected bool $canCheckAll = false;

    protected bool $inline = true;

    /**
     * @param  array|Closure|Collection  $options
     * @return Checkbox
     */
    public function options(array|Closure|Collection $options = []): static
    {
        if ($options instanceof Closure) {
            $this->options = $options;

            return $this;
        }

        $this->options = Helper::array($options);

        return $this;
    }

    /**
     * "info", "primary", "inverse", "danger", "success", "purple".
     *
     * @param  string  $style
     * @return $this
     */
    public function style(string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Add a checkbox above this component, so you can select all checkboxes by click on it.
     *
     * @return $this
     */
    public function canCheckAll(): static
    {
        $this->canCheckAll = true;

        return $this;
    }

    public function inline(bool $inline): static
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function render(): string
    {
        if ($this->options instanceof Closure) {
            $this->options(
                $this->options->call($this->values(), $this->value(), $this)
            );
        }

        $this->addCascadeScript();

        $checkbox = WidgetCheckbox::make(
            $this->getElementName().'[]',
            $this->options,
            $this->style
        );

        if ($this->attributes['disabled'] ?? false) {
            $checkbox->disable();
        }

        $checkbox
            ->inline($this->inline)
            ->check($this->value())
            ->class($this->getElementClassString())
            ->size($this->size);

        $this->addVariables([
            'checkbox' => $checkbox,
            'checkAll' => $this->makeCheckAllCheckbox(),
        ]);

        return parent::render();
    }

    protected function makeCheckAllCheckbox()
    {
        if (! $this->canCheckAll) {
            return;
        }

        $this->addVariables(['canCheckAll' => $this->canCheckAll]);

        return WidgetCheckbox::make('_check_all_', [__('admin.all')]);
    }
}
