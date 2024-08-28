<?php

namespace Dcat\Admin\Grid\Concerns;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait HasNames
{
    /**
     * Grid name.
     *
     * @var string
     */
    protected string $_name = '';

    /**
     * Set name to grid.
     *
     * @param  string  $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->_name   = $name;
        $this->tableId = $this->makeName($this->tableId);

        return $this;
    }

    /**
     * Get name of grid.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Retrieve an input item from the request.
     *
     * @param  string  $key
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRequestInput(string $key): mixed
    {
        return $this->request->get($this->makeName($key));
    }

    /**
     * @param  string  $key
     * @return string
     */
    public function makeName(string $key): string
    {
        return $this->getNamePrefix().$key;
    }

    /**
     * @return string
     */
    public function getNamePrefix(): string
    {
        if (! $name = $this->getName()) {
            return '';
        }

        return $name.'_';
    }

    /**
     * @return string
     */
    public function getRowName(): string
    {
        return $this->makeName('grid-row');
    }

    /**
     * @return string
     */
    public function getSelectAllName(): string
    {
        return $this->makeName('grid-select-all');
    }

    /**
     * @return string
     */
    public function getPerPageName(): string
    {
        return $this->makeName('grid-per-page');
    }

    /**
     * @return string
     */
    public function getExportSelectedName(): string
    {
        return $this->makeName('export-selected');
    }
}
