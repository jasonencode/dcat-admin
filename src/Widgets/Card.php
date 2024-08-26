<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Dcat\Admin\Grid\LazyRenderable as LazyGrid;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;

class Card extends Widget
{
    protected string $view = 'admin::widgets.card';
    protected string $title;
    protected string $content;
    protected string $footer;
    protected array $tools = [];
    protected bool $divider = false;
    protected string $padding;

    public function __construct($title = '', $content = null)
    {
        if ($content === null) {
            $content = $title;
            $title = '';
        }

        $this->title($title);
        $this->content($content);

        $this->class('card');
        $this->id('card-'.Str::random(8));
    }

    /**
     * @return $this
     */
    public function withHeaderBorder(): static
    {
        $this->divider = true;

        return $this;
    }

    /**
     * 设置卡片间距.
     *
     * @param  string  $padding
     * @return Card
     */
    public function padding(string $padding): static
    {
        $this->padding = 'padding:'.$padding;

        return $this;
    }

    public function noPadding(): Card|static
    {
        return $this->padding('0');
    }

    /**
     * @param  Closure|string|Renderable  $content
     * @return $this
     */
    public function content(Renderable|Closure|string $content): static
    {
        if ($content instanceof LazyGrid) {
            $content->simple();
        }

        $this->content = $this->formatRenderable($content);

        return $this;
    }

    /**
     * @param  string  $content
     * @return $this
     */
    public function footer(string $content): static
    {
        $this->footer = $content;

        return $this;
    }

    /**
     * @param  string  $title
     * @return $this
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param  string|Closure|Renderable  $content
     * @return $this
     */
    public function tool(Renderable|string|Closure $content): static
    {
        $this->tools[] = $this->toString($content);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->toString($this->content),
            'footer' => $this->toString($this->footer),
            'tools' => $this->tools,
            'attributes' => $this->formatHtmlAttributes(),
            'padding' => $this->padding,
            'divider' => $this->divider,
        ];
    }
}
