<?php

namespace Dcat\Admin\Traits;

use Dcat\Admin\Admin;

trait HasBuilderEvents
{
    public static function resolving(callable $callback, bool $once = false): void
    {
        static::addBuilderListeners('builder:resolving', $callback, $once);
    }

    protected function callResolving(...$params): void
    {
        $this->fireBuilderEvent('builder:resolving', ...$params);
    }

    public static function composing(callable $callback, bool $once = false): void
    {
        static::addBuilderListeners('builder:composing', $callback, $once);
    }

    protected function callComposing(...$params): void
    {
        $this->fireBuilderEvent('builder:composing', ...$params);
    }

    protected function fireBuilderEvent($key, ...$params): void
    {
        $context = Admin::context();

        $key = static::formatEventKey($key);

        $listeners = $context->get($key) ?: [];

        foreach ($listeners as $k => $listener) {
            [$callback, $once] = $listener;

            if ($once) {
                unset($listeners[$k]);
            }

            call_user_func($callback, $this, ...$params);
        }

        $context[$key] = $listeners;
    }

    protected static function addBuilderListeners($key, $callback, $once): void
    {
        $context = Admin::context();

        $key = static::formatEventKey($key);

        $listeners = $context->get($key) ?: [];

        $listeners[] = [$callback, $once];

        $context[$key] = $listeners;
    }

    protected static function formatEventKey($key): string
    {
        return static::class.':'.$key;
    }
}
