<?php

// src/Message/UserUploadMessage.php
namespace App\Message;

class UserUploadMessage
{
    private string $userDataFile;

    public function __construct(string $userDataFile)
    {
        $this->userDataFile = $userDataFile;
    }

    public function getUserDataFile(): string
    {
        return $this->userDataFile;
    }
}