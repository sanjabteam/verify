<?php

namespace SanjabVerify;

use Exception;
use Illuminate\Support\Facades\Session;
use SanjabVerify\Contracts\VerifyMethod;
use SanjabVerify\Models\VerifyLog;

class Verify
{
    /**
     * Request a new code.
     *
     * @property string $receiver    receiver of code
     * @property string $method     method of sending code
     * @return array
     * @example ['success' => true, 'message' => '...']
     */
    public function request(string $receiver, string $method = null)
    {
        VerifyLog::where('created_at', '<', now()->subDay())->delete();
        $lastestLog = VerifyLog::where('ip', request()->ip())->orWhere('receiver', $receiver)->latest()->first();
        if ($lastestLog) {
            if ($lastestLog->created_at->gt(now()->subSeconds(config('verify.resend_delay')))) {
                return [
                    'success' => false,
                    'message' => trans('verify::verify.resend_wait', [
                        'seconds' => config('verify.resend_delay') - $lastestLog->created_at->diffInSeconds()
                    ]),
                    'seconds' => config('verify.resend_delay') - $lastestLog->created_at->diffInSeconds()
                ];
            }
            if (VerifyLog::where('created_at', '>', now()->subHour())
                ->where(function ($query) use ($receiver) {
                    $query->where('ip', request()->ip())->orWhere('receiver', $receiver);
                })->count() > config('verify.max_resends.per_ip') ||
                (is_array(session('sanjab_verify')) && count(array_filter(session('sanjab_verify'), function ($time) {
                    return $time > time() - 3600;
                })) > config('verify.max_resends.per_session'))
            ) {
                return ['success' => false, 'message' => trans('verify::verify.too_many_requests')];
            }
        }
        $verifyMethod = new $method;
        if (!($verifyMethod instanceof VerifyMethod)) {
            throw new Exception('Verify method is not instance of SanjabVerify\Contracts\VerifyMethod.');
        }
        $code = $this->generate();
        if ($verifyMethod->send($receiver, $code)) {
            VerifyLog::create([
                'ip'       => request()->ip(),
                'agent'    => request()->userAgent(),
                'receiver' => $receiver,
                'code'     => $code,
                'method'   => $method,
            ]);
            Session::push('sanjab_verify', time());
            return ['success' => true, 'message' => trans('verify::verify.sent_successfully')];
        }
        return ['success' => false, 'message' => trans('verify::verify.send_failed')];
    }

    /**
     * Request a new code.
     *
     * @property string $receiver    receiver of code
     * @property string $code       code input value
     * @return array
     * @example ['success' => true, 'message' => '...']
     */
    public function verify(string $receiver, string $code)
    {
        $log = VerifyLog::where('receiver', $receiver)->latest()->first();
        if ($log == null || $log->created_at->diffInMinutes() > config('verify.expire_in')) {
            return [
                'success' => false,
                'message' => trans('verify::verify.code_expired'),
            ];
        }
        if ($log->count > config('verify.max_attemps')) {
            return [
                'success' => false,
                'message' => trans('verify::verify.code_attempt_limited', ['count' => config('verify.max_attemps')]),
            ];
        }
        if ($log->ip != request()->ip() || $log->agent != request()->userAgent()) {
            return [
                'success' => false,
                'message' => trans('verify::verify.code_is_not_yours'),
            ];
        }

        $log->increment('count');

        if ($log->code != $code && (config('verify.code.case_sensitive') == false && strtolower($log->code) != strtolower($code))) {
            return [
                'success' => false,
                'message' => trans('verify::verify.code_is_wrong'),
            ];
        }

        $log->update(['count' => 2147483647]);
        $log->save();

        return ['success' => true, 'message' => trans('verify::verify.verified_successfully')];
    }

    /**
     * Generate code.
     *
     * @return string
     */
    public function generate()
    {
        $string = '';
        while (strlen($string) < config('verify.code.length')) {
            if (config('verify.code.numbers') && strlen($string) < config('verify.code.length')) {
                $string .= str_shuffle('1234567890')[0];
            }
            if (config('verify.code.lower_case') && strlen($string) < config('verify.code.length')) {
                $string .= str_shuffle('abcdefghijklmnopqrstuvwxyz')[0];
            }
            if (config('verify.code.upper_case') && strlen($string) < config('verify.code.length')) {
                $string .= str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ')[0];
            }
            if (config('verify.code.symbols') && strlen($string) < config('verify.code.length')) {
                $string .= str_shuffle('!@#$%^&*')[0];
            }
        }
        if (empty($string)) {
            throw new Exception("Generated code is empty because of wrong configuaration.");
        }
        return str_shuffle($string);
    }
}
