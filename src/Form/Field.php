<?php

namespace Dcat\Admin\Form;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasBuilderEvents;
use Dcat\Admin\Traits\HasVariables;
use Dcat\Admin\Widgets\Form as WidgetForm;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Throwable;

/**
 * Class Field.
 */
class Field implements Renderable
{
    use Macroable;
    use Form\Concerns\HasFieldValidator;
    use HasBuilderEvents;
    use HasVariables;

    const FILE_DELETE_FLAG = '_file_del_';

    const FIELD_CLASS_PREFIX = 'field_';

    const BUILD_IGNORE = 'build-ignore';

    const NORMAL_CLASS = '_normal_';

    /**
     * Element value.
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Data of all original columns of value.
     *
     * @var mixed
     */
    protected mixed $data = [];

    /**
     * Field original value.
     *
     * @var mixed
     */
    protected mixed $original = null;

    /**
     * Field default value.
     *
     * @var mixed
     */
    protected mixed $default = null;

    /**
     * @var bool
     */
    protected bool $allowDefaultValueInEditPage = false;

    /**
     * Element label.
     *
     * @var ?string
     */
    protected ?string $label = '';

    /**
     * Column name.
     *
     * @var string|array
     */
    protected string|array $column = '';

    /**
     * Form element name.
     *
     * @var string|array
     */
    protected string|array $elementName = [];

    /**
     * Form element classes.
     *
     * @var array
     */
    protected array $elementClass = [];

    /**
     * Options for specify elements.
     *
     * @var array|Closure|Collection
     */
    protected array|Collection|Closure $options = [];

    /**
     * Checked for specify elements.
     *
     * @var array
     */
    protected array $checked = [];

    /**
     * Css required by this field.
     *
     * @var array
     */
    protected static $css = [];

    /**
     * Js required by this field.
     *
     * @var array
     */
    protected static $js = [];

    /**
     * Script for field.
     *
     * @var string
     */
    protected string $script = '';

    /**
     * Element attributes.
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Parent form.
     *
     * @var Form|WidgetForm|null
     */
    protected Form|WidgetForm|null $form = null;

    /**
     * @var WidgetForm|null
     */
    protected ?WidgetForm $parent = null;

    /**
     * View for field to render.
     *
     * @var string
     */
    protected string $view = '';

    /**
     * Help block.
     *
     * @var array
     */
    protected array $help = [];

    /**
     * Key for errors.
     *
     * @var string|array
     */
    protected string|array $errorKey = '';

    /**
     * Placeholder for this field.
     *
     * @var string|array
     */
    protected string|array $placeholder = '';

    /**
     * Width for label and field.
     *
     * @var array
     */
    protected array $width = [
        'label' => 2,
        'field' => 8,
    ];

    /**
     * If the form horizontal layout.
     *
     * @var bool
     */
    protected bool $horizontal = true;

    /**
     * column data format.
     *
     * @var Closure|null
     */
    protected ?Closure $customFormat = null;

    /**
     * @var bool
     */
    protected bool $display = true;

    /**
     * @var array
     */
    protected array $labelClass = ['text-capitalize'];

    /**
     * @var array
     */
    protected array $fieldClass = [];

    /**
     * @var array
     */
    protected array $formGroupClass = ['form-field'];

    /**
     * @var Closure[]
     */
    protected array $savingCallbacks = [];

    /**
     * Field constructor.
     *
     * @param  array|string  $column
     * @param  array  $arguments
     */
    public function __construct(array|string $column, array $arguments = [])
    {
        $this->column = $column;
        $this->label = $this->formatLabel($arguments);

        $this->callResolving();
    }

    /**
     * @param  array  $options
     * @return $this
     */
    public function setRelation(array $options = []): static
    {
        return $this;
    }

