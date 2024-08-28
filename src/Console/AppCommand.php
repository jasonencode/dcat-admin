<?php

namespace Dcat\Admin\Console;

use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class AppCommand extends InstallCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:app {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new application';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws FileNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(): void
    {
        $this->addConfig();
        $this->initAdminDirectory();

        $this->info('Done.');
    }

    /**
     * @throws FileNotFoundException
     */
    protected function addConfig(): void
    {
        /* @var Filesystem $files */
        $files = $this->laravel['files'];

        $app = Helper::slug($namespace = $this->argument('name'));

        $files->put(
            $config = config_path($app.'.php'),
            str_replace(
                ['DummyNamespace', 'DummyApp'],
                [$namespace, $app],
                $files->get(__DIR__.'/stubs/config.stub')
            )
        );

        config(['admin' => include $config]);
    }

    /**
     * Set admin directory.
     *
     * @return void
     */
    protected function setDirectory(): void
    {
        $this->directory = app_path($this->argument('name'));
    }
}
