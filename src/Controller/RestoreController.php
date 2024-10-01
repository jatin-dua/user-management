<?php

namespace App\Controller;

use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RestoreController extends AbstractController
{
    private $fileUploader;
    public function __construct(FileUploader $fileUploader) 
    {
        $this->fileUploader = $fileUploader;
    }

    #[Route('/api/restore', name: 'app_restore', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $dbName = $this->getParameter('database_name');
        $dbUser = $this->getParameter('database_user');
        $dbPassword = $this->getParameter('database_password');
        
        $backupPath = '';
        $uploadedSqlFile = $request->files->get('file');
        if ($uploadedSqlFile) {
            [$newFileName, $newFilePath] = $this->fileUploader->upload($uploadedSqlFile);
            $backupPath = $newFilePath;

        } else {
            $projectDir = $this->getParameter('kernel.project_dir');
            $backupPath = "$projectDir/db_backup/backup.sql";
        }

        $backupCommand = [
            'docker',
            'exec',
            '-i',
            'database',
            'mysql',
            '-u',
            $dbUser,
            "-p$dbPassword",
            $dbName,
        ];

        $process = new Process($backupCommand);
        $process->setTimeout(300);
        $process->setInput(file_get_contents($backupPath));
        // Execute the command and capture output
        try {
            $process->mustRun();
            return new Response("Database restored from $backupPath", Response::HTTP_OK);
        } catch (ProcessFailedException $exception) {
            // Log the exception details
            return new Response(
                'Failed to restore database: ' . $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