    /**
     * Format the label value.
     *
     * @param  array  $arguments
     * @return string
     */
    protected function formatLabel(array $arguments = []): string
    {
        if (isset($arguments[0])) {
            return $arguments[0];
        }

        $column = is_array($this->column) ? current($this->column) : $this->column;

        return str_replace('_', ' ', admin_trans_field($column));
    }

    /**
     * Format the name of the field.
     *
     * @param  string  $column
     * @return array|mixed|string
     */
    protected function formatName(string $column): mixed
    {
        return Helper::formatElementName($column);
    }

    /**
     * Set form element name.
     *
     * @param  array|string  $name
     * @return $this
     *
     * @author Edwin Hui
     */
    public function setElementName(array|string $name): static
    {
        $this->elementName = $name;

        return $this;
    }

    /**
     * Get form element name.
     *
     * @return array|mixed|string
     */
    public function getElementName(): mixed
    {
        return $this->elementName ?: $this->formatName($this->column);
    }

    /**
     * Fill data to the field.
     *
     * @param  array  $data
     * @return void
     */
    public function fill(array $data): void
    {
        $data = Helper::array($data);

        $this->data($data);

        $this->value = $this->formatFieldData($data);

        $this->callCustomFormatter();
    }

    /**
     * Format field data.
     *
     * @param  array  $data
     * @return mixed
     */
    protected function formatFieldData(array $data): mixed
    {
        if (is_array($this->column)) {
            $value = [];

            foreach ($this->column as $key => $column) {
                $value[$key] = $this->getValueFromData($data, $this->normalizeColumn($column));
            }

            return $value;
        }

        return $this->getValueFromData($data, null, $this->value);
    }

    /**
     * @param  array  $data
     * @param  string|null  $column
     * @param  mixed|null  $default
     * @return mixed
     */
    protected function getValueFromData(array $data, string $column = null, mixed $default = null): mixed
    {
        $column = $column ?: $this->normalizeColumn();

        if (Arr::has($data, $column)) {
            return Arr::get($data, $column, $default);
        }

        return Arr::get($data, Str::snake($column), $default);
    }

    protected function normalizeColumn(?string $column = null): array|string
    {
        return str_replace('->', '.', $column ?: $this->column);
    }

    /**
     * custom format form column data when edit.
     *
     * @param  Closure  $call
     * @return $this
     */
    public function customFormat(Closure $call): static
    {
        $this->customFormat = $call;

        return $this;
    }

    /**
     * Set original value to the field.
     *
     * @param  array  $data
     * @return void
     */
    final public function setOriginal(array $data): void
    {
        $data = Helper::array($data);

        $this->original = $this->formatFieldData($data);

        $this->callCustomFormatter('original', new Fluent($data));
    }

    /**
     * @param  string  $key
     * @param  Fluent|null  $data
     */
    protected function callCustomFormatter(string $key = 'value', Fluent $data = null): void
    {
        if ($this->customFormat) {
            $this->{$key} = $this->customFormat
                ->call(
                    $data ?: $this->data(),
                    $this->{$key},
                    $this->column,
                    $this
                );
        }
    }

    /**
     * @param  Form|WidgetForm|null  $form
     * @return $this
     */
    public function setForm(WidgetForm|Form $form = null): static
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @param  WidgetForm|null  $form
     * @return $this
     */
    public function setParent(WidgetForm $form = null): static
    {
        $this->parent = $form;

        return $this;
    }

    /**
     * @return Fluent|Model
     */
    public function values(): Model|Fluent
    {
        return $this->form ? $this->form->model() : new Fluent();
    }

    /**
     * Set width for field and label.
     *
     * @param  int  $field
     * @param  int  $label
     * @return $this
     */
    public function width(int $field = 8, int $label = 2): static
    {
        $this->width = [
            'label' => $label,
            'field' => $field,
        ];

        return $this;
    }

    /**
     * Set the field options.
     *
     * @param  array  $options
     * @return $this
     */
    public function options(array $options = []): static
    {
        if ($options instanceof Closure) {
            $options = $options->call($this->data(), $this->value());
        }

        $this->options = array_merge($this->options, Helper::array($options));

        return $this;
    }

