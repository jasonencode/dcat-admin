<?php

namespace Dcat\Admin\Repositories;

use Dcat\Admin\Contracts\TreeRepository;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Show;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueryBuilderRepository extends Repository implements TreeRepository
{
    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string
     */
    protected string $connection;

    /**
     * @var string
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * @var string
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * @var Builder
     */
    protected Builder $queryBuilder;

    /**
     * QueryBuilderRepository constructor.
     */
    public function __construct()
    {
        $this->initQueryBuilder();
    }

    /**
     * 初始化.
     */
    protected function initQueryBuilder(): void
    {
        $this->queryBuilder = $this->connection
            ? DB::connection($this->connection)->table($this->getTable())
            : DB::table($this->getTable());
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getCreatedAtColumn(): string
    {
        return $this->createdAtColumn;
    }

    /**
     * @return string
     */
    public function getUpdatedAtColumn(): string
    {
        return $this->updatedAtColumn;
    }

    /**
     * 获取列表页面查询的字段.
     *
     * @return array
     */
    public function getGridColumns(): array
    {
        return ['*'];
    }

    /**
     * 获取表单页面查询的字段.
     *
     * @return array
     */
    public function getFormColumns(): array
    {
        return ['*'];
    }

    /**
     * 获取详情页面查询的字段.
     *
     * @return array
     */
    public function getDetailColumns(): array
    {
        return ['*'];
    }

    /**
     * 查询Grid表格数据.
     *
     * @param  Grid\Model  $model
     * @return LengthAwarePaginator|Collection|array
     */
    public function get(Grid\Model $model): LengthAwarePaginator|array|Collection
    {
        $this->setSort($model);
        $this->setPaginate($model);

        $query = $this->newQuery();

        $model->getQueries()->unique()->each(function ($value) use (&$query) {
            if ($value['method'] == 'paginate') {
                $value['arguments'][1] = $this->getGridColumns();
            } elseif ($value['method'] == 'get') {
                $value['arguments'] = [$this->getGridColumns()];
            }

            $query = call_user_func_array([$query, $value['method']], $value['arguments'] ?? []);
        });

        return $query;
    }

    /**
     * 设置表格数据排序.
     *
     * @param  Grid\Model  $model
     * @return void
     */
    protected function setSort(Grid\Model $model): void
    {
        [$column, $type] = $model->getSort();

        if (empty($column) || empty($type)) {
            return;
        }

        if (Str::contains($column, '.')) {
            $this->setRelationSort($model, $column, $type);
        } else {
            $model->resetOrderBy();

            $model->addQuery('orderBy', [$column, $type]);
        }
    }

    /**
     * 设置关联数据排序.
     *
     * @param  Grid\Model  $model
     * @param  string  $column
     * @param  string  $type
     * @return void
     */
    protected function setRelationSort(Grid\Model $model, string $column, string $type): void
    {
        [$relationName, $relationColumn] = explode('.', $column);

        if ($model->getQueries()->contains(function ($query) use ($relationName) {
            return $query['method'] == 'with' && in_array($relationName, $query['arguments']);
        })) {
            $model->addQuery('select', [$this->getGridColumns()]);

            $model->resetOrderBy();

            $model->addQuery('orderBy', [
                $relationColumn,
                $type,
            ]);
        }
    }

    /**
     * 设置分页参数.
     *
     * @param  Grid\Model  $model
     * @return void
     */
    protected function setPaginate(Grid\Model $model): void
    {
        $paginate = $model->findQueryByMethod('paginate')->first();

        $model->rejectQuery(['paginate']);

        if (!$model->allowPagination()) {
            $model->addQuery('get', [$this->getGridColumns()]);
        } else {
            $model->addQuery('paginate', $this->resolvePerPage($model, $paginate));
        }
    }

    /**
     * 获取分页参数.
     *
     * @param  Grid\Model  $model
     * @param  array|null  $paginate
     * @return array
     */
    protected function resolvePerPage(Grid\Model $model, ?array $paginate): array
    {
        if ($paginate && is_array($paginate)) {
            if ($perPage = request()->input($model->getPerPageName())) {
                $paginate['arguments'][0] = (int) $perPage;
            }

            return $paginate['arguments'];
        }

        return [
            $model->getPerPage(),
            $this->getGridColumns(),
            $model->getPageName(),
            $model->getCurrentPage(),
        ];
    }

    /**
     * 查询编辑页面数据.
     *
     * @param  Form  $form
     * @return array
     */
    public function edit(Form $form): array
    {
        $result = $this->newQuery()
            ->where($this->getKeyName(), $form->getKey())
            ->first($this->getFormColumns());

        if (!$result) {
            abort(404);
        }

        return (array) $result;
    }

    /**
     * 查询详情页面数据.
     *
     * @param  Show  $show
     * @return array
     */
    public function detail(Show $show): array
    {
        $result = $this->newQuery()
            ->where($this->getKeyName(), $show->getKey())
            ->first($this->getDetailColumns());

        if (!$result) {
            abort(404);
        }

        return (array) $result;
    }

    /**
     * 新增记录.
     *
     * @param  Form  $form
     * @return bool|int|JsonResponse
     */
    public function store(Form $form): bool|int|JsonResponse
    {
        $result = null;

        DB::transaction(function () use ($form, &$result) {
            $result = $this->newQuery()
                ->insertGetId($form->updates());
        });

        return $result;
    }

    /**
     * 查询更新前的行数据.
     *
     * @param  Form  $form
     * @return array
     */
    public function updating(Form $form): array
    {
        return $this->edit($form);
    }

    /**
     * 更新数据.
     *
     * @param  Form  $form
     * @return bool|JsonResponse
     */
    public function update(Form $form): bool|JsonResponse
    {
        $result = null;

        DB::transaction(function () use ($form, &$result) {
            $result = $this->newQuery()
                ->where($this->getKeyName(), $form->getKey())
                ->limit(1)
                ->update($form->updates());
        });

        return $result;
    }

    /**
     * 数据行排序上移一个单位.
     *
     * @return bool
     * @throws RuntimeException
     */
    public function moveOrderUp(): bool
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 数据行排序下移一个单位.
     *
     * @return bool
     * @throws RuntimeException
     */
    public function moveOrderDown(): bool
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 删除数据.
     *
     * @param  Form  $form
     * @param  array  $deletingData
     * @return bool|int|JsonResponse
     */
    public function delete(Form $form, array $deletingData): bool|int|JsonResponse
    {
        $id = $form->getKey();

        $deletingData = collect($deletingData)->keyBy($this->getKeyName());

        collect(explode(',', $id))->filter()->each(function ($id) use ($form, $deletingData) {
            $data = $deletingData->get($id, []);

            if (!$data) {
                return;
            }

            $form->deleteFiles($data);

            $this->newQuery()
                ->where($this->getKeyName(), $id)
                ->limit(1)
                ->delete();
        });

        return true;
    }

    /**
     * 查询删除前的行数据.
     *
     * @param  Form  $form
     * @return array
     */
    public function deleting(Form $form): array
    {
        $query = $this->newQuery();

        $id = $form->getKey();

        return $query
            ->whereIn(
                $this->getKeyName(),
                collect(explode(',', $id))->filter()->toArray()
            )
            ->get($this->getFormColumns())
            ->transform(function ($value) {
                return (array) $value;
            })
            ->toArray();
    }

    /**
     * 获取父级ID字段名称.
     *
     * @return string
     * @throws RuntimeException
     */
    public function getParentColumn(): string
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 获取标题字段名称.
     *
     * @return string
     * @throws RuntimeException
     */
    public function getTitleColumn(): string
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 获取排序字段名称.
     *
     * @return string
     * @throws RuntimeException
     */
    public function getOrderColumn(): string
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 保存层级数据排序.
     *
     * @param  array  $tree
     * @param  int  $parentId
     * @throws RuntimeException
     */
    public function saveOrder(array $tree = [], int $parentId = 0)
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 设置数据查询回调.
     *
     * @param $queryCallback
     * @return $this
     * @throws RuntimeException
     */
    public function withQuery($queryCallback): static
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * 获取层级数据.
     *
     * @return array
     * @throws RuntimeException
     */
    public function toTree(): array
    {
        throw new RuntimeException('Not support.');
    }

    /**
     * @return Builder
     */
    protected function newQuery(): Builder
    {
        return clone $this->queryBuilder;
    }
}
