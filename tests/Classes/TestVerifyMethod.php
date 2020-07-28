<?php

namespace SanjabVerify\Tests\Classes;

use Illuminate\Support\Facades\App;
use SanjabVerify\Contracts\VerifyMethod;

class TestVerifyMethod implements VerifyMethod
{
    public function send(string $reciver, string $code)
    {
        App::instance('sanjab_test', compact('reciver', 'code'));
        return app('sanjab_test_result');
    }
}
