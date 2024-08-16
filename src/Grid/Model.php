<?php

namespace Dcat\Admin\Grid;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\Repository;
use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Grid;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;
use Illuminate\Database\Eloquent\Model as EloquentModel;
/**
 * @mixin Builder
 */
class Model
{
    use Grid\Concerns\HasTree;

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Repository|null
     */
    protected ?Repository $repository = null;

    /**
     * @var AbstractPaginator|null
     */
    protected ?AbstractPaginator $paginator = null;

    /**
     * Array of queries of the model.
     *
     * @var \Illuminate\Support\Collection
     */
    protected Collection $queries;

    /**
     * Sort parameters of the model.
     *
     * @var array|null
     */
    protected ?array $sort = null;

    /**
     * @var Collection|null
     */
    protected ?Collection $data = null;

    /**
     * @var callable
     */
    protected $builder;

    /*
     * 20 items per page as default.
     *
     * @var int
     */
    protected int $perPage = 20;

    /**
     * @var string
     */
    protected string $pageName = 'page';

    /**
     * @var int
     */
    protected int $currentPage = 1;

    /**
     * If the model use pagination.
     *
     * @var bool
     */
    protected bool $usePaginate = true;

    /**
     * The query string variable used to store the per-page.
     *
     * @var string
     */
    protected string $perPageName = 'per_page';

    /**
     * The query string variable used to store the sort.
     *
     * @var string
     */
    protected string $sortName = '_sort';

    /**
     * @var bool
     */
    protected bool $simple = false;

    /**
     * @var Grid
     */
    protected Grid $grid;

    /**
     * @var Relation
     */
    protected Relation $relation;

    /**
     * @var array
     */
    protected array $eagerLoads = [];

    /**
     * @var array
     */
    protected array $constraints = [];

    /**
     * Create a new grid model instance.
     *
     * @param  Request  $request
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|\Dcat\Admin\Contracts\Repository|string|null  $repository
     * @throws \Dcat\Admin\Exception\InvalidArgumentException
     */
    public function __construct(Request $request, EloquentModel|Builder|Repository|string|null $repository = null)
    {
        if ($repository) {
            $this->repository = Admin::repository($repository);
        }

        $this->request = $request;
        $this->initQueries();
    }

    /**
     * @return void
     */
    protected function initQueries(): void
    {
        $this->queries = new Collection();
    }

    /**
     * @return \Dcat\Admin\Contracts\Repository|null
     */
    public function repository(): ?Repository
    {
        return $this->repository;
    }

    /**
     * @return Collection
     */
    public function getQueries(): Collection
    {
        return $this->queries = $this->queries->unique();
    }

    /**
     * @param  Collection  $query
     * @return void
     */
    public function setQueries(Collection $query): void
    {
        $this->queries = $query;
    }

    /**
     * @return AbstractPaginator|LengthAwarePaginator
     * @throws \Exception
     */
    public function paginator(): ?AbstractPaginator
    {
        $this->buildData();

        return $this->paginator;
    }

