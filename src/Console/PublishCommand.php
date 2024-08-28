<?php

namespace Dcat\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\MountManager;

class PublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:publish
    {--force : Overwrite any existing files}
    {--lang : Publish language files}
    {--assets : Publish assets files}
    {--migrations : Publish migrations files}
    {--config : Publish configuration files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Re-publish dcat-admin's assets, configuration, language and migration files. If you want overwrite the existing files, you can add the `--force` option";

    /**
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * @var array
     */
    protected array $tags = [];

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * @throws FilesystemException
     */
    public function handle(): void
    {
        $options = [];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $tags = $this->getTags();

        foreach ($tags as $tag) {
            $this->call('vendor:publish', $options + ['--tag' => $tag]);
        }

        foreach ($this->tags as $tag) {
            $this->publishTag($tag);
        }

        $this->call('view:clear');
    }

    protected function getTags(): array
    {
        $tags = [];

        if ($this->option('lang')) {
            $this->tags[] = 'dcat-admin-lang';
        }
        if ($this->option('migrations')) {
            $tags[] = 'dcat-admin-migrations';
        }
        if ($this->option('assets')) {
            $tags[] = 'dcat-admin-assets';
        }
        if ($this->option('config')) {
            $tags[] = 'dcat-admin-config';
        }

        // 设置默认标签.
        if (!$tags && !$this->tags) {
            $this->tags[] = 'dcat-admin-lang';
            $tags = [
                'dcat-admin-migrations',
                'dcat-admin-assets',
                'dcat-admin-config',
            ];
        }

        return $tags;
    }

    /**
     * @throws FilesystemException
     */
    protected function publishTag($tag): void
    {
        $published = false;

        foreach ($this->pathsToPublish($tag) as $from => $to) {
            $this->publishItem($from, $to);

            $published = true;
        }

        if ($published) {
            $this->info('Publishing complete.');
        } else {
            $this->error('Unable to locate publishable resources.');
        }
    }

    protected function pathsToPublish($tag): array
    {
        return ServiceProvider::pathsToPublish(null, $tag);
    }

    /**
     * @throws FilesystemException
     */
    protected function publishItem($from, $to)
    {
        if ($this->files->isFile($from)) {
            return $this->publishFile($from, $to);
        } elseif ($this->files->isDirectory($from)) {
            return $this->publishDirectory($from, $to);
        }

        $this->error("Can't locate path: <$from>");
    }

    protected function publishFile($from, $to): void
    {
        if (!$this->files->exists($to) || $this->option('force')) {
            $this->createParentDirectory(dirname($to));

            $this->files->copy($from, $to);

            $this->status($from, $to, 'File');
        }
    }

    /**
     * @throws FilesystemException
     */
    protected function publishDirectory($from, $to): void
    {
        $localClass = class_exists(LocalAdapter::class) ? LocalAdapter::class : LocalFilesystemAdapter::class;

        $this->moveManagedFiles(new MountManager([
            'from' => new Flysystem(new $localClass($from)),
            'to' => new Flysystem(new $localClass($to)),
        ]));

        $this->status($from, $to, 'Directory');
    }

    /**
     * @throws FilesystemException
     */
    protected function moveManagedFiles(MountManager $manager): void
    {
        if (method_exists($manager, 'put')) {
            foreach ($manager->listContents('from://', true) as $file) {
                if (
                    $file['type'] === 'file'
                    && (!$manager->has('to://'.$file['path']) || $this->option('force'))
                    && !$this->isExceptPath($manager, $file['path'])
                ) {
                    $manager->put('to://'.$file['path'], $manager->read('from://'.$file['path']));
                }
            }

            return;
        }

        foreach ($manager->listContents('from://', true) as $file) {
            $path = Str::after($file['path'], 'from://');

            if ($file['type'] === 'file' && (!$manager->fileExists('to://'.$path) || $this->option('force'))) {
                $manager->write('to://'.$path, $manager->read($file['path']));
            }
        }
    }

    protected function isExceptPath($manager, $path): bool
    {
        return $manager->has('to://'.$path) && Str::contains($path, ['/menu.php', '/global.php']);
    }

    protected function createParentDirectory($directory): void
    {
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
    }

    protected function status($from, $to, $type): void
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->line('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }
}
