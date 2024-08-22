<?php

namespace Dcat\Admin;

use Closure;
use Dcat\Admin\Contracts\TreeRepository;
use Dcat\Admin\Exception\InvalidArgumentException;
use Dcat\Admin\Repositories\EloquentRepository;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\HasBuilderEvents;
use Dcat\Admin\Traits\HasVariables;
use Dcat\Admin\Tree\AbstractTool;
use Dcat\Admin\Tree\Actions;
use Dcat\Admin\Tree\Tools;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Throwable;

/**
 * Class Tree.
 *
 * @see https://github.com/dbushell/Nestable
 */
class Tree implements Renderable
{
    use HasBuilderEvents;
    use HasVariables;
    use Macroable;

    const SAVE_ORDER_NAME = '_order';

    /**
     * @var array
     */
    protected array $items = [];

    /**
     * @var string
     */
    protected string $elementId = 'tree-';

    /**
     * @var ?TreeRepository
     */
    protected ?TreeRepository $repository = null;

    /**
     * @var Closure|null
     */
    protected ?Closure $queryCallback = null;

    /**
     * View of tree to render.
     *
     * @var string
     */
    protected string $view = 'admin::tree.container';

    /**
     * @var string
     */
    protected string $branchView = 'admin::tree.branch';

    /**
     * @var ?Closure
     */
    protected ?Closure $callback = null;

    /**
     * @var ?Closure
     */
    protected ?Closure $branchCallback = null;

    /**
     * @var string
     */
    public string $path = '';

    /**
     * @var string
     */
    public string $url = '';

    /**
     * @var bool
     */
    public bool $useCreate = true;

    /**
     * @var bool
     */
    public bool $expand = true;

    /**
     * @var bool
     */
    public bool $useQuickCreate = true;

    /**
     * @var array
     */
    public array $dialogFormDimensions = ['700px', '670px'];

    /**
     * @var bool
     */
    public bool $useSave = true;

    /**
     * @var bool
     */
    public bool $useRefresh = true;

    /**
     * @var array
     */
    protected array $nestableOptions = [];

    /**
     * Header tools.
     *
     * @var ?Tools
     */
    public ?Tools $tools = null;

    /**
     * @var string
     */
    protected string $actionsClass = '';

    /**
     * @var Closure[]
     */
    protected array $actionCallbacks = [];

    /**
     * @var ?Closure
     */
    protected ?Closure $wrapper = null;

    /**
     * Menu constructor.
     *
     * @param  null  $repository
     * @param  Closure|null  $callback
     * @throws InvalidArgumentException
     */
    public function __construct($repository = null, ?Closure $callback = null)
    {
        $this->repository = $this->makeRepository($repository);
        $this->path = $this->path ?: request()->getPathInfo();
        $this->url = url($this->path);

        $this->elementId .= Str::random(8);

        $this->setUpTools();

        if ($callback instanceof Closure) {
            call_user_func($callback, $this);
        }

        $this->callResolving();
    }

    /**
     * Setup tree tools.
     */
    public function setUpTools(): void
    {
        $this->tools = new Tools($this);
    }

    /**
     * @param $repository
     * @return TreeRepository
     * @throws InvalidArgumentException
     */
    public function makeRepository($repository): TreeRepository
    {
        if (is_string($repository)) {
            $repository = new $repository();
        }

        if ($repository instanceof Model || $repository instanceof Builder) {
            $repository = EloquentRepository::make($repository);
        }

        if (!$repository instanceof TreeRepository) {
            $class = get_class($repository);

            throw new InvalidArgumentException("The class [$class] must be a type of [".TreeRepository::class.'].');
        }

        return $repository;
    }

    /**
     * Initialize branch callback.
     *
     * @return void
     */
    protected function setDefaultBranchCallback(): void
    {
        if (is_null($this->branchCallback)) {
            $this->branchCallback = function ($branch) {
                $key = $branch[$this->repository->getPrimaryKeyColumn()];
                $title = $branch[$this->repository->getTitleColumn()];

                return "$key - $title";
            };
        }
    }

    /**
     * Set branch callback.
     *
     * @param  Closure  $branchCallback
     * @return $this
     */
    public function branch(Closure $branchCallback): static
    {
        $this->branchCallback = $branchCallback;

        return $this;
    }

    /**
     * Set query callback this tree.
     *
     * @return $this
     */
    public function query(Closure $callback): static
    {
        $this->queryCallback = $callback;

        return $this;
    }

