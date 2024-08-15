<?php

namespace Dcat\Admin\Grid;

use Closure;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Fluent;

class Row implements Arrayable
{
    /**
     * @var Grid
     */
    protected Grid $grid;

    /**
     * Row data.
     *
     * @var Fluent
     */
    protected $data;

    /**
     * Attributes of row.
     *
     * @var array
     */
    protected array $attributes = [];

    public function __construct(Grid $grid, $data)
    {
        $this->grid = $grid;
        $this->data = is_array($data) ? new Fluent($data) : $data;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return array|string
     */
    public function getKey(): array|string
    {
        return $this->data->{$this->grid->getKeyName()};
    }

    /**
     * Get attributes in html format.
     *
     * @return string
     */
    public function rowAttributes(): string
    {
        return $this->formatHtmlAttributes($this->attributes);
    }

    /**
     * Get column attributes.
     *
     * @param  string  $column
     * @return string
     */
    public function columnAttributes(string $column): string
    {
        if (
            ($column = $this->grid->columns()->get($column))
            && ($attributes = $column->getAttributes())
        ) {
            return $this->formatHtmlAttributes($attributes);
        }

        return '';
    }

    /**
     * Format attributes to html.
     *
     * @param  array  $attributes
     * @return string
     */
    private function formatHtmlAttributes(array $attributes = []): string
    {
        return Helper::buildHtmlAttributes($attributes);
    }

    /**
     * Set attributes.
     *
     * @param  array  $attributes
     * @return \Dcat\Admin\Grid\Row
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Set style of the row.
     *
     * @param  array|string  $style
     */
    public function style(array|string $style): void
    {
        if (is_array($style)) {
            $style = implode('', array_map(function ($key, $val) {
                return "$key:$val";
            }, array_keys($style), array_values($style)));
        }

        if (is_string($style)) {
            $this->attributes['style'] = $style;
        }
    }

    /**
     * Get data of this row.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model(): \Illuminate\Database\Eloquent\Model
    {
        return $this->data;
    }

    /**
     * Getter.
     *
     * @param  string  $attr
     * @return mixed
     */
    public function __get(string $attr)
    {
        return $this->data->{$attr};
    }

    /**
     * Setter.
     *
     * @param  string  $attr
     * @param  mixed  $value
     * @return void
     */
    public function __set(string $attr, mixed $value)
    {
        $this->data[$attr] = $value;
    }

    /**
     * Get or set value of column in this row.
     *
     * @param  string  $name
     * @param  mixed|null  $value
     * @return float|\Illuminate\Support\Carbon|bool|int|string|\Dcat\Admin\Grid\Row|null
     */
    public function column(string $name, mixed $value = null): float|Carbon|bool|int|string|null|static
    {
        if (is_null($value)) {
            return $this->output(
                Arr::get($this->data, $name)
            );
        }

        if ($value instanceof Closure) {
            $value = $value->call($this, $this->column($name));
        }

        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->data->toArray();
    }

    /**
     * Output column value.
     *
     * @param  mixed  $value
     * @return bool|float|\Illuminate\Support\Carbon|int|string|null
     */
    protected function output(mixed $value): float|Carbon|bool|int|string|null
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof Renderable) {
            $value = $value->render();
        }

        if ($value instanceof Htmlable) {
            $value = $value->toHtml();
        }

        if ($value instanceof Jsonable) {
            $value = $value->toJson();
        }

        if (! is_null($value) && ! is_scalar($value)) {
            return sprintf('<pre class="dump">%s</pre>',
                json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return $value;
    }
}
