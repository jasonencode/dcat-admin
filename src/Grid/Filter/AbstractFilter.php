<?php

namespace Dcat\Admin\Grid\Filter;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Grid\Filter;
use Dcat\Admin\Grid\Filter\Presenter\Checkbox;
use Dcat\Admin\Grid\Filter\Presenter\DateTime;
use Dcat\Admin\Grid\Filter\Presenter\MultipleSelect;
use Dcat\Admin\Grid\Filter\Presenter\Presenter;
use Dcat\Admin\Grid\Filter\Presenter\Radio;
use Dcat\Admin\Grid\Filter\Presenter\Select;
use Dcat\Admin\Grid\Filter\Presenter\Text;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Traits\HasVariables;
use Dcat\Laravel\Database\WhereHasInServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Class AbstractFilter.
 *
 * @method Text url()
 * @method Text email()
 * @method Text integer()
 * @method Text decimal($options = [])
 * @method Text currency($options = [])
 * @method Text percentage($options = [])
 * @method Text ip()
 * @method Text mac()
 * @method Text mobile($mask = '19999999999')
 * @method Text inputmask($options = [], $icon = '')
 * @method Text placeholder($placeholder = '')
 */
abstract class AbstractFilter
{
    use HasVariables;

    /**
     * Element id.
     *
     * @var array|string
     */
    protected string|array $id;

    /**
     * Label of presenter.
     *
     * @var string
     */
    protected $label;

    /**
     * @var array|string|null
     */
    protected array|string|null $value = null;

    /**
     * @var array|string|null
     */
    protected array|string|null $defaultValue = null;

    /**
     * @var string
     */
    protected string $column;

    /**
     * Presenter object.
     *
     * @var Presenter|null
     */
    protected ?Presenter $presenter = null;

    /**
     * Query for filter.
     *
     * @var string
     */
    protected $query = 'where';

    /**
     * @var Filter
     */
    protected Filter $parent;

    /**
     * @var int
     */
    protected $width = 10;

    /**
     * @var string
     */
    protected string $style = '';

    /**
     * @var string
     */
    protected string $view = 'admin::filter.where';

    /**
     * @var Collection|null
     */
    public ?Collection $group = null;

    /**
     * @var bool
     */
    protected bool $ignore = false;

    /**
     * AbstractFilter constructor.
     *
     * @param string $column
     * @param string $label
     */
    public function __construct(string $column, string $label = '')
    {
        $this->column = $column;
        $this->label  = $this->formatLabel($label);
    }

    /**
     * Setup default presenter.
     *
     * @return void
     */
    protected function setupDefaultPresenter(): void
    {
        $this->setPresenter(new Text($this->label));
    }

    /**
     * Format label.
     *
     * @param string $label
     * @return string
     */
    protected function formatLabel(string $label): string
    {
        if ($label) {
            return $label;
        }

        $label = admin_trans_field($this->column);

        return str_replace('_', ' ', $label);
    }

