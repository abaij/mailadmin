<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="h-full bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        @auth
            <div class="min-h-full">
                <header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-3 sm:px-6 lg:px-8">
                        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-semibold">
                            <span class="grid size-7 place-items-center rounded bg-zinc-900 text-sm text-white dark:bg-white dark:text-zinc-900">@</span>
                            {{ config('app.name') }}
                        </a>

                        <nav class="flex items-center gap-1 text-sm">
                            @foreach ([['dashboard', 'Dashboard'], ['domains.index', 'Domain'], ['mailboxes.index', 'Mailbox'], ['aliases.index', 'Alias']] as [$route, $label])
                                <a
                                    href="{{ route($route) }}"
                                    wire:navigate
                                    @class([
                                        'rounded-md px-3 py-1.5 transition',
                                        'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white' => request()->routeIs(str_replace('.index', '.*', $route)),
                                        'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-white' => ! request()->routeIs(str_replace('.index', '.*', $route)),
                                    ])
                                >{{ $label }}</a>
                            @endforeach
                        </nav>

                        <div class="ml-auto flex items-center gap-3 text-sm">
                            <span class="hidden text-zinc-500 sm:inline dark:text-zinc-400">
                                {{ auth()->user()->username }}
                                @if (auth()->user()->isGlobalAdmin())
                                    <span class="ml-1 rounded bg-emerald-100 px-1.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">global admin</span>
                                @endif
                            </span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-md px-3 py-1.5 text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-white">
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        @else
            <main class="grid min-h-full place-items-center px-4 py-12">
                {{ $slot }}
            </main>
        @endauth

        @livewireScripts
    </body>
</html>
