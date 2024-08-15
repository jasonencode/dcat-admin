<?php

namespace Dcat\Admin\Grid\Tools;

use Closure;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Selector
{
    /**
     * @var Grid
     */
    protected Grid $grid;

    /**
     * @var Request
     */
    protected mixed $request;

    /**
     * @var array|Collection
     */
    protected Collection|array $selectors = [];

    /**
     * @var array
     */
    protected array $selected;

    /**
     * @var string
     */
    protected string $queryNameSuffix = '_selector';

    /**
     * Selector constructor.
     */
    public function __construct(Grid $grid)
    {
        $this->grid      = $grid;
        $this->request   = request();
        $this->selectors = new Collection();
    }

    /**
     * @param  string  $column
     * @param  array|string  $label
     * @param  array|\Closure  $options
     * @param  null|\Closure  $query
     * @return $this
     */
    public function select(string $column, array|string $label, array|Closure $options = [], ?Closure $query = null): static
    {
        return $this->addSelector($column, $label, $options, $query);
    }

    /**
     * @param  string  $column
     * @param  array|string  $label
     * @param  array  $options
     * @param  null|\Closure  $query
     * @return $this
     */
    public function selectOne(string $column, array|string $label, array $options = [], ?Closure $query = null): static
    {
        return $this->addSelector($column, $label, $options, $query, 'one');
    }

    /**
     * @param  string  $column
     * @param  string  $label
     * @param  array  $options
     * @param  \Closure|null  $query
     * @param  string  $type
     * @return $this
     */
    protected function addSelector(string $column, string $label, array $options = [], ?Closure $query = null, string $type = 'many'): static
    {
        if (is_array($label)) {
            if ($options instanceof Closure) {
                $query = $options;
            }

            $options = $label;
            $label   = admin_trans_field($column);
        }

        $this->selectors[$column] = compact(
            'label',
            'options',
            'type',
            'query'
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getQueryName(): string
    {
        return $this->grid->makeName($this->queryNameSuffix);
    }

    /**
     * Get all selectors.
     *
     * @param  bool  $formatKey
     * @return array|Collection
     */
    public function all(bool $formatKey = false): array|Collection
    {
        if ($formatKey) {
            return $this->selectors->mapWithKeys(function ($v, $k) {
                return [$this->formatKey($k) => $v];
            });
        }

        return $this->selectors;
    }

    /**
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function parseSelected(): array
    {
        if (! is_null($this->selected)) {
            return $this->selected;
        }

        $selected = $this->request->get($this->getQueryName(), []);
        if (! is_array($selected)) {
            return [];
        }

        $selected = array_filter($selected, function ($value) {
            return ! is_null($value);
        });

        foreach ($selected as &$value) {
            $value = explode(',', $value);

            foreach ($value as &$v) {
                $v = $v;
            }
        }

        return $this->selected = $selected;
    }

    public function formatKey($column): array|string
    {
        return str_replace('.', '_', $column);
    }

    /**
     * @param  string  $column
     * @param  mixed|null  $value
     * @param  bool  $add
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function url(string $column, mixed $value = null, bool $add = false): string
    {
        $column = $this->formatKey($column);

        $query = $this->request->query();

        $query[$this->grid->model()->getPageName()] = null;

        $selected  = $this->parseSelected();
        $options   = Arr::get($selected, $column, []);
        $queryName = "{$this->getQueryName()}.$column";

        if (is_null($value)) {
            Arr::forget($query, $queryName);

            return $this->request->fullUrlWithQuery($query);
        }

        if (in_array((string) $value, $options, true)) {
            Helper::deleteByValue($options, (string) $value, true);
        } else {
            if ($add) {
                $options = [];
            }
            $options[] = $value;
        }

        if (! empty($options)) {
            Arr::set($query, $queryName, implode(',', $options));
        } else {
            Arr::forget($query, $queryName);
        }

        return $this->request->fullUrlWithQuery($query);
    }

    /**
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function render(): string
    {
        return view('admin::grid.selector', [
            'self'     => $this,
            'selected' => $this->parseSelected(),
        ]);
    }
}
