<?php

namespace Dcat\Admin;

use Closure;
use Dcat\Admin\Grid\Column;
use Dcat\Admin\Grid\Concerns;
use Dcat\Admin\Grid\Model;
use Dcat\Admin\Grid\Row;
use Dcat\Admin\Grid\Tools;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasBuilderEvents;
use Dcat\Admin\Traits\HasVariables;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

class Grid
{
    use HasBuilderEvents;
    use HasVariables;
    use Concerns\HasEvents;
    use Concerns\HasNames;
    use Concerns\HasFilter;
    use Concerns\HasTools;
    use Concerns\HasActions;
    use Concerns\HasPaginator;
    use Concerns\HasExporter;
    use Concerns\HasComplexHeaders;
    use Concerns\HasSelector;
    use Concerns\HasQuickCreate;
    use Concerns\HasQuickSearch;
    use Concerns\CanFixColumns;
    use Concerns\CanHidesColumns;
    use Macroable {
        Macroable::__call as macroCall;
    }

    const CREATE_MODE_DEFAULT = 'default';
    const CREATE_MODE_DIALOG  = 'dialog';
    const ASYNC_NAME          = '_async_';

    /**
     * The grid data model instance.
     *
     * @var \Dcat\Admin\Grid\Model
     */
    protected $model;

    /**
     * Collection of grid columns.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $columns;

    /**
     * Collection of all grid columns.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $allColumns;

    /**
     * Collection of all data rows.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $rows;

    /**
     * @var array
     */
    protected $rowsCallbacks = [];

    /**
     * All column names of the grid.
     *
     * @var array
     */
    protected array $columnNames = [];

    /**
     * Grid builder.
     *
     * @var \Closure
     */
    protected $builder;

    /**
     * Mark if the grid is built.
     *
     * @var bool
     */
    protected $built = false;

    /**
     * Resource path of the grid.
     *
     * @var
     */
    protected $resourcePath;

    /**
     * Default primary key name.
     *
     * @var string|array
     */
    protected string|array $keyName;

    /**
     * View for grid to render.
     *
     * @var string
     */
    protected $view = 'admin::grid.table';

    /**
     * @var Closure[]
     */
    protected $header = [];

    /**
     * @var Closure[]
     */
    protected $footer = [];

    /**
     * @var Closure
     */
    protected $wrapper;

    /**
     * @var bool
     */
    protected $addNumberColumn = false;

    /**
     * @var string
     */
    protected $tableId = 'grid-table';

    /**
     * @var Grid\Tools\RowSelector
     */
    protected $rowSelector;

    /**
     * Options for grid.
     *
     * @var array
     */
    protected $options = [
        'pagination'          => true,
        'filter'              => true,
        'actions'             => true,
        'quick_edit_button'   => false,
        'edit_button'         => true,
        'view_button'         => true,
        'delete_button'       => true,
        'row_selector'        => true,
        'create_button'       => true,
        'bordered'            => false,
        'table_collapse'      => true,
        'toolbar'             => true,
        'create_mode'         => self::CREATE_MODE_DEFAULT,
        'dialog_form_area'    => ['700px', '670px'],
        'table_class'         => ['table', 'custom-data-table', 'data-table'],
        'scrollbar_x'         => false,
        'actions_class'       => null,
        'batch_actions_class' => null,
        'paginator_class'     => null,
    ];

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var bool
     */
    protected $show = true;

    /**
     * @var bool
     */
    protected $async = false;

    /**
     * Create a new grid instance.
     *
     * Grid constructor.
     *
     * @param  null  $repository
     * @param  null|\Closure  $builder
     * @param  null  $request
     * @throws \Dcat\Admin\Exception\InvalidArgumentException
     */
    public function __construct($repository = null, ?Closure $builder = null, $request = null)
    {
        $this->model        = new Model(request(), $repository);
        $this->columns      = new Collection();
        $this->allColumns   = new Collection();
        $this->rows         = new Collection();
        $this->builder      = $builder;
        $this->request      = $request ?: request();
        $this->resourcePath = url($this->request->getPathInfo());

        if ($repository = $this->model->repository()) {
            $this->setKeyName($repository->getKeyName());
        }

        $this->model->setGrid($this);

        $this->setUpTools();
        $this->setUpFilter();

        $this->callResolving();
    }

