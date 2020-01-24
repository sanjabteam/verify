<?php

namespace SanjabVerify\Contracts;

interface VerifyMethod
{
    /**
     * Send verify code to reciver.
     *
     * @param string $reciver
     * @param string $code
     * @return bool
     */
    public function send(string $reciver, string $code);
}
