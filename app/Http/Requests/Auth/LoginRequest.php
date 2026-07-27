<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginHistory;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = $this->string('email');
        $context = AuditLogger::requestContext();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            LoginHistory::create([
                'user_id' => null,
                'email' => $email,
                'failed' => true,
                'failure_reason' => 'invalid_credentials',
                'login_at' => now(),
                'ip_address' => $context['ip_address'],
                'browser' => $context['browser'],
                'device' => $context['device'],
                'user_agent' => $context['user_agent'],
            ]);

            AuditLogger::log('Failed Logins', null, [
                'email' => $email,
                'failure_reason' => 'invalid_credentials',
            ], 'Auth');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();
        if (! $user->isActive()) {
            LoginHistory::create([
                'user_id' => $user?->id,
                'email' => $email,
                'failed' => true,
                'failure_reason' => 'account_disabled',
                'login_at' => now(),
                'ip_address' => $context['ip_address'],
                'browser' => $context['browser'],
                'device' => $context['device'],
                'user_agent' => $context['user_agent'],
            ]);

            AuditLogger::log('Failed Logins', null, [
                'email' => $email,
                'failure_reason' => 'account_disabled',
            ], 'Auth');

            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been disabled.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $loginHistory = LoginHistory::create([
            'user_id' => $user->id,
            'email' => $email,
            'failed' => false,
            'login_at' => now(),
            'ip_address' => $context['ip_address'],
            'browser' => $context['browser'],
            'device' => $context['device'],
            'user_agent' => $context['user_agent'],
        ]);

        $this->session()->put('login_history_id', $loginHistory->id);

        AuditLogger::log('Login', null, [
            'email' => $email,
        ], 'Auth');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
