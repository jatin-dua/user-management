<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BackupController extends AbstractController
{
    #[Route('/api/backup', name: 'app_backup', methods: ['GET'])]
    public function backup(): Response
    {
        return $this->render('backup/index.html.twig', [
            'controller_name' => 'BackupController',
        ]);
    }
}
