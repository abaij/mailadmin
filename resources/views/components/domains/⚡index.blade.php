<?php

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Services\DomainService;
use App\Support\Scoping\ManagedDomains;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function domains()
    {
        return ManagedDomains::scope(Domain::query())
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('domain', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            }))
            ->withCount('mailboxes')
            ->orderBy('domain')
            ->paginate(20);
    }

    /** Bytes consumed per domain, so the list can show real usage. */
    #[Computed]
    public function usageByDomain(): array
    {
        return ManagedDomains::scope(\App\Models\Vmail\UsedQuota::query())
            ->selectRaw('domain, SUM(bytes) AS bytes')
            ->groupBy('domain')
            ->pluck('bytes', 'domain')
            ->all();
    }

    public function toggleActive(string $domain, DomainService $service): void
    {
        $record = $this->findManageable($domain);

        $service->setActive($record, ! $record->active);

        unset($this->domains);

        $this->dispatch('toast', message: $record->active
            ? "Domain {$domain} diaktifkan."
            : "Domain {$domain} dinonaktifkan. Email masuk akan ditolak.");
    }

    private function findManageable(string $domain): Domain
    {
        abort_unless(auth()->user()->canManageDomain($domain), 403);

        return Domain::query()->findOrFail($domain);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Domain</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Domain mail yang dilayani server ini.</p>
        </div>

        @if (auth()->user()->isGlobalAdmin())
            <a href="{{ route('domains.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                Tambah domain
            </a>
        @endif
    </div>

    <input
        wire:model.live.debounce.300ms="search"
        type="search"
        placeholder="Cari domain atau deskripsi…"
        class="w-full max-w-sm rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:focus:border-white dark:focus:ring-white"
    >

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Domain</th>
                        <th class="px-5 py-3 font-medium">Mailbox</th>
                        <th class="px-5 py-3 font-medium">Terpakai</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->domains as $domain)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('domains.edit', $domain->domain) }}" wire:navigate class="font-medium hover:underline">{{ $domain->domain }}</a>
                                @if ($domain->description)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $domain->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 tabular-nums">
                                {{ $domain->mailboxes_count }}{{ $domain->hasMailboxLimit() ? ' / '.$domain->mailboxes : '' }}
                            </td>
                            <td class="px-5 py-3 tabular-nums text-zinc-500 dark:text-zinc-400">
                                {{ Number::fileSize($this->usageByDomain[$domain->domain] ?? 0, precision: 1) }}
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => $domain->active,
                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => ! $domain->active,
                                ])>{{ $domain->active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    wire:click="toggleActive('{{ $domain->domain }}')"
                                    wire:confirm="{{ $domain->active ? 'Nonaktifkan '.$domain->domain.'? Email masuk ke domain ini akan ditolak.' : 'Aktifkan kembali '.$domain->domain.'?' }}"
                                    class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white"
                                >{{ $domain->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-zinc-500 dark:text-zinc-400">
                                Tidak ada domain yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $this->domains->links() }}
</div>
