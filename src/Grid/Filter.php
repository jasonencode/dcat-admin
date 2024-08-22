<?php

namespace Dcat\Admin\Grid;

use Dcat\Admin\Admin;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Events\ApplyFilter;
use Dcat\Admin\Grid\Events\Fetched;
use Dcat\Admin\Grid\Events\Fetching;
use Dcat\Admin\Grid\Filter\AbstractFilter;
use Dcat\Admin\Grid\Filter\Between;
use Dcat\Admin\Grid\Filter\Date;
use Dcat\Admin\Grid\Filter\Day;
use Dcat\Admin\Grid\Filter\EndWith;
use Dcat\Admin\Grid\Filter\Equal;
use Dcat\Admin\Grid\Filter\FindInSet;
use Dcat\Admin\Grid\Filter\Group;
use Dcat\Admin\Grid\Filter\Gt;
use Dcat\Admin\Grid\Filter\Hidden;
use Dcat\Admin\Grid\Filter\Ilike;
use Dcat\Admin\Grid\Filter\In;
use Dcat\Admin\Grid\Filter\Layout\Layout;
use Dcat\Admin\Grid\Filter\Like;
use Dcat\Admin\Grid\Filter\Lt;
use Dcat\Admin\Grid\Filter\Month;
use Dcat\Admin\Grid\Filter\Newline;
use Dcat\Admin\Grid\Filter\Ngt;
use Dcat\Admin\Grid\Filter\Nlt;
use Dcat\Admin\Grid\Filter\NotEqual;
use Dcat\Admin\Grid\Filter\NotIn;
use Dcat\Admin\Grid\Filter\Scope;
use Dcat\Admin\Grid\Filter\StartWith;
use Dcat\Admin\Grid\Filter\Where;
use Dcat\Admin\Grid\Filter\WhereBetween;
use Dcat\Admin\Grid\Filter\Year;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasBuilderEvents;
use Dcat\Admin\Traits\HasVariables;
use Exception;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Throwable;

/**
 * Class Filter.
 *
 * @method Equal equal($column, $label = '')
 * @method NotEqual notEqual($column, $label = '')
 * @method Like like($column, $label = '')
 * @method Ilike ilike($column, $label = '')
 * @method StartWith startWith($column, $label = '')
 * @method EndWith endWith($column, $label = '')
 * @method Gt gt($column, $label = '')
 * @method Lt lt($column, $label = '')
 * @method Ngt ngt($column, $label = '')
 * @method Nlt nlt($column, $label = '')
 * @method Between between($column, $label = '')
 * @method In in($column, $label = '')
 * @method NotIn notIn($column, $label = '')
 * @method Where where($colum, $callback, $label = '')
 * @method WhereBetween whereBetween($colum, $callback, $label = '')
 * @method Date date($column, $label = '')
 * @method Day day($column, $label = '')
 * @method Month month($column, $label = '')
 * @method Year year($column, $label = '')
 * @method Hidden hidden($name, $value)
 * @method Group group($column, $builder = null, $label = '')
 * @method Newline newline()
 * @method FindInSet findInSet($column, $label = '')
 */
class Filter implements Renderable
{
    use HasBuilderEvents;
    use Macroable;
    use HasVariables;

    const MODE_RIGHT_SIDE = 'right-side';
    const MODE_PANEL      = 'panel';

    /**
     * @var array
     */
    protected static array $supports = [];

    /**
     * @var array
     */
    protected static array $defaultFilters = [
        'equal'        => Equal::class,
        'notEqual'     => NotEqual::class,
        'ilike'        => Ilike::class,
        'like'         => Like::class,
        'startWith'    => StartWith::class,
        'endWith'      => EndWith::class,
        'gt'           => Gt::class,
        'lt'           => Lt::class,
        'ngt'          => Ngt::class,
        'nlt'          => Nlt::class,
        'between'      => Between::class,
        'group'        => Group::class,
        'where'        => Where::class,
        'whereBetween' => WhereBetween::class,
        'in'           => In::class,
        'notIn'        => NotIn::class,
        'date'         => Date::class,
        'day'          => Day::class,
        'month'        => Month::class,
        'year'         => Year::class,
        'hidden'       => Hidden::class,
        'newline'      => Newline::class,
        'findInSet'    => FindInSet::class,
    ];

    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @var AbstractFilter[]
     */
    protected array $filters = [];

