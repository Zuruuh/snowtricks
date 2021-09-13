<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

class FileService
{
    
    private FileSystem $fileSystem;

    public function __construct()
    {
        $this->fileSystem = new Filesystem();
    }

    public function createFolder(string $folder): string
    {
        if (!$this->fileSystem->exists(getcwd() . $folder)) {
            $this->fileSystem->mkdir(getcwd() . $folder);
        }
        return getcwd() . $folder;
    }

    public function move(UploadedFile $file, string $path, string $name): string
    {
        $file->move(getcwd() . $path . "/", $name);
        return $path . "/" . $name;
    }

    public function deleteFolder(string $folder): void
    {
        dump($folder);
        $this->fileSystem->remove(getcwd() . $folder);
    }
}
