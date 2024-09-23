<?php

namespace App\Controller;

use App\Entity\User;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FileUploadController extends AbstractController
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
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

        // TODO: Send Email Notifications
        // TODO: Validate User
        // TODO: Check if User already exists in DB

        return new JsonResponse([
            'message' => 'File uploaded successfully',
            'filename' => $filename,
            'file_type' => $fileType
        ]);
    }
}