    /**
     * Action of search form.
     *
     * @var string
     */
    protected string $action = '';

    /**
     * @var string
     */
    protected string $view = '';

    /**
     * @var string
     */
    protected string $filterID;

    /**
     * @var string
     */
    protected string $name = '';

    /**
     * @var bool
     */
    public bool $expand = false;

    /**
     * @var Collection
     */
    protected Collection $scopes;

    /**
     * @var Layout
     */
    protected Layout $layout;

    /**
     * Primary key of giving model.
     *
     * @var array|string
     */
    protected array|string $primaryKey;

    /**
     * @var string
     */
    protected string $style = 'padding:0';

    /**
     * @var bool
     */
    protected bool $disableResetButton = false;

    /**
     * @var string
     */
    protected string $border = 'border-top:1px solid #f4f4f4;';

    /**
     * @var string
     */
    protected string $containerClass = '';

    /**
     * @var bool
     */
    protected bool $disableCollapse = false;

    /**
     * @var array
     */
    protected array $inputs = [];

    /**
     * @var string
     */
    protected string $mode = self::MODE_RIGHT_SIDE;

    protected array $conditions = [];

    /**
     * Create a new filter instance.
     *
     * @param  Model  $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        $this->primaryKey = $model->getKeyName();

        $this->filterID = $this->formatFilterId();

        $this->initLayout();

        $this->scopes = new Collection();

        $this->callResolving();
    }

    /**
     * Initialize filter layout.
     */
    protected function initLayout(): void
    {
        $this->layout = new Filter\Layout\Layout($this);
    }

    /**
     * @return string
     */
    protected function formatFilterId(): string
    {
        return 'filter-box'.Str::random(8);
    }

    /**
     * Set action of search form.
     *
     * @param  string  $action
     * @return $this
     */
    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return $this
     */
    public function withoutInputBorder(): static
    {
        $this->containerClass = 'input-no-border';

        return $this;
    }

    /**
     * @param  bool  $disabled
     */
    public function disableCollapse(bool $disabled = true): void
    {
        $this->disableCollapse = $disabled;
    }

    /**
     * @param  bool  $disabled
     */
    public function disableResetButton(bool $disabled = true): void
    {
        $this->disableResetButton = $disabled;
    }

    /**
     * Get input data.
     *
     * @param  null  $key
     * @param  null  $default
     * @return array|mixed
     */
    public function input($key = null, $default = null): mixed
    {
        $inputs = $this->inputs();

        if ($key === null) {
            return $inputs;
        }

        return Arr::get($inputs, $key, $default);
    }

    /**
     * Get grid model.
     *
     * @return Model
     */
    public function model(): Model
    {
        return $this->model;
    }

    /**
     * Get grid.
     *
     * @return Grid
     */
    public function grid(): Grid
    {
        return $this->model->grid();
    }

    /**
     * Set ID of search form.
     *
     * @param  string  $filterID
     */
    public function setFilterID(string $filterID): void
    {
        $this->filterID = $filterID;
    }

    /**
     * @return string|Filter
     */
    public function panel(): string|static
    {
        return $this->mode(static::MODE_PANEL);
    }

    /**
     * @return string|Filter
     */
    public function rightSide(): string|static
    {
        return $this->mode(static::MODE_RIGHT_SIDE);
    }

