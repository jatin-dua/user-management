<?php

// src/Message/UploadUsersMessage.php
namespace App\Message;

class UploadUsersMessage
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