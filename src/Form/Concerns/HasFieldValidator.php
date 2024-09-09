<?php

namespace Dcat\Admin\Form\Concerns;

use Closure;
use Dcat\Admin\Form;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

/**
 * @property Form $form
 */
trait HasFieldValidator
{
    /**
     * The validation rules for creation.
     *
     * @var array|Closure
     */
    protected Closure|array $creationRules = [];

    /**
     * The validation rules for updates.
     *
     * @var array|Closure
     */
    protected Closure|array $updateRules = [];

    /**
     * Validation rules.
     *
     * @var array|Closure
     */
    protected Closure|array $rules = [];

    /**
     * @var Closure|null
     */
    protected ?Closure $validator = null;

    /**
     * Validation messages.
     *
     * @var array
     */
    protected array $validationMessages = [];

    /**
     * Set the update validation rules for the field.
     *
     * @param  callable|array|string|null  $rules
     * @param  array  $messages
     * @return $this
     */
    public function updateRules(callable|array|string $rules = null, array $messages = []): static
    {
        $this->updateRules = $this->mergeRules($rules, $this->updateRules);

        if ($messages) {
            $this->setValidationMessages('update', $messages);
        }

        return $this;
    }

    /**
     * Set the creation validation rules for the field.
     *
     * @param  callable|array|string|null  $rules
     * @param  array  $messages
     * @return $this
     */
    public function creationRules(callable|array|string $rules = null, array $messages = []): static
    {
        $this->creationRules = $this->mergeRules($rules, $this->creationRules);

        if ($messages) {
            $this->setValidationMessages('creation', $messages);
        }

        return $this;
    }

    /**
     * Get or set rules.
     *
     * @param  null  $rules
     * @param  array  $messages
     * @return $this
     */
    public function rules($rules = null, array $messages = []): static
    {
        if ($rules instanceof Closure) {
            $this->rules = $rules;
        }

        $originalRules = $this->getRules();

        if (is_array($rules)) {
            $this->rules = array_merge($originalRules, $rules);
        } elseif (is_string($rules)) {
            $this->rules = array_merge($originalRules, array_filter(explode('|', $rules)));
        }

        if ($messages) {
            $this->setValidationMessages('default', $messages);
        }

        return $this;
    }

    /**
     * Get field validation rules.
     *
     * @return string|array
     */
    protected function getRules(): array|string
    {
        if ($this->isCreating()) {
            $rules = $this->creationRules ?: $this->rules;
        } elseif ($this->isEditing()) {
            $rules = $this->updateRules ?: $this->rules;
        } else {
            $rules = $this->rules;
        }

        if ($rules instanceof Closure) {
            $rules = $rules->call($this, $this->form);
        }

        if (is_string($rules)) {
            $rules = array_filter(explode('|', $rules));
        }

        if (!$this->form) {
            return $rules;
        }

        if (method_exists($this->form, 'key') || !$id = $this->form->getKey()) {
            return $rules;
        }

        if (is_array($rules)) {
            foreach ($rules as &$rule) {
                if (is_string($rule)) {
                    $rule = str_replace('{{id}}', $id, $rule);
                }
            }
        }

        return $rules;
    }

    /**
     * Format validation rules.
     *
     * @param  array|string  $rules
     * @return array
     */
    protected function formatRules(array|string $rules): array
    {
        if (is_string($rules)) {
            $rules = array_filter(explode('|', $rules));
        }

        return array_filter((array) $rules);
    }

    /**
     * @param  array|string|Closure  $input
     * @param  array|string  $original
     * @return array|Closure
     */
    protected function mergeRules(array|string|Closure $input, array|string $original): array|Closure
    {
        if ($input instanceof Closure) {
            $rules = $input;
        } else {
            if (!empty($original)) {
                $original = $this->formatRules($original);
            }
            $rules = array_merge($original, $this->formatRules($input));
        }

        return $rules;
    }

    /**
     * @param  string  $rule
     * @return $this
     */
    public function removeUpdateRule(string $rule): static
    {
        $this->deleteRuleByKeyword($this->updateRules, $rule);

        return $this;
    }

    /**
     * @param  string  $rule
     * @return $this
     */
    public function removeCreationRule(string $rule): static
    {
        $this->deleteRuleByKeyword($this->creationRules, $rule);

        return $this;
    }

    /**
     * Remove a specific rule by keyword.
     *
     * @param  string  $rule
     * @return $this
     */
    public function removeRule(string $rule): static
    {
        $this->deleteRuleByKeyword($this->rules, $rule);

        return $this;
    }

