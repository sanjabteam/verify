<?php

namespace SanjabVerify;

class VerificationResponse
{
    public $success;
    public $message;
    public $seconds;

    public function __construct(bool $success, string $message, int $seconds = 0)
    {
        $this->success = $success;
        $this->message = $message;
        $this->seconds = $seconds;
    }
}
