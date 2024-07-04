<?php

namespace Dcat\Admin\Traits;

use Dcat\Admin\Actions\Action;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Widgets\Widget;
use Illuminate\Support\Arr;

trait HasHtmlAttributes
{
    protected array $htmlAttributes = [];

    public function defaultHtmlAttribute($attribute, $value): static
    {
        if (! array_key_exists($attribute, $this->htmlAttributes)) {
            $this->setHtmlAttribute($attribute, $value);
        }

        return $this;
    }

    public function setHtmlAttribute($key, $value = null): static
    {
        if (is_array($key)) {
            $this->htmlAttributes = array_merge($this->htmlAttributes, $key);

            return $this;
        }
        $this->htmlAttributes[$key] = $value;

        return $this;
    }

    public function appendHtmlAttribute($key, $value): Form|Widget|Action
    {
        $result = $this->getHtmlAttribute($key);

        if (is_array($result)) {
            $result[] = $value;
        } else {
            $result = "$result $value";
        }

        return $this->setHtmlAttribute($key, $result);
    }

    public function forgetHtmlAttribute($keys): static
    {
        Arr::forget($this->htmlAttributes, $keys);

        return $this;
    }

    public function getHtmlAttributes(): array
    {
        return $this->htmlAttributes;
    }

    public function getHtmlAttribute($key, $default = null)
    {
        return $this->htmlAttributes[$key] ?? $default;
    }

    public function hasHtmlAttribute($key): bool
    {
        return array_key_exists($key, $this->htmlAttributes);
    }

    public function formatHtmlAttributes(): string
    {
        return Helper::buildHtmlAttributes($this->htmlAttributes);
    }
}
