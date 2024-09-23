<?php

namespace App\Controller;

use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class FileUploadController extends AbstractController
{
    private $messageBus;
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
        $this->entityManager = $entityManager;
    }

    #[Route('/api/upload', name: 'app_file_upload', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $uploadedFile = $request->files->get('file');
        $fileType = $request->request->get('file_type');

        if (!$uploadedFile) {
            throw new BadRequestHttpException('No file provided');
        }

        // Validate the file type (you can also define a list of allowed types)
        $allowedTypes = ['text/csv']; // Add your allowed types here
        if (!in_array($fileType, $allowedTypes)) {
            throw new BadRequestHttpException('Invalid file type');
        }

        // Define the directory where you want to store the uploaded file
        $uploadDirectory = $this->getParameter('upload_directory');

        $filename = pathinfo($uploadedFile->getClientOriginalName(), flags: PATHINFO_FILENAME).'.csv';

        // Move the uploaded file
        $uploadedFile->move($uploadDirectory, $filename);
    

        $filepath = "$uploadDirectory/$filename";
        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            die('Cannot open the file ' . $filename);
        }

        $emails = [];
        $headers = fgetcsv($handle);
        // read each line in CSV file at a time
        while (($row = fgetcsv($handle)) !== false) {
            [$name, $email, $username, $address, $role] = $row;
            $role = $role === "USER" ? "ROLE_USER" : "ROLE_ADMIN";

            $emails[] = $email;

            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setUsername($username);
            $user->setAddress($address);
            $user->setRoles([$role]);
            $user->setPassword('password');

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
            'filename' => $filename,
            'file_type' => $fileType
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
