<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Dcat\Admin\Grid\LazyRenderable as LazyGrid;
use Illuminate\Contracts\Support\Renderable;

class Box extends Widget
{
    protected string $view = 'admin::widgets.box';
    protected string $title = 'Box header';
    protected string $content = 'here is the box content.';
    protected array $tools = [];
    protected string $padding;

    public function __construct($title = '', $content = '')
    {
        if ($title) {
            $this->title($title);
        }

        if ($content) {
            $this->content($content);
        }

        $this->class('box');
    }

    /**
     * Set content padding.
     *
     * @param  string  $padding
     * @return Box
     */
    public function padding(string $padding): static
    {
        $this->padding = 'padding:'.$padding;

        return $this;
    }

    /**
     * Set box content.
     *
     * @param  string  $content
     * @return $this
     */
    public function content(string $content): static
    {
        if ($content instanceof LazyGrid) {
            $content->simple();
        }

        $this->content = $this->formatRenderable($content);

        return $this;
    }

    /**
     * Set box title.
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
     * Set box as removable.
     *
     * @return $this
     */
    public function removable(): static
    {
        $this->tools[] =
            '<button class="border-0 bg-white" data-action="remove"><i class="feather icon-x"></i></button>';

        return $this;
    }

    /**
     * Set box style.
     *
     * @param  string  $styles
     * @return $this|Box
     */
    public function style(string $styles): Box|static
    {
        $styles = array_map(function ($style) {
            return 'box-'.$style;
        }, (array) $styles);

        $this->class = $this->class.' '.implode(' ', $styles);

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
     * Add `box-solid` class to box.
     *
     * @return $this
     */
    public function solid(): static
    {
        return $this->style('solid');
    }

    /**
     * Variables in view.
     *
     * @return array
     */
    public function defaultVariables(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->toString($this->content),
            'tools' => $this->tools,
            'attributes' => $this->formatHtmlAttributes(),
            'padding' => $this->padding,
        ];
    }
}
