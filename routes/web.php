<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', 'auth.login')->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'mail-admin'])->group(function (): void {
    Route::redirect('/', '/dashboard');

    Route::livewire('/dashboard', 'dashboard')->name('dashboard');

    Route::livewire('/domains', 'domains.index')->name('domains.index');
    Route::livewire('/domains/create', 'domains.form')->name('domains.create');
    Route::livewire('/domains/{domain}/edit', 'domains.form')->name('domains.edit');

    Route::livewire('/aliases', 'aliases.index')->name('aliases.index');
    Route::livewire('/aliases/create', 'aliases.form')->name('aliases.create');
    Route::livewire('/aliases/{alias}/edit', 'aliases.form')->name('aliases.edit');

    Route::livewire('/mailboxes', 'mailboxes.index')->name('mailboxes.index');
    Route::livewire('/mailboxes/create', 'mailboxes.form')->name('mailboxes.create');
    Route::livewire('/mailboxes/{mailbox}/edit', 'mailboxes.form')->name('mailboxes.edit');
});
