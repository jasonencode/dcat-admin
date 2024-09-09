<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Support\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Select extends Field
{
    use CanCascadeFields;
    use CanLoadFields;
    use Sizeable;

    protected string $cascadeEvent = 'change';

    /**
     * @var array
     */
    protected array $groups = [];

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * Set options.
     *
     * @param  array|Collection|Closure  $options
     * @return Select
     * @throws RuntimeException
     */
    public function options(array|Collection|Closure $options = []): static
    {
        if ($options instanceof Closure) {
            $this->options = $options;

            return $this;
        }

        // remote options
        if (is_string($options)) {
            // reload selected
            if (class_exists($options) && in_array(Model::class, class_parents($options))) {
                return $this->model(...func_get_args());
            }

            return $this->loadRemoteOptions(...func_get_args());
        }

        $this->options = Helper::array($options);

        return $this;
    }

    /**
     * @param  array  $groups
     */

    /**
     * Set option groups.
     *
     * eg: $group = [
     *        [
     *        'label' => 'xxxx',
     *        'options' => [
     *            1 => 'foo',
     *            2 => 'bar',
     *            ...
     *        ],
     *        ...
     *     ]
     *
     * @param  array  $groups
     * @return $this
     */
    public function groups(array $groups): static
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Load options from current selected resource(s).
     *
     * @param  string  $model
     * @param  string  $idField
     * @param  string  $textField
     * @return $this
     * @throws RuntimeException
     */
    public function model(string $model, string $idField = 'id', string $textField = 'name'): static
    {
        if (!class_exists($model)
            || !in_array(Model::class, class_parents($model))
        ) {
            throw new RuntimeException("[$model] must be a valid model class");
        }

        $this->options = function ($value) use ($model, $idField, $textField) {
            if (empty($value)) {
                return [];
            }

            $resources = [];

            if (is_array($value)) {
                if (Arr::isAssoc($value)) {
                    $resources[] = Arr::get($value, $idField);
                } else {
                    $resources = array_column($value, $idField);
                }
            } else {
                $resources[] = $value;
            }

            return $model::whereIn($idField, $resources)->pluck($textField, $idField)->toArray();
        };

        return $this;
    }

    /**
     * Load options from remote.
     *
     * @param  string  $url
     * @param  array  $parameters
     * @param  array  $options
     * @return $this
     */
    protected function loadRemoteOptions(string $url, array $parameters = [], array $options = []): static
    {
        $ajaxOptions = [
            'url' => admin_url($url.'?'.http_build_query($parameters)),
        ];

        $ajaxOptions = array_merge($ajaxOptions, $options);

        return $this->addVariables(['remoteOptions' => $ajaxOptions]);
    }

    /**
     * @param  array|string  $key
     * @param  mixed|null  $value
     * @return $this
     */
    public function addDefaultConfig(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->addDefaultConfig($k, $v);
            }

            return $this;
        }

        if (!isset($this->config[$key])) {
            $this->config[$key] = $value;
        }

        return $this;
    }

    /**
     * Load options from ajax results.
     *
     * @param  string  $url
     * @param  string  $idField
     * @param  string  $textField
     * @return $this
     */
    public function ajax(string $url, string $idField = 'id', string $textField = 'text'): static
    {
        $this->addDefaultConfig([
            'minimumInputLength' => 1,
        ]);

        $url = admin_url($url);

        return $this->addVariables(['ajax' => compact('url', 'idField', 'textField')]);
    }

    /**
     * Set config for select2.
     *
     * all configurations see https://select2.org/configuration/options-api
     *
     * @param  string  $key
     * @param  mixed  $val
     * @return $this
     */
    public function config(string $key, mixed $val): static
    {
        $this->config[$key] = $val;

        return $this;
    }

    /**
     * Disable clear button.
     *
     * @return $this
     */
    public function disableClearButton(): static
    {
        return $this->config('allowClear', false);
    }

    /**
     * {@inheritdoc}
     * @throws RuntimeException
     */
    public function render(): string
    {
        $this->addDefaultConfig([
            'allowClear' => true,
            'placeholder' => [
                'id' => '',
                'text' => $this->placeholder(),
            ],
        ]);

        $this->formatOptions();

        $this->addVariables([
            'options' => $this->options,
            'groups' => $this->groups,
            'configs' => $this->config,
            'cascadeScript' => $this->getCascadeScript(),
        ]);

        $this->initSize();

        $this->attribute('data-value', implode(',', Helper::array($this->value())));

        return parent::render();
    }

    /**
     * @throws RuntimeException
     */
    protected function formatOptions(): void
    {
        if ($this->options instanceof Closure) {
            $this->options = $this->options->bindTo($this->values());

            $this->options(call_user_func($this->options, $this->value(), $this));
        }

        $this->options = array_filter($this->options, 'strlen');
    }

    /**
     * {@inheritdoc}
     */
    public function placeholder(string $placeholder = null): array|string|static
    {
        if ($placeholder === null) {
            return $this->placeholder ?: $this->label;
        }

        $this->placeholder = $placeholder;

        return $this;
    }
}
