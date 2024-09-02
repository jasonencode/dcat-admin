<?php

namespace Dcat\Admin\Form\Field;

use Closure;
use Dcat\Admin\Exception\UploadException;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Traits\HasUploadedFile;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait UploadField
{
    use HasUploadedFile {
        HasUploadedFile::disk as _disk;
    }

    /**
     * Upload directory.
     *
     * @var Closure|string
     */
    protected Closure|string $directory = '';

    /**
     * File name.
     *
     * @var Closure|string|null
     */
    protected Closure|string|null $name = null;

    /**
     * Storage instance.
     *
     * @var Filesystem|null
     */
    protected ?Filesystem $storage = null;

    /**
     * If use unique name to store upload file.
     *
     * @var bool
     */
    protected bool $useUniqueName = false;

    /**
     * If use sequence name to store upload file.
     *
     * @var bool
     */
    protected bool $useSequenceName = false;

    /**
     * Controls the storage permission. Could be 'private' or 'public'.
     *
     * @var string|null
     */
    protected ?string $storagePermission = null;

    /**
     * Retain file when delete record from DB.
     *
     * @var bool
     */
    protected bool $retainable = false;

    /**
     * @var bool
     */
    protected bool $saveFullUrl = false;

    /**
     * Initialize the storage instance.
     *
     * @return void.
     * @throws Exception
     */
    protected function initStorage(): void
    {
        $this->disk(config('admin.upload.disk'));

        if (!$this->storage) {
            $this->storage = null;
        }
    }

    /**
     * If name already exists, rename it.
     *
     * @param  UploadedFile  $file
     * @return void
     * @throws Exception
     */
    public function renameIfExists(UploadedFile $file): void
    {
        if ($this->getStorage()->exists("{$this->getDirectory()}/$this->name")) {
            $this->name = $this->generateUniqueName($file);
        }
    }

    /**
     * @return string
     */
    protected function getUploadPath(): string
    {
        return "{$this->getDirectory()}/$this->name";
    }

    /**
     * Get store name of upload file.
     *
     * @param  UploadedFile  $file
     * @return string
     * @throws Exception
     */
    protected function getStoreName(UploadedFile $file): string
    {
        if ($this->useUniqueName) {
            return $this->generateUniqueName($file);
        }

        if ($this->useSequenceName) {
            return $this->generateSequenceName($file);
        }

        if ($this->name instanceof Closure) {
            $this->name = $this->name->call($this->values(), $file);
        }

        if ($this->name !== '' && is_string($this->name)) {
            return $this->name;
        }

        return $file->getClientOriginalName();
    }

    /**
     * Get directory for store file.
     *
     * @return string
     */
    public function getDirectory(): string
    {
        if ($this->directory instanceof Closure) {
            $this->directory = $this->directory->call($this->values(), $this->form);
        }

        return $this->directory;
    }

    /**
     * Indicates if the underlying field is retainable.
     *
     * @param  bool  $retainable
     * @return $this
     */
    public function retainable(bool $retainable = true): static
    {
        $this->retainable = $retainable;

        return $this;
    }

    public function saveFullUrl(bool $value = true): static
    {
        $this->saveFullUrl = $value;

        return $this;
    }

    /**
     * Upload File.
     *
     * @param  UploadedFile  $file
     * @return JsonResponse
     * @throws UploadException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function upload(UploadedFile $file): JsonResponse
    {
        $request = request();

        $id = $request->get('_id');

        if (!$id) {
            return $this->responseErrorMessage('Missing id');
        }

        if ($errors = $this->getValidationErrors($file)) {
            return $this->responseValidationMessage($errors);
        }

        $this->name = $this->getStoreName($file);

        if ($this->options['override']) {
            $this->remove();
        }

        $this->renameIfExists($file);

        $this->prepareFile($file);

        if (!is_null($this->storagePermission)) {
            $result = $this->getStorage()
                ->putFileAs($this->getDirectory(), $file, $this->name, $this->storagePermission);
        } else {
            $result = $this->getStorage()->putFileAs($this->getDirectory(), $file, $this->name);
        }

        if ($result) {
            $path = $this->getUploadPath();
            $url = $this->objectUrl($path);

            // 上传成功
            return $this->responseUploaded($this->saveFullUrl ? $url : $path, $url);
        }

        // 上传失败
        throw new UploadException(trans('admin.uploader.upload_failed'));
    }

    /**
     * @throws Exception
     */
    public function remove(): void
    {
        if ($this->getStorage()->exists("{$this->getDirectory()}/$this->name")) {
            $this->getStorage()->delete("{$this->getDirectory()}/$this->name");
        }
    }

    /**
     * @param  UploadedFile  $file
     */
    protected function prepareFile(UploadedFile $file)
    {
    }

    /**
     * Specify the directory and name for upload file.
     *
     * @param  Closure|string  $directory
     * @param  string|null  $name
     * @return $this
     */
    public function move(Closure|string $directory, string $name = null): static
    {
        $this->dir($directory);

        $this->name($name);

        return $this;
    }

    /**
     * Specify the directory upload file.
     *
     * @param  Closure|string  $dir
     * @return $this
     */
    public function dir(Closure|string $dir): static
    {
        if ($dir) {
            $this->directory = $dir;
        }

        return $this;
    }

    /**
     * Set name of store name.
     *
     * @param  Closure|string|null  $name
     * @return $this
     */
    public function name(Closure|string|null $name = null): static
    {
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * Use unique name for store upload file.
     *
     * @return $this
     */
    public function uniqueName(): static
    {
        $this->useUniqueName = true;

        return $this;
    }

    /**
     * Use sequence name for store upload file.
     *
     * @return $this
     */
    public function sequenceName(): static
    {
        $this->useSequenceName = true;

        return $this;
    }

    /**
     * Generate a unique name for uploaded file.
     *
     * @param  UploadedFile  $file
     * @return string
     */
    protected function generateUniqueName(UploadedFile $file): string
    {
        $hash = File::hash($file);

        return $hash.'.'.$file->getClientOriginalExtension();
    }

    /**
     * Generate a sequence name for uploaded file.
     *
     * @param  UploadedFile  $file
     * @return string
     * @throws Exception
     */
    protected function generateSequenceName(UploadedFile $file): string
    {
        $index = 1;
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $newName = $originalName.'_'.$index.'.'.$extension;

        while ($this->getStorage()->exists("{$this->getDirectory()}/$newName")) {
            $index++;
            $newName = $originalName.'_'.$index.'.'.$extension;
        }

        return $newName;
    }

    /**
     * @param  UploadedFile  $file
     * @return false|string|void
     */
    protected function getValidationErrors(UploadedFile $file)
    {
        $data = $rules = $attributes = [];

        // 如果文件上传有错误，则直接返回错误信息
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $file->getErrorMessage();
        }

        if (!$fieldRules = $this->getRules()) {
            return false;
        }

        Arr::set($rules, $this->column, $fieldRules);
        Arr::set($attributes, $this->column, $this->label);
        Arr::set($data, $this->column, $file);

        /* @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make($data, $rules, $this->validationMessages, $attributes);

        if (!$validator->passes()) {
            $errors = $validator->errors()->getMessages()[$this->column];

            return implode('<br> ', $errors);
        }
    }

    /**
     * Destroy original files.
     *
     * @return void.
     * @throws Exception
     */
    public function destroy(): void
    {
        $this->deleteFile($this->original);
    }

    /**
     * Destroy original files.
     *
     * @param $file
     * @throws Exception
     */
    public function destroyIfChanged($file): void
    {
        if (!$file || !$this->original) {
            $this->destroy();

            return;
        }

        $file = array_filter((array) $file);
        $original = (array) $this->original;

        $this->deleteFile(Arr::except(array_combine($original, $original), $file));
    }

    /**
     * Destroy files.
     *
     * @param  array|string  $path
     * @throws Exception
     */
    public function deleteFile(array|string $path): void
    {
        if (!$path || $this->retainable) {
            return;
        }

        if (method_exists($this, 'destroyThumbnail')) {
            $this->destroyThumbnail($path);
        }

        $storage = $this->getStorage();

        foreach ((array) $path as $path) {
            if ($storage->exists($path)) {
                $storage->delete($path);
            } else {
                $prefix = $storage->url('');
                $path = str_replace($prefix, '', $path);

                if ($storage->exists($path)) {
                    $storage->delete($path);
                }
            }
        }
    }

    /**
     * Get storage instance.
     *
     * @return Filesystem
     * @throws Exception
     */
    public function getStorage(): Filesystem
    {
        if ($this->storage === null) {
            $this->initStorage();
        }

        return $this->storage;
    }

    /**
     * Set disk for storage.
     *
     * @param  string|null  $disk  Disks defined in `config/filesystems.php`.
     * @return $this
     *
     * @throws Exception
     */
    public function disk(?string $disk = null): static
    {
        try {
            $this->storage = Storage::disk($disk);
        } catch (Exception $exception) {
            if (!array_key_exists($disk, config('filesystems.disks'))) {
                admin_error(
                    'Config error.',
                    "Disk [$disk] not configured, please add a disk config in `config/filesystems.php`."
                );

                return $this;
            }

            throw $exception;
        }

        return $this;
    }

    /**
     * Get file visit url.
     *
     * @param  string  $path
     * @return string
     * @throws Exception
     */
    public function objectUrl(string $path): string
    {
        if (URL::isValidUrl($path)) {
            return $path;
        }

        return $this->getStorage()->url($path);
    }

    /**
     * @param $permission
     * @return $this
     */
    public function storagePermission($permission): static
    {
        $this->storagePermission = $permission;

        return $this;
    }
}
