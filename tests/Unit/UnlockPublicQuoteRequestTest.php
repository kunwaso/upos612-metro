<?php

namespace Tests\Unit;

use App\Http\Requests\UnlockPublicQuoteRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class UnlockPublicQuoteRequestTest extends TestCase
{
    protected function callPrepareForValidation(UnlockPublicQuoteRequest $request): void
    {
        $method = new ReflectionMethod(UnlockPublicQuoteRequest::class, 'prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);
    }

    public function test_password_mode_does_not_merge_code_fields()
    {
        config(['product.public_quote_unlock.input_mode' => 'password']);

        $request = UnlockPublicQuoteRequest::create('https://example.test/q/x/unlock', 'POST', [
            'password' => 'secret',
            'code_1' => '1',
            'code_2' => '2',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $this->callPrepareForValidation($request);

        $this->assertSame('secret', $request->input('password'));
    }

    public function test_otp_mode_merges_code_fields_into_password()
    {
        config(['product.public_quote_unlock.input_mode' => 'otp']);
        config(['product.public_quote_unlock.otp_length' => 4]);

        $request = UnlockPublicQuoteRequest::create('https://example.test/q/x/unlock', 'POST', [
            'code_1' => '9',
            'code_2' => '8',
            'code_3' => '7',
            'code_4' => '6',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $this->callPrepareForValidation($request);

        $this->assertSame('9876', $request->input('password'));
    }

    public function test_otp_digit_mode_rejects_non_numeric_password()
    {
        config(['product.public_quote_unlock.input_mode' => 'otp']);
        config(['product.public_quote_unlock.otp_length' => 3]);
        config(['product.public_quote_unlock.otp_digits_only' => true]);

        $request = UnlockPublicQuoteRequest::create('https://example.test/q/x/unlock', 'POST', [
            'code_1' => '1',
            'code_2' => '2',
            'code_3' => 'a',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $this->callPrepareForValidation($request);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('password'));
    }

    public function test_otp_alphanumeric_mode_accepts_letters_and_digits()
    {
        config(['product.public_quote_unlock.input_mode' => 'otp']);
        config(['product.public_quote_unlock.otp_length' => 3]);
        config(['product.public_quote_unlock.otp_digits_only' => false]);

        $request = UnlockPublicQuoteRequest::create('https://example.test/q/x/unlock', 'POST', [
            'code_1' => 'a',
            'code_2' => '9',
            'code_3' => 'Z',
        ]);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));

        $this->callPrepareForValidation($request);

        $validator = Validator::make($request->all(), $request->rules());
        $this->assertTrue($validator->passes());
    }
}
