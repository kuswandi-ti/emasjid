<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
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
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get the rate limiting throttle key for the request.
     * Based on the transliterated, lowercased email combined with the client IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * Fires a Lockout event and throws a ValidationException if the user has
     * exceeded the maximum number of login attempts (5 attempts per 60 seconds).
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
            'email' => __('Terlalu banyak percobaan login. Coba lagi dalam 1 menit.'),
        ])->status(429);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * On failure, increments the rate limiter and throws a ValidationException.
     * On success, clears the rate limiter.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => __('Email atau password salah.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }
}
