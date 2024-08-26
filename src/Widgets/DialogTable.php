<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Renderable;
use Throwable;

class DialogTable extends Widget
{
    protected string $view = 'admin::widgets.dialogtable';

    /**
     * @var string
     */
    protected string $title;

    /**
     * @var LazyTable
     */
    protected LazyTable $table;

    /**
     * @var string
     */
    protected string $width = '825px';

    /**
     * @var string|Closure|Renderable
     */
    protected string|Closure|Renderable $button;

    /**
     * @var string|Closure|Renderable
     */
    protected string|Closure|Renderable $footer;

    /**
     * show max or min.
     *
     * @var bool
     */
    protected bool $maxmin = true;

    /**
     * resize setting.
     *
     * @var bool
     */
    protected bool $resize = true;

    /**
     * @var array
     */
    protected array $events = ['shown' => null, 'hidden' => null, 'load' => null];

    public function __construct($title = null, LazyRenderable $table = null)
    {
        if ($title instanceof LazyRenderable) {
            $table = $title;
            $title = null;
        }

        $this->title($title);
        $this->from($table);

        $this->elementClass = 'dialog-table-container';

        $this->class('dialog-table');
    }

    /**
     * 设置异步表格实例.
     *
     * @param  LazyRenderable|null  $renderable
     * @return $this
     */
    public function from(?LazyRenderable $renderable): static
    {
        if (!$renderable) {
            return $this;
        }

        $this->table = LazyTable::make($renderable)->simple()->runScript(false);

        return $this;
    }

    /**
     * 设置弹窗标题.
     *
     * @param  string  $title
     * @return $this
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置弹窗宽度.
     *
     * @param  string  $width
     * @return $this
     * @example
     *    $this->width('500px');
     *    $this->width('50%');
     *
     */
    public function width(string $width): static
    {
        $this->width = $width;

        return $this;
    }

    /**
     * show max or min.
     *
     * @param  bool  $maxmin
     * @return $this
     */
    public function maxmin(bool $maxmin): static
    {
        $this->maxmin = $maxmin;

        return $this;
    }

    /**
     * resize setting.
     *
     * @param  bool  $resize
     * @return $this
     */
    public function resize(bool $resize): static
    {
        $this->resize = $resize;

        return $this;
    }

    /**
     * 设置点击按钮HTML.
     *
     * @param  string|Closure|Renderable  $button
     * @return $this
     */
    public function button(Renderable|string|Closure $button): static
    {
        $this->button = $button;

        return $this;
    }

    /**
     * 监听弹窗打开事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onShown(string $script): static
    {
        $this->events['shown'] .= ';'.$script;

        return $this;
    }

    /**
     * 监听弹窗隐藏事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onHidden(string $script): static
    {
        $this->events['hidden'] .= ';'.$script;

        return $this;
    }

    /**
     * 监听表格加载完毕事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onLoad(string $script): static
    {
        $this->events['load'] .= ';'.$script;

        return $this;
    }

    /**
     * 设置弹窗底部内容.
     *
     * @param  string|Closure|Renderable  $footer
     * @return $this
     */
    public function footer(Renderable|string|Closure $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * @return LazyTable
     */
    public function getTable(): LazyTable
    {
        return $this->table;
    }

    public function render(): string
    {
        $this->addVariables([
            'title' => $this->title,
            'width' => $this->width,
            'button' => $this->renderButton(),
            'table' => $this->renderTable(),
            'footer' => $this->renderFooter(),
            'events' => $this->events,
            'maxmin' => $this->maxmin,
            'resize' => $this->resize,
        ]);

        return parent::render();
    }

    /**
     * @throws Throwable
     */
    protected function renderTable(): string
    {
        return $this->table->render();
    }

    protected function renderFooter(): string
    {
        return Helper::render($this->footer);
    }

    protected function renderButton(): string
    {
        if (!$this->button) {
            return '';
        }

        $button = Helper::render($this->button);

        // 如果没有HTML标签则添加一个 a 标签
        if (!preg_match('/(<\/\w+\s*>+)/i', $button)) {
            $button = "<a href=\"javascript:void(0)\">$button</a>";
        }

        return $button;
    }
}
