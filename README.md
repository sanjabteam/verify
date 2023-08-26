<h1 align="center">Sanjab Verify</h1>

<div align="center">

[![Latest Stable Version](https://poser.pugx.org/sanjabteam/verify/v/stable)](https://packagist.org/packages/sanjabteam/verify)
[![Total Downloads](https://poser.pugx.org/sanjabteam/verify/downloads)](https://packagist.org/packages/sanjabteam/verify)
[![Build Status](https://github.com/sanjabteam/verify/workflows/tests/badge.svg)](https://github.com/sanjabteam/verify/actions)
[![Code Style](https://github.styleci.io/repos/214197383/shield?style=flat)](https://github.styleci.io/repos/214197383)
[![Code Coverage](https://codecov.io/gh/sanjabteam/verify/branch/master/graph/badge.svg?sanitize=true)](https://codecov.io/gh/sanjabteam/verify)
[![License](https://poser.pugx.org/sanjabteam/verify/license)](https://packagist.org/packages/sanjabteam/verify)

</div>

Verify your user mobile/email with a one-time password.

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

* `per_session`: Maximum resends in one hour based on the user session. (Limitation: if user clear cookie)
* `per_ip`: Maximum resends in one hour based on user IP. (Limitation: If two different user use one proxy)

## Usage

### Send code to the user

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

You can also verify it manually.
```php
use Verify;

$result = Verify::verify($request->input('mobile'), $request->input('code'));
if ($result['success'] == false) {
    // Show error $result['message']
}
```
> Note: You can verify a code just once. so if you need to check code in two different requests then you should use something like the session to handle that.

## Contributing

Contributions are welcome!

* Fork the Project
* Clone your project (git clone https://github.com/your_username/verify.git)
* Create new branch (git checkout -b your_feature)
* Commit your Changes (git commit -m 'new feature')
* Push to the Branch (git push origin your_feature)
* Open a Pull Request

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
