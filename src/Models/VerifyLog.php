<?php

namespace SanjabVerify\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $method     method of sending code
 * @property string $reciver    reciver mobile/email
 * @property string $ip         ip of requester
 * @property string $agent      agent of requester
 * @property string $code       code sent
 * @property string $count      count this code checked
 */
class VerifyLog extends Model
{
    protected $table = 'sanjab_verifies';

    protected $fillable = [
        'method',
        'reciver',
        'ip',
        'agent',
        'code',
        'count',
    ];
}