    /**
     * number of levels an item can be nested (default 5).
     *
     * @see https://github.com/dbushell/Nestable
     *
     * @param  int  $max
     * @return $this
     */
    public function maxDepth(int $max): static
    {
        return $this->nestable(['maxDepth' => $max]);
    }

    /**
     * Set nestable options.
     *
     * @param  array  $options
     * @return $this
     */
    public function nestable(array $options = []): static
    {
        $this->nestableOptions = array_merge($this->nestableOptions, $options);

        return $this;
    }

    /**
     * @param  bool  $value
     * @return void
     */
    public function expand(bool $value = true): void
    {
        $this->expand = $value;
    }

    /**
     * Disable create.
     *
     * @param  bool  $value
     * @return void
     */
    public function disableCreateButton(bool $value = true): void
    {
        $this->useCreate = !$value;
    }

    public function showCreateButton(bool $value = true): void
    {
        $this->disableCreateButton(!$value);
    }

    public function disableQuickCreateButton(bool $value = true): void
    {
        $this->useQuickCreate = !$value;
    }

    public function showQuickCreateButton(bool $value = true): void
    {
        $this->disableQuickCreateButton(!$value);
    }

    /**
     * @param  string  $width
     * @param  string  $height
     * @return $this
     */
    public function setDialogFormDimensions(string $width, string $height): static
    {
        $this->dialogFormDimensions = [$width, $height];

        return $this;
    }

    /**
     * Disable save.
     *
     * @param  bool  $value
     * @return void
     */
    public function disableSaveButton(bool $value = true): void
    {
        $this->useSave = !$value;
    }

    public function showSaveButton(bool $value = true): void
    {
        $this->disableSaveButton(!$value);
    }

    /**
     * Disable refresh.
     *
     * @param  bool  $value
     * @return void
     */
    public function disableRefreshButton(bool $value = true): void
    {
        $this->useRefresh = !$value;
    }

    public function showRefreshButton(bool $value = true): void
    {
        $this->disableRefreshButton(!$value);
    }

    public function disableQuickEditButton(bool $value = true): void
    {
        $this->actions(function (Actions $actions) use ($value) {
            $actions->disableQuickEdit($value);
        });
    }

    public function showQuickEditButton(bool $value = true): void
    {
        $this->disableQuickEditButton(!$value);
    }

    public function disableEditButton(bool $value = true): void
    {
        $this->actions(function (Actions $actions) use ($value) {
            $actions->disableEdit($value);
        });
    }

    public function showEditButton(bool $value = true): void
    {
        $this->disableEditButton(!$value);
    }

    public function disableDeleteButton(bool $value = true): void
    {
        $this->actions(function (Actions $actions) use ($value) {
            $actions->disableDelete($value);
        });
    }

    public function showDeleteButton(bool $value = true): void
    {
        $this->disableDeleteButton(!$value);
    }