    /**
     * Get table ID.
     *
     * @return string
     */
    public function getTableId(): string
    {
        return $this->tableId;
    }

    /**
     * Set primary key name.
     *
     * @param string|array $name
     * @return $this
     */
    public function setKeyName($name): static
    {
        $this->keyName = $name;

        return $this;
    }

    /**
     * Get or set primary key name.
     *
     * @return string|array
     */
    public function getKeyName(): array|string
    {
        return $this->keyName ?: 'id';
    }

    /**
     * Add column to Grid.
     *
     * @param string $name
     * @param string $label
     * @return Column
     */
    public function column($name, $label = ''): Column
    {
        return $this->addColumn($name, $label);
    }

    /**
     * Add number column.
     *
     * @param null|string $label
     * @return Column
     */
    public function number(?string $label = null): Column
    {
        return $this->addColumn('#', $label ?: '#');
    }

    /**
     * 启用异步渲染功能.
     *
     * @param bool $async
     * @return $this
     */
    public function async(bool $async = true): static
    {
        $this->async = $async;

        if ($async) {
            $this->view('admin::grid.async-table');
        }

        return $this;
    }

    public function getAsync(): bool
    {
        return $this->async;
    }

    /**
     * 判断是否允许查询数据.
     *
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function buildable(): bool
    {
        return !$this->async || $this->isAsyncRequest();
    }

    /**
     * @return bool|null
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function isAsyncRequest(): ?bool
    {
        return $this->request->get(static::ASYNC_NAME);
    }

    /**
     * Batch add column to grid.
     *
     * @param array $columns
     * @return Collection|void
     * @example
     * 1.$grid->columns(['name' => 'Name', 'email' => 'Email' ...]);
     * 2.$grid->columns('name', 'email' ...)
     *
     */
    public function columns($columns = null)
    {
        if ($columns === null) {
            return $this->columns;
        }

        if (func_num_args() == 1 && is_array($columns)) {
            foreach ($columns as $column => $label) {
                $this->column($column, $label);
            }

            return;
        }

        foreach (func_get_args() as $column) {
            $this->column($column);
        }
    }

    /**
     * @return Collection
     */
    public function allColumns(): Collection
    {
        return $this->allColumns;
    }

    /**
     * 删除列.
     *
     * @param string|Column $column
     * @return $this
     */
    public function dropColumn($column): static
    {
        if ($column instanceof Column) {
            $column = $column->getName();
        }

        $this->columns->offsetUnset($column);
        $this->allColumns->offsetUnset($column);

        return $this;
    }

    /**
     * Add column to grid.
     *
     * @param string $field
     * @param string $label
     * @return Column
     */
    protected function addColumn(string $field = '', string $label = ''): Column
    {
        $column = $this->newColumn($field, $label);

        $this->columns->put($field, $column);
        $this->allColumns->put($field, $column);

        return $column;
    }

    /**
     * @param string $field
     * @param string $label
     * @return Column
     */
    public function prependColumn(string $field = '', string $label = ''): Column
    {
        $column = $this->newColumn($field, $label);

        $this->columns->prepend($column, $field);
        $this->allColumns->prepend($column, $field);

        return $column;
    }

    /**
     * @param string $field
     * @param string $label
     * @return Column
     */
    public function newColumn($field = '', $label = ''): Column
    {
        $column = new Column($field, $label);
        $column->setGrid($this);

        return $column;
    }

    /**
     * Get Grid model.
     *
     * @return Model
     */
    public function model(): Model
    {
        return $this->model;
    }

    /**
     * @return array
     */
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    /**
     * Apply column filter to grid query.
     */
    protected function applyColumnFilter(): void
    {
        $this->columns->each->bindFilterQuery($this->model());
    }

