<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Illuminate\Contracts\Support\Renderable;

class Callout extends Widget
{
    protected string $view = 'admin::widgets.alert';
    protected string $title;
    protected string $content;
    protected string $style = 'default';
    protected bool $showCloseBtn = false;

    public function __construct($content = '', ?string $title = null, ?string $style = null)
    {
        $this->content($content);
        $this->title($title);
        $this->style($style);
    }

    /**
     * Set title.
     *
     * @param  string|null  $title
     * @return $this
     */
    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set contents.
     *
     * @param  string|Closure|Renderable  $content
     * @return $this
     */
    public function content(Renderable|string|Closure $content): static
    {
        $this->content = $this->toString($content);

        return $this;
    }

    /**
     * Set light style.
     *
     * @return $this
     */
    public function light(): static
    {
        return $this->style('light');
    }

    /**
     * Set primary style.
     *
     * @return $this
     */
    public function primary(): static
    {
        return $this->style('primary');
    }

    /**
     * Set info style.
     *
     * @return $this
     */
    public function info(): static
    {
        return $this->style();
    }

    /**
     * Set success style.
     *
     * @return $this
     */
    public function success(): static
    {
        return $this->style('success');
    }

    /**
     * Set warning style.
     *
     * @return $this
     */
    public function warning(): static
    {
        return $this->style('warning');
    }

    /**
     * Set warning style.
     *
     * @return $this
     */
    public function danger(): static
    {
        return $this->style('danger');
    }

    /**
     * Show close button.
     *
     * @param  bool  $value
     * @return $this
     */
    public function removable(bool $value = true): static
    {
        $this->showCloseBtn = $value;

        return $this;
    }

    /**
     * Add style.
     *
     * @param  string|null  $style
     * @return $this
     */
    public function style(?string $style = 'info'): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @return array
     */
    public function defaultVariables(): array
    {
        $this->class("callout callout-$this->style alert alert-dismissable");

        return [
            'title' => $this->title,
            'content' => $this->content,
            'attributes' => $this->formatHtmlAttributes(),
            'showCloseBtn' => $this->showCloseBtn,
        ];
    }
}
