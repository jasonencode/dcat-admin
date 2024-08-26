<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Illuminate\Contracts\Support\Renderable;

class Alert extends Widget
{
    protected string $view = 'admin::widgets.alert';
    protected string $title;
    protected string $content;
    protected string $style;
    protected string $icon;
    protected bool $showCloseBtn = false;

    public function __construct($content = '', $title = null, $style = 'danger')
    {
        $this->content($content);

        $this->title($title);

        $this->style($style);
    }

    /**
     * Set title.
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
        return $this->style()->icon('fa fa-info');
    }

    /**
     * Set success style.
     *
     * @return $this
     */
    public function success(): static
    {
        return $this->style('success')->icon('fa fa-check');
    }

    /**
     * Set warning style.
     *
     * @return $this
     */
    public function warning(): static
    {
        return $this->style('warning')->icon('fa fa-warning');
    }

    /**
     * Set warning style.
     *
     * @return $this
     */
    public function danger(): static
    {
        return $this->style('danger')->icon('fa fa-ban');
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
     * @param  string  $style
     * @return $this
     */
    public function style(string $style = 'info'): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Add icon.
     *
     * @param  string  $icon
     * @return $this
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * @return array
     */
    public function defaultVariables(): array
    {
        $this->class("alert alert-$this->style alert-dismissable");

        return [
            'title' => $this->title,
            'content' => $this->content,
            'icon' => $this->icon,
            'attributes' => $this->formatHtmlAttributes(),
            'showCloseBtn' => $this->showCloseBtn,
        ];
    }
}