    /**
     * @param string|array $class
     * @return void
     */
    public function addTableClass($class): void
    {
        $this->options['table_class'] = array_merge((array)$this->options['table_class'], (array)$class);
    }

    public function formatTableClass(): string
    {
        if ($this->options['bordered']) {
            $this->addTableClass(['table-bordered', 'complex-headers', 'data-table']);
        }

        return implode(' ', array_unique((array)$this->options['table_class']));
    }

    /**
     * Build the grid.
     *
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     */
    public function build(): void
    {
        if (!$this->buildable()) {
            $this->callBuilder();
            $this->handleExportRequest();

            $this->prependRowSelectorColumn();
            $this->appendActionsColumn();

            $this->sortHeaders();

            return;
        }

        if ($this->built) {
            return;
        }

        $collection = clone $this->processFilter();

        $this->prependRowSelectorColumn();
        $this->appendActionsColumn();

        Column::setOriginalGridModels($collection);

        $this->columns->map(function (Column $column) use (&$collection) {
            $column->fill($collection);

            $this->columnNames[] = $column->getName();
        });

        $this->buildRows($collection);

        $this->sortHeaders();
    }

    /**
     * @return void
     */
    public function callBuilder(): void
    {
        if ($this->builder && !$this->built) {
            call_user_func($this->builder, $this);
        }

        $this->built = true;
    }

    /**
     * Build the grid rows.
     *
     * @param Collection $data
     * @return void
     */
    protected function buildRows($data): void
    {
        $this->rows = $data->map(function ($row) {
            return new Row($this, $row);
        });

        foreach ($this->rowsCallbacks as $callback) {
            $callback($this->rows);
        }
    }

    /**
     * Set grid row callback function.
     *
     * @return Collection|$this
     */
    public function rows(Closure $callback = null): Collection|static
    {
        if ($callback) {
            $this->rowsCallbacks[] = $callback;

            return $this;
        }

        return $this->rows;
    }

    /**
     * Get create url.
     *
     * @return string
     */
    public function getCreateUrl(): string
    {
        return $this->urlWithConstraints($this->resource().'/create');
    }

    /**
     * @param string $key
     * @return string
     */
    public function getEditUrl($key): string
    {
        return $this->urlWithConstraints("{$this->resource()}/$key/edit");
    }

    /**
     * @param string|null $url
     * @return string
     */
    public function urlWithConstraints(?string $url): string
    {
        $queryString = '';

        if ($constraints = $this->model()->getConstraints()) {
            $queryString = http_build_query($constraints);
        }

        return $url.($queryString ? ('?'.$queryString) : '');
    }

    /**
     * @return Grid\Tools\RowSelector
     */
    public function rowSelector(): Tools\RowSelector
    {
        return $this->rowSelector ?: ($this->rowSelector = new Grid\Tools\RowSelector($this));
    }

    /**
     * Prepend checkbox column for grid.
     *
     * @return void
     */
    protected function prependRowSelectorColumn(): void
    {
        if (!$this->options['row_selector']) {
            return;
        }

        $rowSelector = $this->rowSelector();
        $keyName     = $this->getKeyName();

        $this->prependColumn(
            Grid\Column::SELECT_COLUMN_NAME
        )->setLabel($rowSelector->renderHeader())->display(function () use ($rowSelector, $keyName) {
            return $rowSelector->renderColumn($this, $this->{$keyName});
        });
    }

    /**
     * @param string $width
     * @param string $height
     * @return void
     */
    public function setDialogFormDimensions(string $width, string $height): void
    {
        $this->options['dialog_form_area'] = [$width, $height];
    }

    /**
     * Render create button for grid.
     *
     * @return string
     */
    public function renderCreateButton(): string
    {
        if (!$this->options['create_button']) {
            return '';
        }

        return (new Tools\CreateButton($this))->render();
    }

