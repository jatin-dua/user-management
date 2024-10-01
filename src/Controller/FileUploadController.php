<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\UserUploadMessage;
use App\Service\FileUploader;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FileUploadController extends AbstractController
{
    private $messageBus;
    private $entityManager;
    private $passwordHasher;
    private FileUploader $fileUploader;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus, UserPasswordHasherInterface $passwordHasher, FileUploader $fileUploader)
    {
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->fileUploader = $fileUploader;
    }

    #[Route('/api/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {

        // Your code to handle file upload...
    
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file provided');
        }

        [$newFileName, $newFilePath] = $this->fileUploader->upload($uploadedFile);

        $this->messageBus->dispatch(new UserUploadMessage(userDataFile: $newFilePath));

        return new Response('User data is being processed asynchronously.', Response::HTTP_ACCEPTED);
    }
}
