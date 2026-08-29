<?php

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Models\Vmail\UsedQuota;
use App\Support\Scoping\ManagedDomains;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    /** Accounts above this share of their quota are worth flagging. */
    private const QUOTA_WARNING_RATIO = 0.8;

    #[Computed]
    public function stats(): array
    {
        $domains = ManagedDomains::scope(Domain::query(), 'domain');
        $mailboxes = ManagedDomains::scope(Mailbox::query(), 'domain');

        return [
            'domains' => (clone $domains)->count(),
            'inactiveDomains' => (clone $domains)->where('active', 0)->count(),
            'mailboxes' => (clone $mailboxes)->count(),
            'inactiveMailboxes' => (clone $mailboxes)->where('active', 0)->count(),
            'usedBytes' => (int) ManagedDomains::scope(UsedQuota::query(), 'domain')->sum('bytes'),
            'allottedBytes' => (int) (clone $mailboxes)->sum('quota') * Mailbox::QUOTA_UNIT_BYTES,
        ];
    }

    /** Accounts close to, or over, their allotted quota. */
    #[Computed]
    public function nearingQuota()
    {
        return ManagedDomains::scope(Mailbox::query(), 'domain')
            ->where('quota', '>', 0)
            ->join('used_quota', 'used_quota.username', '=', 'mailbox.username')
            ->select('mailbox.username', 'mailbox.quota', 'used_quota.bytes')
            ->whereRaw('used_quota.bytes >= mailbox.quota * ? * ?', [Mailbox::QUOTA_UNIT_BYTES, self::QUOTA_WARNING_RATIO])
            ->orderByRaw('used_quota.bytes / mailbox.quota DESC')
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function recentlyActive()
    {
        return ManagedDomains::scope(Mailbox::query(), 'domain')
            ->with('lastLogin')
            ->join('last_login', 'last_login.username', '=', 'mailbox.username')
            ->select('mailbox.*')
            ->orderByRaw('GREATEST(COALESCE(last_login.imap, 0), COALESCE(last_login.pop3, 0), COALESCE(last_login.lda, 0)) DESC')
            ->limit(8)
            ->get();
    }

    /** Accounts that have never appeared in `last_login`. */
    #[Computed]
    public function neverLoggedIn()
    {
        return ManagedDomains::scope(Mailbox::query(), 'domain')
            ->whereDoesntHave('lastLogin')
            ->orderBy('username')
            ->get();
    }
};
?>

<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-semibold">Dashboard</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            @if (auth()->user()->isGlobalAdmin())
                Menampilkan seluruh domain di server ini.
            @else
                Menampilkan {{ count(auth()->user()->managedDomains()) }} domain yang Anda kelola.
            @endif
        </p>
    </div>

    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Domain', $this->stats['domains'], $this->stats['inactiveDomains'].' nonaktif'],
            ['Mailbox', $this->stats['mailboxes'], $this->stats['inactiveMailboxes'].' nonaktif'],
            ['Penyimpanan terpakai', Number::fileSize($this->stats['usedBytes'], precision: 1), 'dari '.Number::fileSize($this->stats['allottedBytes'], precision: 1).' dialokasikan'],
            ['Belum pernah login', $this->neverLoggedIn->count(), 'akun tanpa catatan login'],
        ] as [$label, $value, $hint])
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $value }}</dd>
                <dd class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="border-b border-zinc-200 px-5 py-3 text-sm font-semibold dark:border-zinc-800">
                Mendekati batas kuota
            </h2>

            @forelse ($this->nearingQuota as $row)
                @php
                    $allotted = $row->quota * \App\Models\Vmail\Mailbox::QUOTA_UNIT_BYTES;
                    $ratio = min($row->bytes / $allotted, 1);
                @endphp
                <div class="border-b border-zinc-100 px-5 py-3 last:border-0 dark:border-zinc-800/60">
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <a href="{{ route('mailboxes.edit', $row->username) }}" wire:navigate class="truncate font-medium hover:underline">{{ $row->username }}</a>
                        <span class="shrink-0 tabular-nums text-zinc-500 dark:text-zinc-400">
                            {{ Number::fileSize($row->bytes, precision: 1) }} / {{ Number::fileSize($allotted, precision: 0) }}
                        </span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div @class(['h-full rounded-full', 'bg-red-500' => $ratio >= 1, 'bg-amber-500' => $ratio < 1]) style="width: {{ round($ratio * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-zinc-500 dark:text-zinc-400">Tidak ada akun yang melewati 80% kuotanya.</p>
            @endforelse
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="border-b border-zinc-200 px-5 py-3 text-sm font-semibold dark:border-zinc-800">
                Login terakhir
            </h2>

            @forelse ($this->recentlyActive as $mailbox)
                <div class="flex items-baseline justify-between gap-3 border-b border-zinc-100 px-5 py-3 text-sm last:border-0 dark:border-zinc-800/60">
                    <a href="{{ route('mailboxes.edit', $mailbox->username) }}" wire:navigate class="truncate font-medium hover:underline">{{ $mailbox->username }}</a>
                    <span class="shrink-0 text-zinc-500 dark:text-zinc-400">
                        {{ $mailbox->lastLogin?->latest()?->diffForHumans() ?? '—' }}
                    </span>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-zinc-500 dark:text-zinc-400">Belum ada catatan login.</p>
            @endforelse
        </section>
    </div>
</div>
