<?php

namespace Dcat\Admin\Repositories;

use Dcat\Admin\Contracts\Repository as RepositoryInterface;
use Dcat\Admin\Contracts\TreeRepository;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Show;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;

abstract class Repository implements RepositoryInterface, TreeRepository
{
    use Macroable;

    /**
     * @var string
     */
    protected string $keyName = 'id';

    /**
     * @var bool
     */
    protected bool $isSoftDeletes = false;

    /**
     * 获取主键名称.
     *
     * @return string
     */
    public function getKeyName(): string
    {
        return $this->keyName ?: 'id';
    }

    /**
     * 设置主键名称.
     *
     * @param  array|string  $keyName
     */
    public function setKeyName(array|string $keyName): void
    {
        $this->keyName = $keyName;
    }

    /**
     * 获取创建时间字段.
     *
     * @return string
     */
    public function getCreatedAtColumn(): string
    {
        return 'created_at';
    }

    /**
     * 获取更新时间字段.
     *
     * @return string
     */
    public function getUpdatedAtColumn(): string
    {
        return 'updated_at';
    }

    /**
     * 是否使用软删除.
     *
     * @return bool
     */
    public function isSoftDeletes(): bool
    {
        return $this->isSoftDeletes;
    }

    /**
     * @param  bool  $isSoftDeletes
     */
    public function setIsSoftDeletes(?bool $isSoftDeletes): void
    {
        $this->isSoftDeletes = $isSoftDeletes;
    }

    /**
     * 获取Grid表格数据.
     *
     * @param  Grid\Model  $model
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|Collection|array
     */
    public function get(Grid\Model $model): LengthAwarePaginator|array|Collection
    {
        throw new RuntimeException('This repository does not support "get" method.');
    }

    /**
     * 获取编辑页面数据.
     *
     * @param  Form  $form
     * @return array|\Illuminate\Contracts\Support\Arrayable
     */
    public function edit(Form $form): array|Arrayable
    {
        throw new RuntimeException('This repository does not support "edit" method.');
    }

    /**
     * 获取详情页面数据.
     *
     * @param  Show  $show
     * @return array|\Illuminate\Contracts\Support\Arrayable
     */
    public function detail(Show $show): array|Arrayable
    {
        throw new RuntimeException('This repository does not support "detail" method.');
    }

    /**
     * 新增记录.
     *
     * @param  Form  $form
     * @return int|bool|\Dcat\Admin\Http\JsonResponse
     */
    public function store(Form $form): bool|int|JsonResponse
    {
        throw new RuntimeException('This repository does not support "store" method.');
    }

    /**
     * 查询更新前的行数据.
     *
     * @param  Form  $form
     * @return array|\Illuminate\Contracts\Support\Arrayable
     */
    public function updating(Form $form): array|Arrayable
    {
        throw new RuntimeException('This repository does not support "updating" method.');
    }

    /**
     * 更新数据.
     *
     * @param  Form  $form
     * @return bool|\Dcat\Admin\Http\JsonResponse
     */
    public function update(Form $form): bool|JsonResponse
    {
        throw new RuntimeException('This repository does not support "update" method.');
    }

    /**
     * 删除数据.
     *
     * @param  Form  $form
     * @param  array  $deletingData
     * @return bool|int|\Dcat\Admin\Http\JsonResponse
     */
    public function delete(Form $form, array $deletingData): bool|int|JsonResponse
    {
        throw new RuntimeException('This repository does not support "destroy" method.');
    }

    /**
     * 查询删除前的行数据.
     *
     * @param  Form  $form
     * @return array
     */
    public function deleting(Form $form): array
    {
        throw new RuntimeException('This repository does not support "deleting" method.');
    }

    /**
     * 获取主键字段名称.
     *
     * @return string
     */
    public function getPrimaryKeyColumn(): string
    {
        return $this->getKeyName();
    }

    /**
     *  获取父级ID字段名称.
     *
     * @return string
     */
    public function getParentColumn(): string
    {
        return 'parent_id';
    }

    /**
     * 获取标题字段名称.
     *
     * @return string
     */
    public function getTitleColumn(): string
    {
        return 'title';
    }

    /**
     * 获取排序字段名称.
     *
     * @return string
     */
    public function getOrderColumn(): string
    {
        return 'order';
    }

    /**
     * 保存层级数据排序.
     *
     * @param  array  $tree
     * @param  int  $parentId
     */
    public function saveOrder($tree = [], $parentId = 0)
    {
        throw new RuntimeException('This repository does not support "saveOrder" method.');
    }

    /**
     * 设置数据查询回调.
     *
     * @param $queryCallback
     * @return $this
     */
    public function withQuery($queryCallback): static
    {
        throw new RuntimeException('This repository does not support "withQuery" method.');
    }

    /**
     * 获取层级数据.
     *
     * @return array
     */
    public function toTree(): array
    {
        throw new RuntimeException('This repository does not support "toTree" method.');
    }

    /**
     * @param  mixed  ...$params
     * @return $this
     */
    public static function make(...$params): static
    {
        return new static(...$params);
    }
}