    /**
     * Set the column width.
     *
     * @param  int|string  $width
     * @return $this
     */
    public function width(int|string $width): static
    {
        if (is_numeric($width)) {
            $this->width = $width;
        } else {
            $this->style = "width:$width;padding-left:10px;padding-right:10px";
            $this->width = ' ';
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getElementName(): string
    {
        return $this->parent->grid()->makeName($this->originalColumn());
    }

    /**
     * Format name.
     *
     * @param  string  $column
     * @return string
     */
    protected function formatName(string $column): string
    {
        $columns = explode('.', $column);

        if (count($columns) == 1) {
            $name = $columns[0];
        } else {
            $name = array_shift($columns);
            foreach ($columns as $column) {
                $name .= "[$column]";
            }
        }

        return $this->parent->grid()->makeName($name);
    }

    /**
     * Format id.
     *
     * @param  array|string  $columns
     * @return array|string
     */
    protected function formatId(array|string $columns): array|string
    {
        if (is_array($columns)) {
            foreach ($columns as &$column) {
                $column = $this->formatId($column);
            }

            return $columns;
        }

        return $this->parent->grid()->makeName('filter-column-'.str_replace('.', '-', $columns));
    }

    /**
     * @param Filter $filter
     */
    public function setParent(Filter $filter): void
    {
        $this->parent = $filter;

        $this->id = $this->formatId($this->column);
    }

    /**
     * @return Filter
     */
    public function parent(): Filter
    {
        return $this->parent;
    }

    /**
     * Get siblings of current filter.
     *
     * @param null $index
     * @return AbstractFilter[]|mixed
     */
    public function siblings($index = null): mixed
    {
        if (!is_null($index)) {
            return Arr::get($this->parent->filters(), $index);
        }

        return $this->parent->filters();
    }

    /**
     * Get previous filter.
     *
     * @param  int  $step
     * @return AbstractFilter[]|mixed
     */
    public function previous(int $step = 1): mixed
    {
        return $this->siblings(
            array_search($this, $this->parent->filters()) - $step
        );
    }

    /**
     * Get next filter.
     *
     * @param  int  $step
     * @return AbstractFilter[]|mixed
     */
    public function next(int $step = 1): mixed
    {
        return $this->siblings(
            array_search($this, $this->parent->filters()) + $step
        );
    }

    /**
     * Get query condition from filter.
     *
     * @param  array  $inputs
     * @return void
     */
    public function condition(array $inputs)
    {
        $value = Arr::get($inputs, $this->column);

        if ($value === null) {
            return;
        }

        $this->value = $value;

        return $this->buildCondition($this->column, $this->value);
    }

    /**
     * Ignore this query filter.
     *
     * @return $this
     */
    public function ignore(): static
    {
        $this->ignore = true;

        return $this;
    }

    /**
     * Select filter.
     *
     * @param  array  $options
     * @return \Dcat\Admin\Grid\Filter\Presenter\Select|\Dcat\Admin\Grid\Filter\Presenter\Presenter
     */
    public function select(array $options = []): Select|Presenter
    {
        return $this->setPresenter(new Select($options));
    }

    /**
     * @param  array  $options
     * @return \Dcat\Admin\Grid\Filter\Presenter\Presenter|\Dcat\Admin\Grid\Filter\Presenter\MultipleSelect
     */
    public function multipleSelect(array $options = []): Presenter|MultipleSelect
    {
        return $this->setPresenter(new MultipleSelect($options));
    }

    /**
     * @param  LazyRenderable  $table
     * @return \Dcat\Admin\Grid\Filter\Presenter\Presenter|mixed
     */
    public function selectTable(LazyRenderable $table): mixed
    {
        return $this->setPresenter(new Filter\Presenter\SelectTable($table));
    }

    /**
     * @param  LazyRenderable  $table
     * @return \Dcat\Admin\Grid\Filter\Presenter\Presenter|mixed
     */
    public function multipleSelectTable(LazyRenderable $table): mixed
    {
        return $this->setPresenter(new Filter\Presenter\MultipleSelectTable($table));
    }

    /**
     * @param  array  $options
     * @return \Dcat\Admin\Grid\Filter\Presenter\Presenter|\Dcat\Admin\Grid\Filter\Presenter\Radio
     */
    public function radio(array $options = []): Presenter|Radio
    {
        return $this->setPresenter(new Radio($options));
    }

    /**
     * @param  array  $options
     * @return \Dcat\Admin\Grid\Filter\Presenter\Checkbox|\Dcat\Admin\Grid\Filter\Presenter\Presenter
     */
    public function checkbox(array $options = []): Checkbox|Presenter
    {
        return $this->setPresenter(new Checkbox($options));
    }

    /**
     * Datetime filter.
     *
     * @param  array  $options
     * @return \Dcat\Admin\Grid\Filter\Presenter\DateTime|\Dcat\Admin\Grid\Filter\Presenter\Presenter
     */
    public function datetime(array $options = []): DateTime|Presenter
    {
        return $this->setPresenter(new DateTime($options));
    }

    /**
     * Date filter.
     *
     * @return DateTime
     */
    public function date(): DateTime
    {
        return $this->datetime(['format' => 'YYYY-MM-DD']);
    }

    /**
     * Time filter.
     *
     * @return DateTime
     */
    public function time(): DateTime
    {
        return $this->datetime(['format' => 'HH:mm:ss']);
    }

    /**
     * Day filter.
     *
     * @return DateTime
     */
    public function day(): DateTime
    {
        return $this->datetime(['format' => 'DD']);
    }

    /**
     * Month filter.
     *
     * @return DateTime
     */
    public function month(): DateTime
    {
        return $this->datetime(['format' => 'YYYY-MM']);
    }

    /**
     * Year filter.
     *
     * @return DateTime
     */
    public function year(): DateTime
    {
        return $this->datetime(['format' => 'YYYY']);
    }

    /**
     * Set presenter object of filter.
     *
     * @param Presenter $presenter
     * @return mixed
     */
    public function setPresenter(Presenter $presenter): Presenter
    {
        $presenter->setParent($this);

        $presenter::requireAssets();

        return $this->presenter = $presenter;
    }

    /**
     * Get presenter object of filter.
     *
     * @return \Dcat\Admin\Grid\Filter\Presenter\Presenter|null
     */
    protected function presenter(): ?Presenter
    {
        if (!$this->presenter) {
            $this->setupDefaultPresenter();
        }

        return $this->presenter;
    }

    /**
     * Set default value for filter.
     *
     * @param null $default
     * @return $this
     */
    public function default($default = null): static
    {
        if (filled($default)) {
            $this->defaultValue = $default;
        }

        return $this;
    }

    public function getDefault(): array|string|null
    {
        return $this->defaultValue;
    }

    /**
     * Get element id.
     *
     * @return array|string
     */
    public function getId(): array|string
    {
        return $this->id;
    }

    /**
     * Set element id.
     *
     * @param  string  $id
     * @return $this
     */
    public function setId(string $id): static
    {
        $this->id = $this->formatId($id);

        return $this;
    }

    /**
     * Get column name of current filter.
     *
     * @return string
     */
    public function column(): string
    {
        return $this->formatColumnClass($this->column);
    }

    public function originalColumn(): string
    {
        return $this->column;
    }

    /**
     * @param  string  $column
     * @return string
     */
    public function formatColumnClass(string $column): string
    {
        return $this->parent->grid()->makeName(str_replace('.', '-', $column));
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get value of current filter.
     *
     * @return array|string|null
     */
    public function getValue(): array|string|null
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Build conditions of filter.
     *
     * @param  mixed  ...$params
     * @return string|array
     */
    protected function buildCondition(...$params): string|array
    {
        if ($this->ignore) {
            return '';
        }

        $column = explode('.', $this->column);

        if (count($column) == 1) {
            return [$this->query => &$params];
        }

        return $this->buildRelationQuery(...$params);
    }

    /**
     * @param  callable|string  $relColumn
     * @param mixed           ...$params
     * @return array
     */
    protected function buildRelationQuery(callable|string $relColumn, ...$params): array
    {
        $column = explode('.', $this->column);

        $col = array_pop($column);

        $relColumn = is_callable($relColumn) ? $relColumn : $col;

        // 增加对whereHasIn的支持
        $method = class_exists(WhereHasInServiceProvider::class) ? 'whereHasIn' : 'whereHas';

        return [$method => [implode('.', $column), function ($q) use ($relColumn, $params) {
            $relColumn = is_string($relColumn) ? $q->getModel()->getTable().'.'.$relColumn : $relColumn;
            array_unshift($params, $relColumn);

            call_user_func_array([$q, $this->query], $params);
        }]];
    }

    /**
     * Variables for filter view.
     *
     * @return array
     */
    protected function defaultVariables(): array
    {
        return array_merge([
            'id'    => $this->id,
            'name'  => $this->formatName($this->column),
            'label' => $this->label,
            'value' => $this->normalizeValue(),
            'width' => $this->width,
            'style' => $this->style,
        ], $this->presenter()->variables());
    }

    protected function normalizeValue()
    {
        if ($this->value === '' || $this->value === null) {
            $this->value = Arr::get($this->parent->inputs(), $this->column);
        }

        return $this->value === '' || $this->value === null ? $this->defaultValue : $this->value;
    }

    /**
     * Render this filter.
     *
     * @return string
     * @throws \Throwable
     */
    public function render(): string
    {
        $variables = $this->variables();

        $variables['presenter'] = $this->renderPresenter();

        return Admin::view($this->view, $variables);
    }

    /**
     * @return \Closure
     *
     * @throws \Throwable
     */
    protected function renderPresenter(): Closure
    {
        return function () {
            return Admin::view($this->presenter->view(), $this->variables());
        };
    }

    /**
     * Render this filter.
     *
     * @return string
     * @throws \Throwable
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($method, $params)
    {
        if (method_exists($this->presenter(), $method)) {
            return $this->presenter()->{$method}(...$params);
        }

        throw new RuntimeException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