    /**
     * 是否使用 simplePaginate方法进行分页.
     *
     * @param  bool  $value
     * @return $this
     */
    public function simple(bool $value = true): static
    {
        $this->simple = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaginateMethod(): string
    {
        return $this->simple ? 'simplePaginate' : 'paginate';
    }

    /**
     * @param  int  $total
     * @param  array|Collection  $data
     * @param  string|null  $url
     * @return LengthAwarePaginator|Paginator
     */
    public function makePaginator(
        int $total,
        array|Collection $data,
        string $url = null
    ): Paginator|LengthAwarePaginator {
        if ($this->simple) {
            $paginator = new Paginator($data, $this->getPerPage(), $this->getCurrentPage());
        } else {
            $paginator = new LengthAwarePaginator(
                $data,
                $total,
                $this->getPerPage(), // 传入每页显示行数
                $this->getCurrentPage() // 传入当前页码
            );
        }

        return $paginator->setPath(
            $url ?: url()->current()
        );
    }

    /**
     * Get primary key name of model.
     *
     * @return string|array
     */
    public function getKeyName(): array|string
    {
        return $this->grid->getKeyName();
    }

    /**
     * Enable or disable pagination.
     *
     * @param  bool  $use
     *
     * @return \Dcat\Admin\Grid\Model
     * @reutrn $this;
     */
    public function usePaginate(bool $use = true): static
    {
        $this->usePaginate = $use;

        return $this;
    }

    /**
     * @return bool
     */
    public function allowPagination(): bool
    {
        return $this->usePaginate;
    }

    /**
     * Get the query string variable used to store the per-page.
     *
     * @return string
     */
    public function getPerPageName(): string
    {
        return $this->grid->makeName($this->perPageName);
    }

    /**
     * @param  int  $perPage
     * @return \Dcat\Admin\Grid\Model
     */
    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageName(): string
    {
        return $this->grid->makeName($this->pageName);
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setPageName(string $name): static
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Get the query string variable used to store the sort.
     *
     * @return string
     */
    public function getSortName(): string
    {
        return $this->grid->makeName($this->sortName);
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setSortName(string $name): static
    {
        $this->sortName = $name;

        return $this;
    }

    /**
     * Set parent grid instance.
     *
     * @param  Grid  $grid
     * @return $this
     */
    public function setGrid(Grid $grid): static
    {
        $this->grid = $grid;

        return $this;
    }

    /**
     * Get parent gird instance.
     *
     * @return Grid
     */
    public function grid(): Grid
    {
        return $this->grid;
    }

    /**
     * Get filter of Grid.
     *
     * @return Filter
     */
    public function filter(): Filter
    {
        return $this->grid->filter();
    }

    /**
     * Get constraints.
     *
     * @return array
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @param  array  $constraints
     * @return $this
     */
    public function setConstraints(array $constraints): static
    {
        $this->constraints = $constraints;

        return $this;
    }

    /**
     * Build.
     *
     * @return \Illuminate\Support\Collection|null
     * @throws \Exception
     */
    public function buildData(): ?Collection
    {
        if (is_null($this->data)) {
            $this->setData($this->fetch());
        }

        return $this->data;
    }

    /**
     * @param  callable|array|AbstractPaginator|Collection  $data
     * @return $this
     */
    public function setData(callable|array|Collection|AbstractPaginator $data): static
    {
        if (is_callable($data)) {
            $this->builder = $data;

            return $this;
        }

        if ($data instanceof AbstractPaginator) {
            $this->setPaginator($data);

            $data = $data->getCollection();
        } elseif ($data instanceof Collection) {
        } elseif ($data instanceof Arrayable || is_array($data)) {
            $data = collect($data);
        }

        if ($data instanceof Collection) {
            $this->data = $data;
        } else {
            $this->data = collect();
        }

        $this->stdObjToArray($this->data);

        return $this;
    }

    /**
     * Add conditions to grid model.
     *
     * @param  array  $conditions
     * @return $this
     */
    public function addConditions(array $conditions): static
    {
        foreach ($conditions as $condition) {
            call_user_func_array([$this, key($condition)], current($condition));
        }

        return $this;
    }

    /**
     * @return Collection|array
     *
     * @throws \Exception
     */
    protected function fetch(): array|Collection
    {
        if ($this->paginator) {
            return $this->paginator->getCollection();
        }

        if ($this->builder && is_callable($this->builder)) {
            $results = call_user_func($this->builder, $this);
        } else {
            $results = $this->repository->get($this);
        }

        if (is_array($results) || $results instanceof Collection) {
            return $results;
        }

        if ($results instanceof AbstractPaginator) {
            $this->setPaginator($results);

            return $results->getCollection();
        }

        throw new AdminException('Grid query error');
    }

    /**
     * @param  AbstractPaginator  $paginator
     * @return void
     */
    protected function setPaginator(AbstractPaginator $paginator): void
    {
        $this->paginator = $paginator;

        if ($this->simple) {
            if (method_exists($paginator, 'withQueryString')) {
                $paginator->withQueryString();
            } else {
                $paginator->appends(request()->all());
            }
        }

        $paginator->setPageName($this->getPageName());
    }

    /**
     * @param  Collection  $collection
     * @return Collection
     */
    protected function stdObjToArray(Collection $collection): Collection
    {
        return $collection->transform(function ($item) {
            if ($item instanceof stdClass) {
                return (array) $item;
            }

            return $item;
        });
    }

    /**
     * Get current page.
     *
     * @return int|null
     */
    public function getCurrentPage(): ?int
    {
        if (! $this->usePaginate) {
            return null;
        }

        return $this->currentPage ?: ($this->currentPage = ($this->request->get($this->getPageName()) ?: 1));
    }

    /**
     * @param  int  $currentPage
     * @return \Dcat\Admin\Grid\Model
     */
    public function setCurrentPage(int $currentPage): static
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * Get items number of per page.
     *
     * @return int|null
     */
    public function getPerPage(): ?int
    {
        if (! $this->usePaginate) {
            return null;
        }

        $perPage = $this->request->get($this->getPerPageName()) ?: $this->perPage;
        if ($perPage) {
            return (int) $perPage;
        }

        return null;
    }

    /**
     * Find query by method name.
     *
     * @param $method
     * @return Collection
     */
    public function findQueryByMethod($method): Collection
    {
        return $this->queries->where('method', $method);
    }

    /**
     * @param  callable|string  $method
     * @return $this
     */
    public function filterQueryBy(callable|string $method): static
    {
        $this->queries = $this->queries->filter(function ($query, $k) use ($method) {
            if (
                (is_string($method) && $query['method'] === $method)
                || (is_array($method) && in_array($query['method'], $method, true))
            ) {
                return false;
            }

            if (is_callable($method)) {
                return call_user_func($method, $query, $k);
            }

            return true;
        });

        return $this;
    }

    /**
     * Get the grid sort.
     *
     * @return array exp: ['name', 'desc']
     */
    public function getSort(): array
    {
        if (empty($this->sort)) {
            $this->sort = $this->request->get($this->getSortName());
        }

        if (empty($this->sort['column']) || empty($this->sort['type'])) {
            return [null, null, null];
        }

        return [$this->sort['column'], $this->sort['type'], $this->sort['cast'] ?? null];
    }

    /**
     * @param  array|string  $method
     * @return void
     */
    public function rejectQuery(array|string $method): void
    {
        $this->queries = $this->queries->reject(function ($query) use ($method) {
            if (is_callable($method)) {
                return call_user_func($method, $query);
            }

            return in_array($query['method'], (array) $method, true);
        });
    }

    /**
     * Reset orderBy query.
     *
     * @return void
     */
    public function resetOrderBy(): void
    {
        $this->rejectQuery(['orderBy', 'orderByDesc']);
    }

    /**
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    public function __call(string $method, array $arguments)
    {
        return $this->addQuery($method, $arguments);
    }

    /**
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     */
    public function addQuery(string $method, array $arguments = []): static
    {
        $this->queries->push([
            'method'    => $method,
            'arguments' => $arguments,
        ]);

        return $this;
    }

    public function getSortQueries()
    {
        return $this->findQueryByMethod('orderBy')
            ->merge($this->findQueryByMethod('orderByDesc'))
            ->merge($this->findQueryByMethod('latest'))
            ->merge($this->findQueryByMethod('oldest'));
    }

    public function getSortDescMethods(): array
    {
        return ['orderByDesc', 'latest'];
    }

    /**
     * @param  Builder  $query
     * @param  bool  $fetch
     * @param  string[]|null  $columns
     * @return \Illuminate\Database\Eloquent\Builder|mixed
     */
    public function apply(Builder $query, bool $fetch = false, array $columns = null): mixed
    {
        $this->getQueries()->unique()->each(function ($value) use (&$query, $fetch, $columns) {
            if (! $fetch && in_array($value['method'], ['paginate', 'simplePaginate', 'get'], true)) {
                return;
            }

            if ($columns) {
                if (in_array($value['method'], ['paginate', 'simplePaginate'], true)) {
                    $value['arguments'][1] = $columns;
                } elseif ($value['method'] === 'get') {
                    $value['arguments'] = [$columns];
                }
            }

            $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
        });

        return $query;
    }

    /**
     * Set the relationships that should be eager loaded.
     *
     * @param  mixed  $relations
     * @return $this|Model
     */
    public function with(mixed $relations): Model|static
    {
        if (is_array($relations)) {
            if (Arr::isAssoc($relations)) {
                $relations = array_keys($relations);
            }

            $this->eagerLoads = array_merge($this->eagerLoads, $relations);
        }

        if (is_string($relations)) {
            if (Str::contains($relations, '.')) {
                $relations = explode('.', $relations)[0];
            }

            if (Str::contains($relations, ':')) {
                $relations = explode(':', $relations)[0];
            }

            if (in_array($relations, $this->eagerLoads)) {
                return $this;
            }

            $this->eagerLoads[] = $relations;
        }

        return $this->addQuery('with', (array) $relations);
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->data = null;
        $this->initQueries();
    }
}