    /**
     * @param  array  $options
     * @return $this
     */
    public function replaceOptions(array $options): static
    {
        if ($options instanceof Closure) {
            $options = $options->call($this->data(), $this->value());
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param  array|Arrayable  $options
     * @return $this
     */
    public function mergeOptions(array|Arrayable $options): static
    {
        return $this->options($options);
    }

    /**
     * Set the field option checked.
     *
     * @param  array  $checked
     * @return $this
     */
    public function checked(array $checked = []): static
    {
        if ($checked instanceof Arrayable) {
            $checked = $checked->toArray();
        }

        $this->checked = array_merge($this->checked, $checked);

        return $this;
    }

    /**
     * Set key for error message.
     *
     * @param  array|string  $key
     * @return $this
     */
    public function setErrorKey(array|string $key): static
    {
        $this->errorKey = $key;

        return $this;
    }

    /**
     * Get key for error message.
     *
     * @return array|string
     */
    public function getErrorKey(): array|string
    {
        return $this->errorKey ?: $this->column;
    }

    /**
     * Set or get value of the field.
     *
     * @param  mixed|null  $value
     * @return Field|string|null
     */
    public function value(mixed $value = null): null|static|string
    {
        if (is_null($value)) {
            if (
                $this->value === null
                || (is_array($this->value) && empty($this->value))
            ) {
                return $this->default();
            }

            return $this->value;
        }

        $this->value = value($value);

        return $this;
    }

    /**
     * Set or get data.
     *
     * @param  array|null  $data
     * @return $this|Fluent
     */
    public function data(array $data = null): Fluent|static
    {
        if (is_null($data)) {
            if (!$this->data || is_array($this->data)) {
                $this->data = new Fluent((array) $this->data);
            }

            return $this->data;
        }

        $this->data = new Fluent($data);

        return $this;
    }

    /**
     * Get or set default value for field.
     *
     * @param  mixed|null  $default
     * @param  bool  $edit
     * @return string|Field|null
     */
    public function default(mixed $default = null, bool $edit = false): null|string|static
    {
        if ($default === null) {
            if (
                $this->form
                && method_exists($this->form, 'isCreating')
                && !$this->form->isCreating()
                && !$this->allowDefaultValueInEditPage
            ) {
                return null;
            }

            if ($this->default instanceof Closure) {
                $this->default->bindTo($this->data());

                return call_user_func($this->default, $this->form);
            }

            return $this->default;
        }

        $this->default = value($default);
        $this->allowDefaultValueInEditPage = $edit;

        return $this;
    }

    /**
     * Set help block for current field.
     *
     * @param  string  $text
     * @param  string  $icon
     * @return $this
     */
    public function help(string $text = '', string $icon = 'feather icon-help-circle'): static
    {
        $this->help = compact('text', 'icon');

        return $this;
    }

    /**
     * Get column of the field.
     *
     * @return string|array
     */
    public function column(): array|string
    {
        return $this->column;
    }

    /**
     * Get or set label of the field.
     *
     * @param  mixed|null  $label
     * @return $this|string
     */
    public function label(mixed $label = null): string|static
    {
        if ($label == null) {
            return $this->label;
        }

        if ($label instanceof Closure) {
            $label = $label($this->label);
        }

        $this->label = $label;

        return $this;
    }

    /**
     * Get original value of the field.
     *
     * @return mixed
     */
    public function original(): mixed
    {
        return $this->original;
    }

    /**
     * Sanitize input data.
     *
     * @param  array  $input
     * @param  string  $column
     * @return array
     */
    protected function sanitizeInput(array $input, string $column): array
    {
        if ($this instanceof Field\MultipleSelect) {
            $value = Arr::get($input, $column);
            Arr::set($input, $column, array_filter($value));
        }

        return $input;
    }

    /**
     * Add html attributes to elements.
     *
     * @param  array|string  $attribute
     * @param  mixed|null  $value
     * @return $this
     */
    public function attribute(array|string $attribute, mixed $value = null): static
    {
        if (is_array($attribute)) {
            $this->attributes = array_merge($this->attributes, $attribute);
        } else {
            $this->attributes[$attribute] = (string) $value;
        }

        return $this;
    }

    /**
     * @param  string  $key
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * @param  string  $key
     * @return mixed|null
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Specifies a regular expression against which to validate the value of the input.
     *
     * @param  string|null  $error
     * @param  string  $regexp
     * @return $this
     */
    public function pattern(string $regexp, string $error = null): static
    {
        if ($error) {
            $this->attribute('data-pattern-error', $error);
        }

        return $this->attribute('pattern', $regexp);
    }

    /**
     * set the input filed required.
     *
     * @param  bool  $isLabelAsterisked
     * @return $this
     */
    public function required(bool $isLabelAsterisked = true): static
    {
        if ($isLabelAsterisked) {
            $this->setLabelClass(['asterisk']);
        }

        $this->rules('required');

        return $this->attribute('required', true);
    }

    /**
     * Set the field automatically get focus.
     *
     * @param  bool  $value
     * @return $this
     */
    public function autofocus(bool $value = true): static
    {
        return $this->attribute('autofocus', $value);
    }

    /**
     * Set the field as readonly mode.
     *
     * @param  bool  $value
     * @return $this
     */
    public function readOnly(bool $value = true): static
    {
        if (!$value) {
            unset($this->attributes['readonly']);

            return $this;
        }

        return $this->attribute('readonly', $value);
    }

    /**
     * Set field as disabled.
     *
     * @param  bool  $value
     * @return $this
     */
    public function disable(bool $value = true): static
    {
        if (!$value) {
            unset($this->attributes['disabled']);

            return $this;
        }

        return $this->attribute('disabled', $value);
    }

    /**
     * Get or set field placeholder.
     *
     * @param  string|null  $placeholder
     * @return array|string|Field
     */
    public function placeholder(string $placeholder = null): array|string|static
    {
        if ($placeholder === null) {
            return $this->placeholder ?: $this->defaultPlaceholder();
        }

        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return string
     */
    protected function defaultPlaceholder(): string
    {
        return trans('admin.input').' '.$this->label;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    protected function prepareInputValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @param  Closure  $closure
     * @return $this
     */
    public function saving(Closure $closure): static
    {
        $this->savingCallbacks[] = $closure;

        return $this;
    }

    /**
     * Prepare for a field value before update or insert.
     *
     * @param  mixed  $value
     * @return mixed
     */
    final public function prepare(mixed $value): mixed
    {
        $value = $this->prepareInputValue($value);

        if ($this->savingCallbacks) {
            foreach ($this->savingCallbacks as $callback) {
                $value = $callback->call($this->data(), $value);
            }
        }

        return $value;
    }

    /**
     * Format the field attributes.
     *
     * @return string
     */
    protected function formatAttributes(): string
    {
        $html = [];

        foreach ($this->attributes as $name => $value) {
            $html[] = $name.'="'.e($value).'"';
        }

        return implode(' ', $html);
    }

    /**
     * @param  bool  $value
     * @return $this
     */
    public function horizontal(bool $value = true): static
    {
        $this->horizontal = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getViewElementClasses(): array
    {
        if ($this->horizontal) {
            return [
                'label' => "col-md-{$this->width['label']} {$this->getLabelClass()}",
                'field' => "col-md-{$this->width['field']} {$this->getFieldClass()}",
                'form-group' => "form-group row {$this->getFormGroupClass()}",
            ];
        }

        return [
            'label' => $this->getLabelClass(),
            'field' => $this->getFieldClass(),
            'form-group' => $this->getFormGroupClass(),
        ];
    }

    /**
     * Set element class.
     *
     * @param  array|string  $class
     * @param  bool  $normalize
     * @return $this
     */
    public function setElementClass(array|string $class, bool $normalize = true): static
    {
        if ($normalize) {
            $class = $this->normalizeElementClass($class);
        }

        $this->elementClass = array_merge($this->elementClass, (array) $class);

        return $this;
    }

    /**
     * Add element class.
     *
     * @param  array|string  $class
     * @param  bool  $normalize
     * @return $this
     */
    public function addElementClass(array|string $class, bool $normalize = false): static
    {
        $this->setElementClass($class, $normalize);

        $this->elementClass = array_values(array_unique(array_merge($this->elementClass,
            $this->getDefaultElementClass())));

        return $this;
    }

    /**
     * Get element class.
     *
     * @return array
     */
    public function getElementClass(): array
    {
        if (!$this->elementClass) {
            $this->elementClass = $this->getDefaultElementClass();
        }

        return $this->elementClass;
    }

    /**
     * @return array
     */
    protected function getDefaultElementClass(): array
    {
        return array_merge($this->normalizeElementClass((array) $this->getElementName()), [static::NORMAL_CLASS]);
    }

    /**
     * @param  array|string  $class
     * @return array|string
     */
    public function normalizeElementClass(array|string $class): array|string
    {
        if (is_array($class)) {
            return array_map([$this, 'normalizeElementClass'], $class);
        }

        return static::FIELD_CLASS_PREFIX.str_replace(['[', ']', '->', '.'], '_', $class);
    }

    /**
     * Get element class selector.
     *
     * @return string|array
     */
    public function getElementClassSelector(): array|string
    {
        $elementClass = $this->getElementClass();

        $formId = $this->getFormElementId();
        $formId = $formId ? '#'.$formId : '';

        if (Arr::isAssoc($elementClass)) {
            $classes = [];

            foreach ($elementClass as $index => $class) {
                $classes[$index] = $formId.' .'.(is_array($class) ? implode('.', $class) : $class);
            }

            return $classes;
        }

        return $formId.' .'.implode('.', $elementClass);
    }

    /**
     * Get element class string.
     *
     * @return string|array
     */
    public function getElementClassString(): string|array
    {
        $elementClass = $this->getElementClass();

        if (Arr::isAssoc($elementClass)) {
            $classes = [];

            foreach ($elementClass as $index => $class) {
                $classes[$index] = is_array($class) ? implode(' ', $class) : $class;
            }

            return $classes;
        }

        return implode(' ', $elementClass);
    }

    /**
     * @return $this
     */
    public function hideInDialog(): static
    {
        if (
            $this->form instanceof Form
            && $this->form->inDialog()
        ) {
            $this->display(false);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    protected function getFormElementId(): ?string
    {
        return $this->form?->getElementId();
    }

    /**
     * Remove element class.
     *
     * @param $class
     * @return $this
     */
    public function removeElementClass($class): static
    {
        Helper::deleteByValue($this->elementClass, $class);

        return $this;
    }

    /**
     * @param  array|string  $labelClass
     * @param  bool  $append
     * @return $this
     */
    public function setLabelClass(array|string $labelClass, bool $append = true): static
    {
        $this->labelClass = $append
            ? array_unique(array_merge($this->labelClass, (array) $labelClass))
            : (array) $labelClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelClass(): string
    {
        return implode(' ', $this->labelClass);
    }

    /**
     * @param  mixed  $value
     * @param  callable  $callback
     * @return Field
     */
    public function when(mixed $value, callable $callback): static
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * @param  array|string  $class
     * @param  bool  $append
     * @return $this
     */
    public function setFormGroupClass(array|string $class, bool $append = true): static
    {
        $this->formGroupClass = $append
            ? array_unique(array_merge($this->formGroupClass, (array) $class))
            : (array) $class;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormGroupClass(): string
    {
        return implode(' ', $this->formGroupClass);
    }

    /**
     * @param  array|string  $class
     * @param  bool  $append
     * @return $this
     */
    public function setFieldClass(array|string $class, bool $append = true): static
    {
        $this->fieldClass = $append
            ? array_unique(array_merge($this->fieldClass, (array) $class))
            : (array) $class;

        return $this;
    }

    public function getFieldClass(): string
    {
        return implode(' ', $this->fieldClass);
    }

    /**
     * Get the view variables of this field.
     *
     * @return array
     */
    public function defaultVariables(): array
    {
        return [
            'name' => $this->getElementName(),
            'help' => $this->help,
            'class' => $this->getElementClassString(),
            'value' => $this->value(),
            'label' => $this->label,
            'viewClass' => $this->getViewElementClasses(),
            'column' => $this->column,
            'errorKey' => $this->getErrorKey(),
            'attributes' => $this->formatAttributes(),
            'placeholder' => $this->placeholder(),
            'disabled' => $this->attributes['disabled'] ?? false,
            'formId' => $this->getFormElementId(),
            'selector' => $this->getElementClassSelector(),
            'options' => $this->options,
        ];
    }

    protected function isCreating(): bool
    {
        return request()->isMethod('POST');
    }

    protected function isEditing(): bool
    {
        return request()->isMethod('PUT');
    }

    /**
     * Get view of this field.
     *
     * @return string
     */
    public function view(): string
    {
        return $this->view ?: 'admin::form.'.strtolower(class_basename(static::class));
    }

    /**
     * Set view of current field.
     *
     * @param $view
     * @return string
     */
    public function setView($view): string
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get script of current field.
     *
     * @return string
     */
    public function getScript(): string
    {
        return $this->script;
    }

    /**
     * Set script of current field.
     *
     * @param $script
     * @return self
     */
    public function script($script): static
    {
        $this->script = $script;

        return $this;
    }

    /**
     * To set this field should render or not.
     *
     * @param  bool  $display
     * @return self
     */
    public function display(bool $display): static
    {
        $this->display = $display;

        return $this;
    }

    /**
     * 设置默认属性.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return $this
     */
    public function defaultAttribute(string $attribute, mixed $value): static
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            $this->attribute($attribute, $value);
        }

        return $this;
    }

    /**
     * If this field should render.
     *
     * @return bool
     */
    protected function shouldRender(): bool
    {
        return $this->display;
    }

    /**
     * 保存数据为json格式.
     *
     * @param  int  $option
     * @return $this
     */
    public function saveAsJson(int $option = 0): static
    {
        return $this->saving(function ($value) use ($option) {
            if ($value === null || is_scalar($value)) {
                return $value;
            }

            return json_encode($value, $option);
        });
    }

    /**
     * 保存数据为字符串格式.
     *
     * @return $this
     */
    public function saveAsString(): static
    {
        return $this->saving(function ($value) {
            if (is_object($value) || is_array($value)) {
                return json_encode($value);
            }

            return (string) $value;
        });
    }

    /**
     * Collect assets required by this field.
     */
    public static function requireAssets(): void
    {
        static::$js && Admin::js(static::$js);
        static::$css && Admin::css(static::$css);
    }

    /**
     * 设置默认class.
     */
    protected function setDefaultClass(): void
    {
        if (is_string($class = $this->getElementClassString())) {
            $this->defaultAttribute('class', $class);
        }
    }

    /**
     * Render this filed.
     *
     * @return string
     * @throws Throwable
     */
    public function render(): string
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $this->setDefaultClass();

        $this->callComposing();

        $this->withScript();

        return Admin::view($this->view(), $this->variables());
    }

    protected function withScript(): void
    {
        if ($this->script) {
            Admin::script($this->script);
        }
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function __toString()
    {
        $view = $this->render();

        return $view instanceof Renderable ? $view->render() : $view;
    }
}
