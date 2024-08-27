<?php

namespace Dcat\Admin\Http\Controllers;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TinymceController
{
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $disk = $this->disk();

        $newName = $this->generateNewName($file);

        $dir = date('Y/m/d');
        $disk->putFileAs($dir, $file, $newName);

        return ['location' => $disk->url("$dir/$newName")];
    }

    protected function generateNewName(UploadedFile $file)
    {
        $hash = File::hash($file);
        return $hash.'.'.$file->getClientOriginalExtension();
    }

    /**
     * @return Filesystem
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function disk()
    {
        $disk = request()->get('disk') ?: config('admin.upload.disk');

        return Storage::disk($disk);
    }
}
