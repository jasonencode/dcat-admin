<?php
/*
 * This file is part of the dcat-admin.
 *
 * (c) jqh <841324345@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Dcat\Admin\Contracts;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Show;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

interface Repository
{
    /**
     * 获取主键名称.
     *
     * @return string|array
     */
    public function getKeyName(): array|string;

    /**
     * 获取创建时间字段.
     *
     * @return string
     */
    public function getCreatedAtColumn(): string;

    /**
     * 获取更新时间字段.
     *
     * @return string
     */
    public function getUpdatedAtColumn(): string;

    /**
     * 是否使用软删除.
     *
     * @return bool
     */
    public function isSoftDeletes(): bool;

    /**
     * 获取Grid表格数据.
     *
     * @param  Grid\Model  $model
     * @return LengthAwarePaginator|Collection|array
     */
    public function get(Grid\Model $model): LengthAwarePaginator|array|Collection;

    /**
     * 获取编辑页面数据.
     *
     * @param  Form  $form
     * @return array|Arrayable
     */
    public function edit(Form $form): array|Arrayable;

    /**
     * 获取详情页面数据.
     *
     * @param  Show  $show
     * @return array|Arrayable
     */
    public function detail(Show $show): array|Arrayable;

    /**
     * 新增记录.
     *
     * @param  Form  $form
     * @return int|bool|JsonResponse
     */
    public function store(Form $form): bool|int|JsonResponse;

    /**
     * 查询更新前的行数据.
     *
     * @param  Form  $form
     * @return array|Arrayable
     */
    public function updating(Form $form): array|Arrayable;

    /**
     * 更新数据.
     *
     * @param  Form  $form
     * @return bool|JsonResponse
     */
    public function update(Form $form): bool|JsonResponse;

    /**
     * 删除数据.
     *
     * @param  Form  $form
     * @param  array  $deletingData
     * @return mixed|JsonResponse
     */
    public function delete(Form $form, array $deletingData): mixed;

    /**
     * 查询删除前的行数据.
     *
     * @param  Form  $form
     * @return array|Arrayable
     */
    public function deleting(Form $form): array|Arrayable;
}
