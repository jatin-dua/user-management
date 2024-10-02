<?php

// src/Message/SendEmailsMessage.php
namespace App\Message;

class SendEmailsMessage
{
    public function __construct(private array $emails)
    {
    }

    public function getEmails(): array
    {
        return $this->emails;
    }
}