    /**
     * @param  Closure  $closure
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
        return (bool) $this->wrapper;
    }

    /**
     * Save tree order from a input.
     *
     * @param  string  $serialize
     * @return bool
     * @throws InvalidArgumentException
     */
    public function saveOrder(string $serialize): bool
    {
        $tree = json_decode($serialize, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        $this->repository->saveOrder($tree);

        return true;
    }

    /**
     * Set view of tree.
     *
     * @param  string  $view
     * @return $this
     */
    public function view(string $view): static
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @param  string  $view
     * @return $this
     */
    public function branchView(string $view): static
    {
        $this->branchView = $view;

        return $this;
    }

    /**
     * @return Closure
     */
    public function resolveAction(): Closure
    {
        return function ($branch) {
            $class = $this->actionsClass ?: Actions::class;

            $action = new $class();

            $action->setParent($this);
            $action->setRow($branch);

            $this->callActionCallbacks($action);

            return $action->render();
        };
    }

    protected function callActionCallbacks(Actions $actions): void
    {
        foreach ($this->actionCallbacks as $callback) {
            $callback->call($actions->row, $actions);
        }
    }

    /**
     * 自定义行操作类.
     *
     * @param  string  $actionClass
     * @return $this
     */
    public function setActionClass(string $actionClass): static
    {
        $this->actionsClass = $actionClass;

        return $this;
    }

    /**
     * 设置行操作回调.
     *
     * @param  array|Closure  $callback
     * @return $this
     */
    public function actions(array|Closure $callback): static
    {
        if ($callback instanceof Closure) {
            $this->actionCallbacks[] = $callback;
        } else {
            $this->actionCallbacks[] = function (Actions $actions) use ($callback) {
                if (!is_array($callback)) {
                    $callback = [$callback];
                }

                foreach ($callback as $value) {
                    $actions->append(clone $value);
                }
            };
        }

        return $this;
    }

    /**
     * Return all items of the tree.
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->repository->withQuery($this->queryCallback)->toTree();
    }

    /**
     * Variables in tree template.
     *
     * @return array
     */
    public function defaultVariables(): array
    {
        return [
            'id' => $this->elementId,
            'tools' => $this->tools->render(),
            'items' => $this->getItems(),
            'useCreate' => $this->useCreate,
            'useQuickCreate' => $this->useQuickCreate,
            'useSave' => $this->useSave,
            'useRefresh' => $this->useRefresh,
            'createButton' => $this->renderCreateButton(),
            'nestableOptions' => $this->nestableOptions,
            'url' => $this->url,
            'resolveAction' => $this->resolveAction(),
            'expand' => $this->expand,
        ];
    }

    /**
     * @return mixed
     */
    public function getKeyName(): mixed
    {
        return $this->repository->getKeyName();
    }

    /**
     * @return string
     */
    public function resource(): string
    {
        return $this->url;
    }

    /**
     * Set resource path.
     *
     * @param  string  $path
     * @return $this
     */
    public function setResource(string $path): static
    {
        $this->url = admin_url($path);

        return $this;
    }

    /**
     * Setup tools.
     *
     * @param  array|string|Closure|AbstractTool|Htmlable|Renderable|null  $callback
     * @return $this|Tools
     */
    public function tools(AbstractTool|Renderable|Htmlable|array|string|Closure $callback = null): Tools|static
    {
        if ($callback === null) {
            return $this->tools;
        }

        if ($callback instanceof Closure) {
            call_user_func($callback, $this->tools);

            return $this;
        }

        if (!is_array($callback)) {
            $callback = [$callback];
        }

        foreach ($callback as $tool) {
            $this->tools->add($tool);
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function renderCreateButton(): string
    {
        if (!$this->useQuickCreate && !$this->useCreate) {
            return '';
        }

        $url = $this->url.'/create';
        $new = trans('admin.new');

        $quickBtn = $btn = '';
        if ($this->useCreate) {
            $btn = "<a href='$url' class='btn btn-sm btn-primary'><i class='feather icon-plus'></i><span class='d-none d-sm-inline'>&nbsp;$new</span></a>";
        }

        if ($this->useQuickCreate) {
            $text = $this->useCreate ? '<i class=\' fa fa-clone\'></i>' : "<i class='feather icon-plus'></i><span class='d-none d-sm-inline'>&nbsp; $new</span>";
            $quickBtn = "<button data-url='$url' class='btn btn-sm btn-primary tree-quick-create'>$text</button>";
        }

        return "&nbsp;<div class='btn-group pull-right' style='margin-right:3px'>$btn$quickBtn</div>";
    }

    /**
     * @return void
     */
    protected function renderQuickCreateButton(): void
    {
        if ($this->useQuickCreate) {
            [$width, $height] = $this->dialogFormDimensions;

            Form::dialog(trans('admin.new'))
                ->click('.tree-quick-create')
                ->success('Dcat.reload()')
                ->dimensions($width, $height);
        }
    }

    /**
     * Render a tree.
     *
     * @return string
     * @throws Throwable
     */
    public function render(): string
    {
        $this->callResolving();

        $this->setDefaultBranchCallback();

        $this->renderQuickCreateButton();

        view()->share([
            'currentUrl' => $this->url,
            'keyName' => $this->getKeyName(),
            'branchView' => $this->branchView,
            'branchCallback' => $this->branchCallback,
        ]);

        return $this->doWrap();
    }

    /**
     * @return string
     * @throws Throwable
     */
    protected function doWrap(): string
    {
        $view = view($this->view, $this->variables());

        if (!$wrapper = $this->wrapper) {
            $html = Admin::resolveHtml($view->render())['html'];

            return "<div class='card'>$html</div>";
        }

        return Admin::resolveHtml(Helper::render($wrapper($view)))['html'];
    }

    /**
     * Get the string contents of the grid view.
     *
     * @return string
     * @throws Throwable
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Create a tree instance.
     *
     * @param  mixed  ...$param
     * @return $this
     * @throws InvalidArgumentException
     */
    public static function make(...$param): static
    {
        return new static(...$param);
    }
}