    /**
     * @param  string|null  $mode
     * @return $this|string
     */
    public function mode(string $mode = null): string|static
    {
        if ($mode === null) {
            return $this->mode;
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * Get filter ID.
     *
     * @return string
     */
    public function filterID(): string
    {
        return $this->filterID;
    }

    /**
     * @return $this
     */
    public function withoutBorder(): static
    {
        return $this->withBorder('');
    }

    /**
     * @return $this
     */
    public function withBorder($border = null): static
    {
        $this->border = is_null($border) ? 'border-top:1px solid #f4f4f4;' : $border;

        return $this;
    }

    /**
     * Remove filter by column.
     *
     * @param  array|string  $column
     */
    public function removeFilter(array|string $column): void
    {
        $this->filters = array_filter($this->filters, function (AbstractFilter $filter) use (&$column) {
            if (is_array($column)) {
                return ! in_array($filter->column(), $column);
            }

            return $filter->column() != $column;
        });
    }

    /**
     * @return array
     */
    public function inputs(): array
    {
        if (! blank($this->inputs)) {
            return $this->inputs;
        }

        $this->inputs = Arr::dot(request()->all());

        $this->inputs = array_filter($this->inputs, function ($input) {
            return $input !== '' && ! is_null($input);
        });

        $this->sanitizeInputs($this->inputs);

        return $this->inputs;
    }

    /**
     * Get all conditions of the filters.
     *
     * @return array
     */
    public function getConditions(): array
    {
        $inputs = $this->inputs();

        if (empty($inputs)) {
            return [];
        }

        if ($this->conditions !== null) {
            return $this->conditions;
        }

        $params = [];

        foreach ($inputs as $key => $value) {
            Arr::set($params, $key, $value);
        }

        $conditions = [];

        foreach ($this->filters() as $filter) {
            $conditions[] = $filter->condition($params);
        }

        return tap(array_filter($conditions), function ($conditions) {
            if (! empty($conditions)) {
                if ($this->expand === false || $this->mode !== static::MODE_RIGHT_SIDE) {
                    $this->expand();
                }

                $this->grid()->fireOnce(new ApplyFilter([$conditions]));

                $this->grid()->model()->disableBindTreeQuery();
            }

            $this->conditions = $conditions;
        });
    }

    /**
     * @param  array  $inputs
     * @return void
     */
    protected function sanitizeInputs(array &$inputs): void
    {
        if (! $prefix = $this->grid()->getNamePrefix()) {
            return;
        }

        $inputs = collect($inputs)->filter(function ($input, $key) use ($prefix) {
            return Str::startsWith($key, $prefix);
        })->mapWithKeys(function ($val, $key) use ($prefix) {
            $key = str_replace($prefix, '', $key);

            return [$key => $val];
        })->toArray();
    }

    /**
     * Add a filter to grid.
     *
     * @param  AbstractFilter  $filter
     * @return AbstractFilter
     */
    protected function addFilter(AbstractFilter $filter): AbstractFilter
    {
        $this->layout->addFilter($filter);

        $filter->setParent($this);

        return $this->filters[] = $filter;
    }

    /**
     * Use a custom filter.
     *
     * @param  AbstractFilter  $filter
     * @return AbstractFilter
     */
    public function use(AbstractFilter $filter): AbstractFilter
    {
        return $this->addFilter($filter);
    }

    /**
     * Get all filters.
     *
     * @return AbstractFilter[]
     */
    public function filters(): array
    {
        return $this->filters;
    }

    /**
     * 统计查询条件的数量.
     *
     * @return int
     */
    public function countConditions(): int
    {
        return $this->mode() === Filter::MODE_RIGHT_SIDE
            ? count($this->getConditions()) : 0;
    }

    /**
     * @param  string  $key
     * @param  string  $label
     * @return Scope
     */
    public function scope(string $key, string $label = ''): Scope
    {
        $scope = new Scope($this, $key, $label);

        $this->scopes->push($scope);

        return $scope;
    }

    /**
     * @return string
     */
    public function getScopeQueryName(): string
    {
        return $this->grid()->makeName('_scope_');
    }

    /**
     * Get all filter scopes.
     *
     * @return Collection
     */
    public function scopes(): Collection
    {
        return $this->scopes;
    }

    /**
     * Get current scope.
     *
     * @return Scope|null
     */
    public function getCurrentScope(): ?Scope
    {
        $key = $this->getCurrentScopeName();

        return $this->scopes->first(function ($scope) use ($key) {
            return $scope->key == $key;
        });
    }

    /**
     * Get the name of current scope.
     *
     * @return string|null
     */
    public function getCurrentScopeName(): ?string
    {
        return request($this->getScopeQueryName());
    }

    /**
     * Get scope conditions.
     *
     * @return array
     */
    protected function getScopeConditions(): array
    {
        if ($scope = $this->getCurrentScope()) {
            return $scope->condition();
        }

        return [];
    }

    /**
     * Expand filter container.
     *
     * @param  bool  $value
     * @return $this
     */
    public function expand(bool $value = true): static
    {
        $this->expand = $value;

        return $this;
    }

    /**
     * Execute the filter with conditions.
     *
     * @return Collection|mixed
     * @throws Exception
     */
    public function execute(): mixed
    {
        $conditions = array_merge(
            $this->getConditions(),
            $this->getScopeConditions()
        );

        $this->model->addConditions($conditions);

        $this->grid()->fireOnce(new Fetching());

        $data = $this->model->buildData();

        $this->grid()->fireOnce(new Fetched([&$data]));

        return $data;
    }

    /**
     * @param  string  $top
     * @param  string  $right
     * @param  string  $bottom
     * @param  string  $left
     * @return Filter
     */
    public function padding(
        string $top = '15px',
        string $right = '15px',
        string $bottom = '5px',
        string $left = ''
    ): static {
        return $this->style("padding:$top $right $bottom $left");
    }

    /**
     * @param  string|null  $style
     * @return $this
     */
    public function style(?string $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return $this
     */
    public function noPadding(): static
    {
        return $this->style('padding:0;left:-4px;');
    }

    /**
     * @return $this
     */
    public function hiddenResetButtonText(): static
    {
        Admin::style(".$this->containerClass a.reset .d-none d-sm-inline{display:none}");

        return $this;
    }

    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Get the string contents of the filter view.
     *
     * @return string
     * @throws Throwable
     */
    public function render(): string
    {
        $this->grid()->callBuilder();

        if (empty($this->filters)) {
            return '';
        }

        $this->callComposing();

        if (! $this->view) {
            $this->view = $this->mode === static::MODE_RIGHT_SIDE ? 'admin::filter.right-side-container' : 'admin::filter.container';
        }

        return view($this->view)->with($this->variables())->render();
    }

    protected function defaultVariables(): array
    {
        return [
            'action'             => $this->action ?: $this->urlWithoutFilters(),
            'layout'             => $this->layout,
            'filterID'           => $this->disableCollapse ? '' : $this->filterID,
            'expand'             => $this->expand,
            'style'              => $this->style,
            'border'             => $this->border,
            'containerClass'     => $this->containerClass,
            'disableResetButton' => $this->disableResetButton,
        ];
    }

    /**
     * Get url without filter queryString.
     *
     * @return string
     */
    public function urlWithoutFilters(): string
    {
        $filters = collect($this->filters);

        /** @var Collection $columns */
        $columns = $filters->map->getElementName()->flatten();

        $columns->push(
            $this->grid()->model()->getPageName()
        );

        $groupNames = $filters->filter(function ($filter) {
            return $filter instanceof Group;
        })->map(function (AbstractFilter $filter) {
            return "{$filter->getId()}_group";
        });

        return Helper::fullUrlWithoutQuery(
            $columns->merge($groupNames)
        );
    }

    /**
     * Get url without scope queryString.
     *
     * @return string
     */
    public function urlWithoutScopes(): string
    {
        return Helper::fullUrlWithoutQuery($this->getScopeQueryName());
    }

    /**
     * Generate a filter object and add to grid.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return AbstractFilter|$this
     * @throws RuntimeException
     */
    public function __call($method, $parameters)
    {
        if (! empty(static::$supports[$method])) {
            $class = static::$supports[$method];
            if (! is_subclass_of($class, AbstractFilter::class)) {
                throw new RuntimeException("The class [$class] must be a type of ".AbstractFilter::class.'.');
            }

            return $this->addFilter(new $class(...$parameters));
        }

        if (isset(static::$defaultFilters[$method])) {
            return $this->addFilter(new static::$defaultFilters[$method](...$parameters));
        }

        return $this;
    }

    /**
     * @param  string  $name
     * @param  string  $filterClass
     */
    public static function extend(string $name, string $filterClass): void
    {
        static::$supports[$name] = $filterClass;
    }

    /**
     * @return array
     */
    public static function extensions(): array
    {
        return static::$supports;
    }
}
