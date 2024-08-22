<?php

namespace Dcat\Admin\Grid;

use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Exporters\AbstractExporter;
use Dcat\Admin\Grid\Exporters\ExporterInterface;

/**
 * @mixin AbstractExporter
 *
 * @method mixed export
 */
class Exporter
{
    /**
     * Export scope constants.
     */
    const SCOPE_ALL = 'all';
    const SCOPE_CURRENT_PAGE = 'page';
    const SCOPE_SELECTED_ROWS = 'selected';

    /**
     * Available exporter drivers.
     *
     * @var array
     */
    protected static array $drivers = [];

    /**
     * Export query name.
     *
     * @var string
     */
    protected string $queryName = '_export_';

    /**
     * @var Grid
     */
    protected Grid $grid;

    /**
     * @var ExporterInterface|null
     */
    protected ?ExporterInterface $driver = null;

    /**
     * @var array
     */
    protected array $options = [
        'show_export_all' => true,
        'show_export_current_page' => true,
        'show_export_selected_rows' => true,
        'chunk_size' => 5000,
    ];

    /**
     * Create a new Exporter instance.
     *
     * @param  Grid  $grid
     */
    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
    }

    /**
     *  Set option for exporter.
     *
     * @param  string  $key
     * @param  mixed|null  $value
     * @return $this
     */
    public function option(string $key, mixed $value = null): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Disable export all.
     *
     * @param  bool  $value
     * @return Exporter
     */
    public function disableExportAll(bool $value = true): static
    {
        return $this->option('show_export_all', !$value);
    }

    /**
     * Disable export current page.
     *
     * @param  bool  $value
     * @return $this
     */
    public function disableExportCurrentPage(bool $value = true): static
    {
        return $this->option('show_export_current_page', !$value);
    }

    /**
     * Disable export selected rows.
     *
     * @param  bool  $value
     * @return $this
     */
    public function disableExportSelectedRow(bool $value = true): static
    {
        return $this->option('show_export_selected_rows', !$value);
    }

    /**
     * @param  int  $value
     * @return $this
     */
    public function chunkSize(int $value): static
    {
        return $this->option('chunk_size', $value);
    }

    /**
     * Get export query name.
     *
     * @return string
     */
    public function getQueryName(): string
    {
        return $this->grid->makeName($this->queryName);
    }

    /**
     * Extends new exporter driver.
     *
     * @param $driver
     * @param $extend
     */
    public static function extend($driver, $extend): void
    {
        static::$drivers[$driver] = $extend;
    }

    /**
     * Resolve export driver.
     *
     * @param  null  $driver
     * @return ExporterInterface
     */
    public function resolve($driver = null): ExporterInterface
    {
        if ($this->driver) {
            return $this->driver;
        }

        if ($driver instanceof AbstractExporter) {
            $this->driver = $driver->setGrid($this->grid);
        } elseif ($driver instanceof ExporterInterface) {
            $this->driver = $driver;
        } else {
            $this->driver = $this->newDriver($driver);
        }

        return $this->driver;
    }

    /**
     * @return AbstractExporter|ExporterInterface
     */
    public function driver(): Exporters\AbstractExporter|ExporterInterface
    {
        return $this->driver ?: $this->resolve();
    }

    /**
     * Get export driver.
     *
     * @param  string  $driver
     * @return AbstractExporter
     */
    protected function newDriver(string $driver): ExporterInterface
    {
        if (!$driver || !array_key_exists($driver, static::$drivers)) {
            return $this->makeDefaultDriver();
        }

        $driver = new static::$drivers[$driver]();

        if (method_exists($driver, 'setGrid')) {
            $driver->setGrid($this->grid);
        }

        return $driver;
    }

    /**
     * Get default exporter.
     *
     * @return Grid\Exporters\ExcelExporter
     */
    public function makeDefaultDriver(): Exporters\ExcelExporter
    {
        return Grid\Exporters\ExcelExporter::make()->setGrid($this->grid);
    }

    /**
     * Format query for export url.
     *
     * @param  int|string  $scope
     * @param  null  $args
     * @return array
     */
    public function formatExportQuery(int|string $scope = '', $args = null): array
    {
        $query = '';

        if ($scope == static::SCOPE_ALL) {
            $query = $scope;
        }

        if ($scope == static::SCOPE_CURRENT_PAGE) {
            $query = "$scope:$args";
        }

        if ($scope == static::SCOPE_SELECTED_ROWS) {
            $query = "$scope:$args";
        }

        return [$this->getQueryName() => $query];
    }

    /**
     * @param $method
     * @param $arguments
     * @return Exporter
     */
    public function __call($method, $arguments)
    {
        $this->driver()->$method(...$arguments);

        return $this;
    }
}
