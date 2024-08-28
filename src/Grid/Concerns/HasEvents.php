<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid\Events;
use Illuminate\Support\Facades\Event;

trait HasEvents
{
    /**
     * @var array
     */
    protected array $dispatched = [];

    /**
     * 监听事件.
     *
     * @param  string  $class
     * @param  Closure  $callback
     */
    public function listen(string $class, Closure $callback): void
    {
        Event::listen($class, function (Events\Event $event) use ($callback) {
            if ($event->grid !== $this) {
                return;
            }

            return $callback($event->grid, ...$event->payload);
        });
    }

    /**
     * 触发事件.
     *
     * @param  Events\Event  $event
     */
    public function fire(Events\Event $event): void
    {
        $this->dispatched[get_class($event)] = $event;

        $event->setGrid($this);

        Event::dispatch($event);
    }

    /**
     * 只触发一次.
     *
     * @param  Events\Event  $event
     * @return void
     */
    public function fireOnce(Events\Event $event): void
    {
        if (isset($this->dispatched[get_class($event)])) {
            return;
        }

        $this->fire($event);
    }
}