    /**
     * @param $rules
     * @param $rule
     * @return void
     */
    protected function deleteRuleByKeyword(&$rules, $rule): void
    {
        if (is_array($rules)) {
            Helper::deleteByValue($rules, $rule);

            return;
        }

        if (!is_string($rules)) {
            return;
        }

        $pattern = "/{$rule}[^|]?(\||$)/";

        $rules = preg_replace($pattern, '', $rules, -1);
    }

    /**
     * @param  string  $rule
     * @return bool
     */
    public function hasUpdateRule(string $rule): bool
    {
        return $this->isRuleExists($this->updateRules, $rule);
    }

    /**
     * @param  string  $rule
     * @return bool
     */
    public function hasCreationRule(string $rule): bool
    {
        return $this->isRuleExists($this->creationRules, $rule);
    }

    /**
     * @param  string  $rule
     * @return bool
     */
    public function hasRule(string $rule): bool
    {
        return $this->isRuleExists($this->getRules(), $rule);
    }

    /**
     * @param  string  $rule
     * @return string|false
     */
    protected function getRule(string $rule): string|false
    {
        $rules = $this->getRules();

        foreach (explode('|', $rules) as $r) {
            if ($this->isRuleExists($r, $rule)) {
                return $r;
            }
        }

        return false;
    }

    /**
     * @param $rules
     * @param $rule
     * @return bool
     */
    protected function isRuleExists($rules, $rule): bool
    {
        if (is_array($rules)) {
            foreach ($rules as $r) {
                if ($this->isRuleExists($r, $rule)) {
                    return true;
                }
            }

            return false;
        }

        if (!is_string($rules)) {
            return false;
        }

        $rule = str_replace(['*', '/'], ['([0-9a-z-_,:=><])*', "\/"], $rule);

        $pattern = "/{$rule}[^|]?(\||$)/";

        return (bool) preg_match($pattern, $rules);
    }

    /**
     * Set field validator.
     *
     * @param  callable  $validator
     * @return $this
     */
    public function validator(callable $validator): static
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Get validator for this field.
     *
     * @param  array  $input
     * @return false|\Illuminate\Validation\Validator|mixed
     */
    public function getValidator(array $input): mixed
    {
        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        $rules = $attributes = [];

        if (!$fieldRules = $this->getRules()) {
            return false;
        }

        if (is_string($this->column)) {
            if (!Arr::has($input, $this->column)) {
                return false;
            }

            $input = $this->sanitizeInput($input, $this->column);

            $rules[$this->column] = $fieldRules;
            $attributes[$this->column] = $this->label;
        }

        if (is_array($this->column)) {
            foreach ($this->column as $key => $column) {
                if (!Arr::has($input, $column)) {
                    continue;
                }
                $k = $column.$key;
                Arr::set($input, $k, Arr::get($input, $column));
                $rules[$k] = $fieldRules;
                $attributes[$k] = "$this->label[$column]";
            }
        }

        return Validator::make($input, $rules, $this->getValidationMessages(), $attributes);
    }

    /**
     * Set validation messages for column.
     *
     * @param  string  $key
     * @param  array  $messages
     * @return $this
     */
    public function setValidationMessages(string $key, array $messages): static
    {
        $this->validationMessages[$key] = $messages;

        return $this;
    }

    /**
     * Get validation messages for the field.
     *
     * @return array
     */
    public function getValidationMessages(): array
    {
        // Default validation message.
        $messages = $this->validationMessages['default'] ?? [];

        if ($this->isCreating()) {
            $messages = $this->validationMessages['creation'] ?? $messages;
        } elseif ($this->isEditing()) {
            $messages = $this->validationMessages['update'] ?? $messages;
        }

        $result = [];

        foreach ($messages as $k => $v) {
            if (Str::contains($k, '.')) {
                $result[$k] = $v;
                continue;
            }

            if (is_string($this->column)) {
                $k = $this->column.'.'.$k;

                $result[$k] = $v;
                continue;
            }

            foreach ($this->column as $column) {
                $result[$column.'.'.$k] = $v;
            }
        }

        return $result;
    }

    /**
     * Set error messages for individual form field.
     *
     * @see http://1000hz.github.io/bootstrap-validator/
     *
     * @param  string  $error
     * @param  string|null  $key
     * @return $this
     */
    public function setClientValidationError(string $error, string $key = null): static
    {
        $key = $key ? "$key-" : '';

        return $this->attribute("data-{$key}error", $error);
    }

    /**
     * @param  MessageBag  $messageBag
     * @return MessageBag
     */
    public function formatValidatorMessages(MessageBag $messageBag): MessageBag
    {
        return $messageBag;
    }
}
