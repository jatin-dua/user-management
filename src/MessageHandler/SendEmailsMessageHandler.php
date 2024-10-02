<?php

// src/MessageHandler/SendEmailsMessageHandler.php
namespace App\MessageHandler;
use App\Message\SendEmailsMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AsMessageHandler]
class SendEmailsMessageHandler {
    public function __construct(
        private LoggerInterface $logger
    ) 
    {
    }

    public function __invoke(SendEmailsMessage $message): void 
    {
        $emails = $message->getEmails();
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
                $mailer->send($email);
            } catch(TransportExceptionInterface $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}