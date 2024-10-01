<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\FileUploader;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
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
    public function upload(Request $request): JsonResponse
    {

        // Your code to handle file upload...
    
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file provided');
        }

        [$newFileName, $newFilePath] = $this->fileUploader->upload($uploadedFile);

        $handle = fopen($newFilePath, 'r');
        if ($handle === false) {
            die('Cannot open the file ' . $newFileName);
        }

        $emails = [];
        $headers = fgetcsv($handle);

        $password = $this->passwordHasher->hashPassword(new User(), 'password');
        while (($row = fgetcsv($handle)) !== false) {
            [$name, $email, $username, $address, $role] = $row;
            $roles = ['ROLE_USER'];
            if ($role == 'ADMIN') {
                $roles[] = 'ROLE_ADMIN';
            }

            $emails[] = $email;

            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setAddress($address);
            $user->setRoles($roles);
            $user->setPassword($password);

            // Persist the user entity
            $this->entityManager->persist($user);
        }
        // Flush to save all the users
        $this->entityManager->flush();
        
        // close the file
        fclose($handle);

        $this->sendEmailNotifications($emails);

        return new JsonResponse([
            'message' => 'File uploaded successfully',
            'filename' => $newFileName,
        ]);
    }

    private function sendEmailNotifications(array $emails)
    {
        // Get the DSN from environment variables
        $dsn = $_ENV['MAILER_DSN_URL'];
        // Create the transport
        $transport = Transport::fromDsn($dsn);
        // Create the Mailer
        $mailer = new Mailer($transport);
        $username = $_ENV['MAILER_USER_EMAIL'];

        foreach ($emails as $emailAddress) {
            $email = (new Email())
                ->from($username)
                ->to($emailAddress)
                ->subject('Data Upload Notification')
                ->text('Data has been uploaded and saved to the database.');
    
            // Send the email
            try {
                $this->messageBus->dispatch(message: new SendEmailMessage($email));
            } catch(TransportExceptionInterface $e) {
                // Log or handle the email send error
            }
        }
    }
}
