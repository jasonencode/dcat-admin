<?php

namespace Dcat\Admin\Form\Field;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait ImageField
{
    /**
     * Intervention calls.
     *
     * @var array
     */
    protected array $interventionCalls = [];

    /**
     * Thumbnail settings.
     *
     * @var array
     */
    protected array $thumbnails = [];

    protected static array $interventionAlias = [
        'filling' => 'fill',
    ];

    /**
     * Execute Intervention calls.
     *
     * @param  string  $target
     * @param  string  $mime
     * @return string
     */
    public function callInterventionMethods(string $target, string $mime): string
    {
        if (! empty($this->interventionCalls)) {
            $image = app('image')->read($target);

            $mime = $mime ?: finfo_file(finfo_open(FILEINFO_MIME_TYPE), $target);

            foreach ($this->interventionCalls as $call) {
                call_user_func_array(
                    [$image, $call['method']],
                    $call['arguments']
                )->save($target, null, $mime);
            }
        }

        return $target;
    }

    /**
     * Call intervention methods.
     *
     * @param  string  $method
     * @param  array  $arguments
     * @return $this
     *
     * @throws \Exception
     */
    public function __call($method, $arguments)
    {
        if (static::hasMacro($method)) {
            return parent::__call($method, $arguments);
        }

        $this->interventionCalls[] = [
            'method'    => static::$interventionAlias[$method] ?? $method,
            'arguments' => $arguments,
        ];

        return $this;
    }

    /**
     * @param  array|string  $name
     * @param  int|null  $width
     * @param  int|null  $height
     * @return $this
     */
    public function thumbnail(array|string $name, int $width = null, int $height = null): static
    {
        if (func_num_args() == 1 && is_array($name)) {
            foreach ($name as $key => $size) {
                if (count($size) >= 2) {
                    $this->thumbnails[$key] = $size;
                }
            }
        } elseif (func_num_args() == 3) {
            $this->thumbnails[$name] = [$width, $height];
        }

        return $this;
    }

    /**
     * Destroy original thumbnail files.
     *
     * @param  null  $file
     * @param  bool  $force
     * @return void.
     * @throws \Exception
     */
    public function destroyThumbnail($file = null, bool $force = false): void
    {
        if ($this->retainable && ! $force) {
            return;
        }

        $file = $file ?: $this->original;
        if (! $file) {
            return;
        }

        if (is_array($file)) {
            foreach ($file as $f) {
                $this->destroyThumbnail($f, $force);
            }

            return;
        }

        foreach ($this->thumbnails as $name => $_) {
            $ext  = pathinfo($file, PATHINFO_EXTENSION);
            $path = Str::replaceLast('.'.$ext, '', $file);
            $path = $path.'-'.$name.'.'.$ext;

            if ($this->getStorage()->exists($path)) {
                $this->getStorage()->delete($path);
            }
        }
    }

    /**
     * Upload file and delete original thumbnail files.
     *
     * @param  UploadedFile  $file
     * @return $this
     * @throws \Exception
     */
    protected function uploadAndDeleteOriginalThumbnail(UploadedFile $file): static
    {
        foreach ($this->thumbnails as $name => $size) {
            $ext    = pathinfo($this->name, PATHINFO_EXTENSION);
            $path   = Str::replaceLast('.'.$ext, '', $this->name);
            $path   = $path.'-'.$name.'.'.$ext;
            $image  = app('image')->read($file);
            $action = $size[2] ?? 'resize';
            $image->$action($size[0], $size[1]);

            if (! is_null($this->storagePermission)) {
                $this->getStorage()->put(
                    "{$this->getDirectory()}/$path",
                    $image->encodeByExtension($ext),
                    $this->storagePermission
                );
            } else {
                $this->getStorage()->put(
                    "{$this->getDirectory()}/$path",
                    $image->encodeByExtension($ext)
                );
            }
        }

        if (! is_array($this->original)) {
            $this->destroyThumbnail();
        }

        return $this;
    }
}
