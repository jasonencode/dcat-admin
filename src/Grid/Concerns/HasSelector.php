<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Tools\Selector;
use Dcat\Admin\Support\Helper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @mixin Grid
 */
trait HasSelector
{
    /**
     * @var Selector|null
     */
    protected Selector|null $_selector = null;

    /**
     * @param  Closure|null  $closure
     * @return $this|Selector
     */
    public function selector(Closure $closure = null): Selector|static
    {
        if ($closure === null) {
            return $this->_selector;
        }

        $this->_selector = new Selector($this);

        call_user_func($closure, $this->_selector);

        $this->header(function () {
            return $this->renderSelector();
        });

        return $this;
    }

    /**
     * Apply selector query to grid model query.
     *
     * @return $this
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function applySelectorQuery(): static
    {
        if (is_null($this->_selector)) {
            return $this;
        }

        $active = $this->_selector->parseSelected();

        $this->_selector->all()->each(function ($selector, $column) use ($active) {
            $key = $this->_selector->formatKey($column);

            if (!array_key_exists($key, $active)) {
                return;
            }

            $this->fireOnce(new Grid\Events\ApplySelector([$active]));

            $values = $active[$key];
            if ($selector['type'] == 'one') {
                $values = current($values);
            }

            if ($selector['query']) {
                call_user_func($selector['query'], $this->model(), $values);

                return;
            }

            Helper::withQueryCondition(
                $this->model(),
                $column,
                is_array($values) ? 'whereIn' : 'where',
                [$values]
            );
        });

        return $this;
    }

    /**
     * Render grid selector.
     *
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function renderSelector(): string
    {
        return $this->_selector->render();
    }
}
