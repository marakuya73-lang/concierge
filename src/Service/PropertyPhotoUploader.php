<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class PropertyPhotoUploader
{
    public function __construct(private string $uploadDirectory)
    {
    }

    public function upload(UploadedFile $file): string
    {
        $extension = $file->guessExtension() ?: 'jpg';
        $filename = bin2hex(random_bytes(16)).'.'.$extension;
        $file->move($this->uploadDirectory, $filename);

        return $filename;
    }

    public function remove(string $filename): void
    {
        $path = $this->uploadDirectory.'/'.$filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
