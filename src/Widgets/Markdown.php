<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;

class Markdown extends Widget
{
    protected string $view = 'admin::widgets.markdown';

    /**
     * @var string|Renderable
     */
    protected string|Renderable $content;

    /**
     * 配置.
     *
     * @var array
     */
    protected array $options = [
        'htmlDecode'      => 'style,script,iframe',
        'emoji'           => true,
        'taskList'        => true,
        'tex'             => true,
        'flowChart'       => true,
        'sequenceDiagram' => true,
    ];

    public function __construct($markdown = null)
    {
        if ($markdown !== null) {
            $this->content($markdown);
        }

        $this->id('mkd-'.Str::random(8));
    }

    /**
     * @param  string|Renderable  $markdown
     * @return $this
     */
    public function content(Renderable|string $markdown): static
    {
        $this->content = &$markdown;

        return $this;
    }

    protected function renderContent(): string
    {
        return Helper::render($this->content);
    }

    public function render(): string
    {
        $this->addVariables([
            'id'      => $this->id(),
            'content' => $this->renderContent(),
        ]);

        return parent::render();
    }
}
