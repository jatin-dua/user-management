<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupController extends AbstractController
{
    #[Route('/api/backup', name: 'app_backup', methods: ['GET'])]
    public function backup(): Response
    {
        $dbName = $this->getParameter('database_name');
        $dbUser = $this->getParameter('database_user');
        $dbPassword = $this->getParameter('database_password');
        
        $projectDir = $this->getParameter('kernel.project_dir');
        $backupDir = "$projectDir/db_backup";
        $backupFile = 'backup.sql';
        $backupPath = "$backupDir/$backupFile";

        $backupCommand = [
            'docker',
            'exec',
            'database',
            'mysqldump',
            '-u',
            $dbUser,
            "-p$dbPassword",
            $dbName,
        ];

        $process = new Process($backupCommand);
        $process->setTimeout(300);
        
        try {
            $process->mustRun();
            $output = $process->getOutput();
            file_put_contents($backupPath, $output);
            return new Response("Database backup created at Path: $backupPath", Response::HTTP_OK);
        } catch (ProcessFailedException $exception) {
            return new Response(
                'Failed to create database backup: ' . $exception->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
