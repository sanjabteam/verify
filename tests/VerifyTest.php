<?php

namespace SanjabVerify\Tests;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;
use SanjabVerify\Models\VerifyLog;
use SanjabVerify\Support\Facades\Verify;
use SanjabVerify\Tests\Classes\TestVerifyMethod;

class VerifyTest extends TestCase
{
    public function testSend()
    {
        App::instance('sanjab_test_result', false);
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.send_failed'));

        App::instance('sanjab_test_result', true);
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);

        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.resend_wait', ['seconds' => 120]));
        VerifyLog::query()->update(['created_at' => now()->subMinutes(3)]);

        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);

        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.resend_wait', ['seconds' => 120]));
        VerifyLog::query()->update(['created_at' => now()->subMinutes(3)]);

        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.too_many_requests'));

        Session::flush();
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);
        VerifyLog::query()->update(['created_at' => now()->subMinutes(3)]);

        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.too_many_requests'));
    }

    public function testVerify()
    {
        App::instance('sanjab_test_result', true);
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);

        $this->assertTrue(Validator::make(
            ['code' => rand(100, 999), 'reciver' => '1234567890'],
            ['code' => 'required|sanjab_verify:reciver']
        )->fails());

        $this->assertFalse(Validator::make(
            ['code' => app('sanjab_test')['code'], 'reciver' => '1234567890'],
            ['code' => 'required|sanjab_verify:reciver']
        )->fails());

        $this->assertTrue(Validator::make(
            ['code' => app('sanjab_test')['code'], 'reciver' => '1234567890'],
            ['code' => 'required|sanjab_verify:reciver']
        )->fails());

        VerifyLog::query()->delete();
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);

        foreach (range(0, 2) as $range) {
            $result = Verify::verify('1234567890', rand(100, 900));
            $this->assertFalse($result['success']);
            $this->assertEquals($result['message'], trans('verify::verify.code_is_wrong'));
        }

        $result = Verify::verify('1234567890', rand(100, 900));
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.code_attempt_limited', ['count' => config('verify.max_attemps')]));

        VerifyLog::query()->delete();
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);
        VerifyLog::latest()->update(['created_at' => now()->subHour(1)]);

        $result = Verify::verify('1234567890', app('sanjab_test')['code']);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.code_expired'));

        VerifyLog::query()->delete();
        $result = Verify::request('1234567890', TestVerifyMethod::class);
        $this->assertTrue($result['success']);
        request()->server->set('REMOTE_ADDR', '1.2.3.4');

        $result = Verify::verify('1234567890', app('sanjab_test')['code']);
        $this->assertFalse($result['success']);
        $this->assertEquals($result['message'], trans('verify::verify.code_is_not_yours'));
    }

    public function testInvalidVerifyMethod()
    {
        $this->expectException(Exception::class);
        $result = Verify::request('1234567890', Session::class);
    }

    public function testInvalidConfig()
    {
        Config::set('verify.code.length', 0);
        $this->expectException(Exception::class);
        Verify::request('1234567890', TestVerifyMethod::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('verify.max_attemps', 2);
        $app['config']->set('verify.max_resends.per_session', 1);
        $app['config']->set('verify.max_resends.per_ip', 2);

        $app['config']->set(
            'verify.code',
            [
                'length' => 6,
                'case_sensitive' => false,
                'numbers' => true,
                'upper_case' => true,
                'lower_case' => true,
                'symbols' => true,
            ]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
    }

    protected function getPackageAliases($app)
    {
        return [
            'Verify' => \SanjabVerify\Support\Facades\Verify::class
        ];
    }

    protected function getPackageProviders($app)
    {
        return [\SanjabVerify\VerifyServiceProvider::class];
    }
}
