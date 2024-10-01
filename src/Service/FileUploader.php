<?php

// src/Service/FileUploader.php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    // TODO: Fix manual autowiring of scalar value
    public function __construct(
        #[Autowire(param: 'upload_directory')]
        private string $uploadDirectory
        )
    {
    }

    public function upload(UploadedFile $file): array
    {
        // $newFilename = uniqid() . '.' . $extension;
        $newFileName = pathinfo(path: $file->getClientOriginalName(), flags: PATHINFO_FILENAME). '.' .  $file->getClientOriginalExtension();
        $newFilePath = $this->uploadDirectory . '/' . $newFileName;

        // Move the uploaded file
        $file->move($this->uploadDirectory, $newFileName);

        return [$newFileName, $newFilePath];
    }
}
