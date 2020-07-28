<h1 align="center">Sanjab Verify</h1>

<p align="center">

[![Latest Stable Version](https://poser.pugx.org/sanjabteam/verify/v/stable)](https://packagist.org/packages/sanjabteam/verify)
[![Total Downloads](https://poser.pugx.org/sanjabteam/verify/downloads)](https://packagist.org/packages/sanjabteam/verify)
[![Build Status](https://travis-ci.com/sanjabteam/verify.svg?branch=master)](https://travis-ci.com/sanjabteam/verify)
[![Code Style](https://github.styleci.io/repos/214197383/shield)](https://github.styleci.io/repos/214197383)
[![Code Coverage](https://codecov.io/gh/sanjabteam/verify/branch/master/graph/badge.svg?sanitize=true)](https://codecov.io/gh/sanjabteam/verify)
[![License](https://poser.pugx.org/sanjabteam/verify/license)](https://packagist.org/packages/sanjabteam/verify)

</p>

Verify your user mobile/email with one time password.

## Installation

You can install the package via composer:

```bash
composer require sanjabteam/verify
```

Publish config file using:

```bash
php artisan vendor:publish --provider=SanjabVerify\VerifyServiceProvider
```

## Configuration
`code`: Unique code generator configs.

`resend_delay` : Resend delay between code sends in seconds.

`expire_in`: Expire sent code after minutes.

`max_attemps`: Max code check attempts.

`max_resends`: Maximum resends in one hour.

* `per_session`: Maximum resends in one hour based on user session. (Limitation: if user clear cookie)
* `per_ip`: Maximum resends in one hour based on user ip. (Limitation: If two different user use one proxy)

## Usage

### Send code to user

```php
use Verify;
use App\Helpers\SmsVerifyMethod;

$result = Verify::request($request->input('mobile'), SmsVerifyMethod::class);

if ($result['success'] == false) { // If user exceed limitation
    return redirect()->back()->with('error', $result['message']); // Show error message
}
```

`App\Helpers\SmsVerifyMethod` is your send method class and you need to create that like this.

```php
namespace App\Helpers;

use SanjabVerify\Contracts\VerifyMethod;

class SmsVerifyMethod implements VerifyMethod
{
    public function send(string $receiver, string $code)
    {
        // Send code to receiver
        if (send_sms($receiver, 'Your code is :'.$code) == 'success') {
            return true; // If code sent successfuly then return true
        }
        return false; // If send code failed return false
    }
}

```

### Verify
You can verify code with request validation.

```php
$request->validate([
    'code' => 'required|sanjab_verify:mobile'
]);
```
> `mobile` is your receiver which in this case is mobile.

You can also verify manually.
```php
use Verify;

if (Verify::verify($request->input('mobile'), $request->input('code')) == false) {
    // Show error
}
```
> Note: You can verify a code just once. so if you need to check code in two different requests then you should use something like session to handle that.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
