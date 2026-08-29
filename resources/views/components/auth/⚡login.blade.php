<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|email')]
    public string $username = '';

    #[Validate('required|string')]
    public string $password = '';

    /** Failed attempts allowed per address+IP before the form locks. */
    private const MAX_ATTEMPTS = 5;

    public function login()
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        // No "remember me": iRedMail's schema has no column for a remember
        // token, so a persistent cookie could never be validated.
        if (! Auth::attempt(['username' => $this->username, 'password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        return $this->redirectIntended(route('dashboard'), navigate: true);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username).'|'.request()->ip());
    }
};
?>

<div class="w-full max-w-sm">
    <div class="mb-8 text-center">
        <h1 class="text-xl font-semibold">{{ config('app.name') }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Masuk dengan alamat email dan kata sandi akun mail Anda.
        </p>
    </div>

    <form wire:submit="login" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label for="username" class="mb-1.5 block text-sm font-medium">Alamat email</label>
            <input
                wire:model="username"
                id="username"
                type="email"
                autocomplete="username"
                autofocus
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-white dark:focus:ring-white"
                placeholder="postmaster@contoh.com"
            >
            @error('username')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium">Kata sandi</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:focus:border-white dark:focus:ring-white"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memeriksa…</span>
        </button>
    </form>
</div>
