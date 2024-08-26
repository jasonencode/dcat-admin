<?php

namespace Dcat\Admin\Traits;

use Dcat\Admin\Contracts\LazyRenderable;

/**
 * @property string $target
 */
trait InteractsWithRenderApi
{
    /**
     * @var LazyRenderable
     */
    protected LazyRenderable $renderable;

    /**
     * @var string
     */
    protected string $loadScript;

    /**
     * 监听异步渲染完成事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onLoad(string $script): static
    {
        $this->loadScript .= ";$script";

        return $this;
    }

    public function getRenderable(): LazyRenderable
    {
        return $this->renderable;
    }

    public function setRenderable(?LazyRenderable $renderable): static
    {
        $this->renderable = $renderable;

        return $this;
    }

    protected function getRenderableScript(): string
    {
        if (! $this->getRenderable()) {
            return '';
        }

        $url = $this->renderable->getUrl();

        return <<<JS
target.on('$this->target:load', function () {
    Dcat.helpers.asyncRender('$url', function (html) {
        body.html(html);
        
        {$this->loadScript}
        
        target.trigger('$this->target:loaded');
    });
});
JS;
    }
}
