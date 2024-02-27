<?php

namespace Dcat\Admin\Contracts;

interface LazyRenderable
{
    /**
     * 获取请求地址
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * 渲染组件.
     *
     * @return string
     */
    public function render(): string;
}
