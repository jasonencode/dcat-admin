<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Grid;

abstract class AbstractTool extends Grid\GridAction
{
    /**
     * @var string
     */
    protected string $style = 'btn btn-white waves-effect';

    /**
     * @return string
     */
    protected function html(): string
    {
        $this->appendHtmlAttribute('class', $this->style);

        return <<<HTML
<button {$this->formatHtmlAttributes()}>{$this->title()}</button>
HTML;
    }
}
