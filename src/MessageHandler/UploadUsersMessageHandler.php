<?php

// src/MessageHandler/UploadUsersMessageHandler.php
namespace App\MessageHandler;

use App\Entity\User;
use App\Message\UploadUsersMessage;
use App\Message\SendEmailsMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

// TODO: Implement Error handling while data upload (like for duplicate data)
#[AsMessageHandler]
class UploadUsersMessageHandler 
{
    public function __construct(private EntityManagerInterface $entityManager, 
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus
        ) {}

    public function __invoke(UploadUsersMessage $message)
    {
        $userDataFile = $message->getUserDataFile();

        $handle = fopen($userDataFile, 'r');
        if ($handle === false) {
            die('Cannot open the file ' . $userDataFile);
        }

        $emails = [];
        $headers = fgetcsv($handle);

        $password = $this->passwordHasher->hashPassword(new User(), 'password');

        $batchSize = 10;
        $batchNo = 1;
        $i = 0;
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

            if (($i % $batchSize) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $this->logger->info("Processed Batch $batchNo at " . microtime(true));
                $this->messageBus->dispatch(new SendEmailsMessage($emails));
                $emails = [];
                $batchNo++;
                sleep(4);
            }
            $i++;
        }
        // close the file
        fclose($handle);

        $this->entityManager->flush();  // Final flush for any remaining records
        $this->entityManager->clear();  // Clear after the final flush
        $this->messageBus->dispatch(new SendEmailsMessage($emails));
    }
}
