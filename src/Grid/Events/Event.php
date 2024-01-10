<?php

namespace Dcat\Admin\Grid\Events;

use Dcat\Admin\Grid;

abstract class Event
{
    /**
     * @var Grid|null
     */
    public ?Grid $grid = null;

    public array $payload = [];

    public function __construct(array $payload = [])
    {
        $this->payload = $payload;
    }

    public function setGrid(Grid $grid): void
    {
        $this->grid = $grid;
    }
}