    /**
     * @param bool $value
     * @return void
     */
    public function withBorder(bool $value = true): void
    {
        $this->options['bordered'] = $value;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function tableCollapse(bool $value = true): void
    {
        $this->options['table_collapse'] = $value;
    }

    /**
     * 显示横轴滚动条.
     *
     * @param bool $value
     * @return void
     */
    public function scrollbar(bool $value = true): void
    {
        $this->options['table_scrollbar'] = $value;
    }

    /**
     * Set grid header.
     *
     * @param Closure|Renderable|string $content
     * @return $this
     */
    public function header(Closure|Renderable|string $content): static
    {
        $this->header[] = $content;

        return $this;
    }

    /**
     * Render grid header.
     *
     * @return string
     * @throws \Exception
     */
    public function renderHeader(): string
    {
        if (!$this->header) {
            return '';
        }

        return <<<HTML
<div class="card-header clearfix" style="border-bottom: 0;background: transparent;padding: 0">{$this->renderHeaderOrFooter($this->header)}</div>
HTML;
    }

    /**
     * @throws \Exception
     */
    protected function renderHeaderOrFooter($callbacks): string
    {
        $target  = [$this->processFilter(), $this];
        $content = [];

        foreach ($callbacks as $callback) {
            $content[] = Helper::render($callback, $target);
        }

        if (empty($content)) {
            return '';
        }

        return implode('<div class="mb-1 clearfix"></div>', $content);
    }

    /**
     * Set grid footer.
     *
     * @param Closure|string|Renderable $content
     * @return $this
     */
    public function footer(Closure|Renderable|string $content): static
    {
        $this->footer[] = $content;

        return $this;
    }

    /**
     * Render grid footer.
     *
     * @return string
     * @throws \Exception
     */
    public function renderFooter(): string
    {
        if (!$this->footer) {
            return '';
        }

        return <<<HTML
<div class="box-footer clearfix">{$this->renderHeaderOrFooter($this->footer)}</div>
HTML;
    }

    /**
     * Get or set option for grid.
     *
     * @param string|array $key
     * @param mixed        $value
     * @return mixed|void
     */
    public function option($key, $value = null)
    {
        if (is_null($value)) {
            return $this->options[$key] ?? null;
        }

        if (is_array($key)) {
            $this->options = array_merge($this->options, $key);
        } else {
            $this->options[$key] = $value;
        }
    }

    protected function setUpOptions(): void
    {
        if ($this->options['bordered']) {
            $this->tableCollapse(false);
        }
    }

    /**
     * Disable row selector.
     *
     * @param bool $disable
     * @return void
     */
    public function disableRowSelector(bool $disable = true): void
    {
        $this->tools->disableBatchActions($disable);

        $this->option('row_selector', !$disable);
    }

    /**
     * Show row selector.
     *
     * @param bool $val
     * @return void
     */
    public function showRowSelector(bool $val = true): void
    {
        $this->disableRowSelector(!$val);
    }

    /**
     * Remove create button on grid.
     *
     * @param bool $disable
     * @return void
     */
    public function disableCreateButton(bool $disable = true): void
    {
        $this->option('create_button', !$disable);
    }

    /**
     * Show create button.
     *
     * @param bool $val
     * @return void
     */
    public function showCreateButton(bool $val = true): void
    {
        $this->disableCreateButton(!$val);
    }

    /**
     * If allow creation.
     *
     * @return bool
     */
    public function allowCreateButton(): bool
    {
        return $this->options['create_button'];
    }

    /**
     * @param string $mode
     * @return \Dcat\Admin\Grid|null
     */
    public function createMode(string $mode): null|static
    {
        return $this->option('create_mode', $mode);
    }

    /**
     * @return \Dcat\Admin\Grid|null
     */
    public function enableDialogCreate(): null|static
    {
        return $this->createMode(self::CREATE_MODE_DIALOG);
    }

    /**
     * Get or set resource path.
     *
     * @return string
     */
    public function resource(): string
    {
        return $this->resourcePath;
    }

    /**
     * Create a grid instance.
     *
     * @param  mixed  ...$params
     * @return $this
     * @throws \Dcat\Admin\Exception\InvalidArgumentException
     */
    public static function make(...$params): static
    {
        return new static(...$params);
    }

    /**
     * @param Closure $closure
     * @return $this;
     */
    public function wrap(Closure $closure): static
    {
        $this->wrapper = $closure;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasWrapper(): bool
    {
        return (bool)$this->wrapper;
    }

    /**
     * Add variables to grid view.
     *
     * @param array $variables
     * @return $this
     */
    public function with(array $variables): static
    {
        return $this->addVariables($variables);
    }

    /**
     * Get all variables will used in grid view.
     *
     * @return array
     */
    protected function defaultVariables(): array
    {
        return [
            'grid'    => $this,
            'tableId' => $this->getTableId(),
        ];
    }

    /**
     * Set a view to render.
     *
     * @param string $view
     * @return $this
     */
    public function view($view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Set grid title.
     *
     * @param string $title
     * @return $this
     */
    public function title($title): static
    {
        $this->variables['title'] = $title;

        return $this;
    }

    /**
     * Set grid description.
     *
     * @param string $description
     * @return $this
     */
    public function description($description): static
    {
        $this->variables['description'] = $description;

        return $this;
    }

    /**
     * Set resource path for grid.
     *
     * @param string $path
     * @return $this
     */
    public function setResource($path): static
    {
        $this->resourcePath = admin_url($path);

        return $this;
    }

    /**
     * 设置是否显示.
     *
     * @param bool $value
     * @return $this
     */
    public function show(bool $value = true): static
    {
        $this->show = $value;

        return $this;
    }

    /**
     * 是否显示横向滚动条.
     *
     * @param bool $value
     * @return $this
     */
    public function scrollbarX(bool $value = true): static
    {
        $this->options['scrollbar_x'] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function formatTableParentClass(): string
    {
        $tableCollaps = $this->option('table_collapse') ? 'table-collapse' : '';
        $scrollbarX   = $this->option('scrollbar_x') ? 'table-scrollbar-x' : '';

        return "table-responsive table-wrapper complex-container table-middle mt-1 $tableCollaps $scrollbarX";
    }

    /**
     * Get the string contents of the grid view.
     *
     * @return string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function render(): string
    {
        $this->callComposing();
        $this->build();
        $this->applyFixColumns();
        $this->setUpOptions();
        $this->addFilterScript();
        $this->addScript();

        return $this->doWrap();
    }

    public function getView(): string
    {
        if ($this->async && $this->hasFixColumns()) {
            return 'admin::grid.async-fixed-table';
        }

        return $this->view;
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function addScript(): void
    {
        if ($this->async && !$this->isAsyncRequest()) {
            $query = static::ASYNC_NAME;
            $url   = Helper::fullUrlWithoutQuery(['_pjax']);
            $url   = Helper::urlWithQuery($url, [static::ASYNC_NAME => 1]);

            $options = [
                'selector'  => ".async-{$this->getTableId()}",
                'queryName' => $query,
                'url'       => $url,
            ];

            if ($this->hasFixColumns()) {
                $options['loadingStyle'] = 'height:140px;';
            }

            $options = json_encode($options);

            Admin::script(
                <<<JS
Dcat.grid.async($options).render()
JS
            );
        }
    }

    /**
     * @return string
     * @throws \Throwable
     */
    protected function doWrap(): string
    {
        if (!$this->show) {
            return '';
        }

        $view = view($this->getView(), $this->variables());

        if (!$wrapper = $this->wrapper) {
            return $view->render();
        }

        return Helper::render($wrapper($view));
    }

    /**
     * Add column to grid.
     *
     * @param string $name
     * @return Column
     */
    public function __get($name)
    {
        return $this->addColumn($name);
    }

    /**
     * Dynamically add columns to the grid view.
     *
     * @param $method
     * @param $parameters
     * @return Column
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->addColumn($method, $parameters[0] ?? null);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function __toString()
    {
        return $this->render();
    }
}
