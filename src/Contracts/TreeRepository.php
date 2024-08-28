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

interface TreeRepository
{
    /**
     * 获取主键字段名称.
     *
     * @return string
     */
    public function getPrimaryKeyColumn(): string;

    /**
     * 获取父级ID字段名称.
     *
     * @return string
     */
    public function getParentColumn(): string;

    /**
     * 获取标题字段名称.
     *
     * @return string
     */
    public function getTitleColumn(): string;

    /**
     * 获取排序字段名称.
     *
     * @return string
     */
    public function getOrderColumn(): string;

    /**
     * 保存层级数据排序.
     *
     * @param  array  $tree
     * @param  int  $parentId
     */
    public function saveOrder(array $tree = [], int $parentId = 0);

    /**
     * 设置数据查询回调.
     *
     * @param $queryCallback
     * @return $this
     */
    public function withQuery($queryCallback): static;

    /**
     * 获取层级数据.
     *
     * @return array
     */
    public function toTree(): array;
}
