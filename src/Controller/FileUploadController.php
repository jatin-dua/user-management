<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FileUploadController extends AbstractController
{
    #[Route('/api/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $uploadedFile = $request->files->get('file');
        // $fileType = $request->request->get('file_type');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file provided');
        }

        // // Validate the file type (you can also define a list of allowed types)
        // $allowedTypes = ['text/csv', 'text/plain', 'application/sql']; // Add your allowed types here
        // if (!in_array($fileType, $allowedTypes)) {
        //     throw new BadRequestHttpException('Invalid file type');
        // }

        // // Use the provided file type to set the extension or for any other processing
        // $extension = match ($fileType) {
        //     'text/plain' => 'txt',
        //     'text/csv' => 'csv',
        //     'application/sql' => 'sql',
        //     default => 'bin',
        // };

        // Define the directory where you want to store the uploaded file
        $uploadDirectory = $this->getParameter('upload_directory');

        $filename = pathinfo($uploadedFile->getClientOriginalName(), flags: PATHINFO_FILENAME).'.'.$uploadedFile->getClientOriginalExtension();

        // Move the uploaded file
        $uploadedFile->move($uploadDirectory, $filename);
    
        return new JsonResponse([
            'message' => 'File uploaded successfully',
            'filename' => $filename,
            // 'file_type' => $fileType
        ]);
    }
}
