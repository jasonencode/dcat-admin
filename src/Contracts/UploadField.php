<?php

namespace Dcat\Admin\Contracts;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

interface UploadField
{
    /**
     * Upload File.
     *
     * @param  UploadedFile  $file
     * @return Response
     */
    public function upload(UploadedFile $file): Response;

    /**
     * Destroy original files.
     *
     * @return void.
     */
    public function destroy(): void;

    /**
     * Destroy files.
     *
     * @param  array|string  $path
     */
    public function deleteFile(array|string $path);
}
