<?php

namespace Dcat\Admin\Form;

trait ResolveField
{
    protected array $resolvingFieldCallbacks = [];

    /**
     * @param  \Closure  $callback
     * @return $this
     * @example $form->resolvingField(function ($field, $form) {
     *     ...
     * });
     *
     */
    public function resolvingField(\Closure $callback): static
    {
        $this->resolvingFieldCallbacks[] = $callback;

        return $this;
    }

    public function setResolvingFieldCallbacks(array $callbacks): void
    {
        $this->resolvingFieldCallbacks = $callbacks;
    }

    /**
     * @param  Field  $field
     * @return void
     */
    protected function callResolvingFieldCallbacks(Field $field): void
    {
        foreach ($this->resolvingFieldCallbacks as $callback) {
            if ($callback($field, $this) === false) {
                break;
            }
        }
    }
}
