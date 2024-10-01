<?php

// src/MessageHandler/UserUploadMessageHandler.php
namespace App\MessageHandler;

use App\Entity\User;
use App\Message\UserUploadMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

// TODO: Refactor Email sending function to its place
// TODO: Implement Batch Processing of Data
// TODO: Implement Error handling while data upload (like for duplicate data)
#[AsMessageHandler]
class UserUploadMessageHandler 
{
    public function __construct(private EntityManagerInterface $entityManager, 
        private UserPasswordHasherInterface $passwordHasher,
        private MessageBusInterface $messageBus
        ) {}

    public function __invoke(UserUploadMessage $message)
    {
        $userDataFile = $message->getUserDataFile();

        $handle = fopen($userDataFile, 'r');
        if ($handle === false) {
            die('Cannot open the file ' . $userDataFile);
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
        // close the file
        fclose($handle);

        // Flush to save all the users
        $this->entityManager->flush();

        // $this->sendEmailNotifications($emails);
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
