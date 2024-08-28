<?php

namespace Dcat\Admin\Grid\ColumnSelector;

use Illuminate\Contracts\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

class CacheStore extends SessionStore
{
    protected Repository $driver;

    protected mixed $ttl;

    public function __construct($driver = 'file', $ttl = 25920000)
    {
        $this->driver = cache()->driver($driver);
        $this->ttl    = $ttl;
    }

    public function store(array $input): void
    {
        $this->driver->put($this->getKey(), $input, $this->ttl);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get()
    {
        return $this->driver->get($this->getKey());
    }

    public function forget(): void
    {
        $this->driver->forget($this->getKey());
    }
}
