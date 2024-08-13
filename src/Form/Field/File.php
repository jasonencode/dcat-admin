<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Contracts\UploadField as UploadFieldInterface;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Support\JavaScript;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class File extends Field implements UploadFieldInterface
{
    use WebUploader,
        UploadField;

    protected $view = 'admin::form.file';

    /**
     * @var array
     */
    protected $options = [
        'events'   => [],
        'override' => false,
    ];

    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);

        $this->setUpDefaultOptions();
    }

    public function setElementName($name): File
    {
        $this->mergeOptions(['elementName' => $name]);

        return parent::setElementName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(array $input)
    {
        if (request()->has(static::FILE_DELETE_FLAG)) {
            return false;
        }

        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        if (! Arr::has($input, $this->column)) {
            return false;
        }

        $value = Arr::get($input, $this->column);
        $value = array_filter(is_array($value) ? $value : explode(',', $value));

        $rules      = $attributes = [];
        $requiredIf = null;

        $fileLimit = $this->options['fileNumLimit'] ?? 1;
        if (! empty($value) && $fileLimit > 1) {
            $rules[$this->column][] = function ($atribute, $value, $fail) use ($fileLimit) {
                $value = array_filter(is_array($value) ? $value : explode(',', $value));
                if (count($value) > $fileLimit) {
                    $fail(trans('admin.uploader.max_file_limit', ['attribute' => $this->label, 'max' => $fileLimit]));
                }
            };
            return Validator::make($input, $rules, $this->getValidationMessages(), $attributes);
        }

        if (! $this->hasRule('required') && ! $requiredIf = $this->getRule('required_if*')) {
            return false;
        }

        $rules[$this->column]      = $requiredIf ?: 'required';
        $attributes[$this->column] = $this->label;

        return Validator::make($input, $rules, $this->getValidationMessages(), $attributes);
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareInputValue($value)
    {
        if (request()->has(static::FILE_DELETE_FLAG)) {
            $this->destroy();
            return $value;
        }

        $this->destroyIfChanged($value);

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function setRelation(array $options = []): File|Field|static
    {
        $this->options['formData']['_relation'] = [$options['relation'], $options['key'] ?? null];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function disable(bool $value = true): File|Field|static
    {
        $this->options['disabled'] = $value;

        return $this;
    }

    protected function formatFieldData($data)
    {
        return Helper::array($this->getValueFromData($data));
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function initialPreviewConfig(): array
    {
        $previews = [];

        foreach (Helper::array($this->value()) as $value) {
            $previews[] = [
                'id'   => $value,
                'path' => Helper::basename($value),
                'url'  => $this->objectUrl($value),
            ];
        }

        return $previews;
    }

    protected function forceOptions(): void
    {
        $this->options['fileNumLimit'] = 1;
    }

    /**
     * {@inheritDoc}
     */
    public function render(): string
    {
        $this->setDefaultServer();

        if (! empty($this->value())) {
            $this->setupPreviewOptions();
        }

        $this->forceOptions();
        $this->formatValue();

        $this->addVariables([
            'fileType'      => $this->options['isImage'] ? '' : 'file',
            'showUploadBtn' => ! (($this->options['autoUpload'] ?? false)),
            'options'       => JavaScript::format($this->options),
        ]);

        return parent::render();
    }

    /**
     * @return void
     */
    protected function formatValue(): void
    {
        if ($this->value !== null) {
            $this->value = implode(',', Helper::array($this->value));
        } elseif (is_array($this->default)) {
            $this->default = implode(',', $this->default);
        }
    }

    /**
     * Webuploader 事件监听.
     *
     * @see http://fex.baidu.com/webuploader/doc/index.html#WebUploader_Uploader_events
     *
     * @param  string  $event
     * @param  string  $script
     * @param  bool  $once
     * @return $this
     */
    public function on(string $event, string $script, bool $once = false): static
    {
        $script = JavaScript::make($script);

        $this->options['events'][] = compact('event', 'script', 'once');

        return $this;
    }

    /**
     * Webuploader 事件监听(once).
     *
     * @see http://fex.baidu.com/webuploader/doc/index.html#WebUploader_Uploader_events
     *
     * @param  string  $event
     * @param  string  $script
     * @return $this
     */
    public function once(string $event, string $script): static
    {
        return $this->on($event, $script, true);
    }

    /**
     * @param  Field  $field
     * @param  array|string  $fieldRules
     * @return void
     */
    public static function deleteRules(Field $field, array|string &$fieldRules): void
    {
        if ($field instanceof self) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            Helper::deleteContains($fieldRules, ['image', 'file', 'dimensions', 'size', 'max', 'min']);
        }
    }

    public function override(bool $override = true): static
    {
        $this->options['override'] = $override;

        return $this;
    }
}
