<?php

// src/Service/EmailNotification.php

namespace App\Service;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

class EmailNotification {
    public function __construct(
        private MessageBusInterface $messageBus
    ) {}

    private function sendEmailNotifications(array $emails): void
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