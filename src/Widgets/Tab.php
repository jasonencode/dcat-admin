<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Illuminate\Contracts\Support\Renderable;
use Throwable;

class Tab extends Widget
{
    const TYPE_CONTENT = 1;
    const TYPE_LINK = 2;

    /**
     * @var string
     */
    protected string $view = 'admin::widgets.tab';

    /**
     * @var array
     */
    protected array $data = [
        'id' => '',
        'title' => '',
        'tabs' => [],
        'dropDown' => [],
        'active' => 0,
        'padding' => null,
        'tabStyle' => '',
    ];

    /**
     * Add a tab and its contents.
     *
     * @param  string  $title
     * @param  string|Renderable  $content
     * @param  bool  $active
     * @param  string|null  $id
     * @return $this
     */
    public function add(string $title, Renderable|string $content, bool $active = false, string $id = null): static
    {
        $this->data['tabs'][] = [
            'id' => $id ?: mt_rand(),
            'title' => $title,
            'content' => $this->toString($this->formatRenderable($content)),
            'type' => static::TYPE_CONTENT,
        ];

        if ($active) {
            $this->data['active'] = count($this->data['tabs']) - 1;
        }

        return $this;
    }

    /**
     * Add a link on tab.
     *
     * @param  string  $title
     * @param  string  $href
     * @param  bool  $active
     * @return $this
     */
    public function addLink(string $title, string $href, bool $active = false): static
    {
        $this->data['tabs'][] = [
            'id' => mt_rand(),
            'title' => $title,
            'href' => $href,
            'type' => static::TYPE_LINK,
        ];

        if ($active) {
            $this->data['active'] = count($this->data['tabs']) - 1;
        }

        return $this;
    }

    /**
     * Set tab content padding.
     *
     * @param  string  $padding
     * @return Tab
     */
    public function padding(string $padding): static
    {
        $this->data['padding'] = 'padding:'.$padding;

        return $this;
    }

    public function noPadding(): static
    {
        return $this->padding('0');
    }

    /**
     * Set title.
     *
     * @param  string  $title
     * @return Tab
     */
    public function title(string $title = ''): static
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Set drop-down items.
     *
     * @param  array  $links
     * @return $this
     */
    public function dropdown(array $links): static
    {
        if (is_array($links[0])) {
            foreach ($links as $link) {
                call_user_func([$this, 'dropDown'], $link);
            }

            return $this;
        }

        $this->data['dropDown'][] = [
            'name' => $links[0],
            'href' => $links[1],
        ];

        return $this;
    }

    public function withCard(): static
    {
        return $this
            ->class('card', true)
            ->style('padding:.25rem .4rem .4rem');
    }

    public function vertical(): static
    {
        return $this
            ->class('nav-vertical d-block', true)
            ->style('padding:0!important;')
            ->tabStyle('nav-left flex-column');
    }

    public function theme(string $style = 'primary'): static
    {
        return $this
            ->class('nav-theme-'.$style, true)
            ->style('padding:0!important;');
    }

    public function tabStyle($type): static
    {
        $this->data['tabStyle'] = $type;

        return $this;
    }

    /**
     * Render Tab.
     *
     * @return string
     * @throws Throwable
     */
    public function render(): string
    {
        $data = array_merge(
            $this->data,
            ['attributes' => $this->formatHtmlAttributes()]
        );

        $this->setupScript();

        return view($this->view, $data)->render();
    }

    /**
     * Setup script.
     */
    protected function setupScript(): void
    {
        $script = <<<'SCRIPT'
            var hash = document.location.hash;
            if (hash) {
                $('.nav-tabs a[href="' + hash + '"]').tab('show');
            }
            
            // Change hash for page-reload
            $('.nav-tabs a').on('shown.bs.tab', function (e) {
                history.pushState(null,null, e.target.hash);
            });
            SCRIPT;
        Admin::script($script);
    }
}
