<?php

use App\Models\Vmail\AliasDomain;
use App\Models\Vmail\Domain;
use App\Services\AliasDomainService;
use App\Support\IRedMail\AddressRegistry;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Domain $domain;

    public string $newAliasDomain = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canManageDomain($this->domain->domain), 403);
    }

    #[Computed]
    public function aliasDomains()
    {
        return AliasDomain::query()
            ->where('target_domain', $this->domain->domain)
            ->orderBy('alias_domain')
            ->get();
    }

    public function add(AliasDomainService $service, AddressRegistry $registry): void
    {
        abort_unless(auth()->user()->canManageDomain($this->domain->domain), 403);

        $this->validate([
            'newAliasDomain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/i'],
        ], [
            'newAliasDomain.regex' => 'Masukkan nama domain yang valid.',
        ]);

        try {
            $service->create($this->domain, $this->newAliasDomain);
        } catch (\RuntimeException $exception) {
            $this->addError('newAliasDomain', $exception->getMessage());

            return;
        }

        $this->reset('newAliasDomain');
        unset($this->aliasDomains);
    }

    public function remove(string $aliasDomain, AliasDomainService $service): void
    {
        $service->delete($this->findOwned($aliasDomain));

        unset($this->aliasDomains);
    }

    public function toggleActive(string $aliasDomain, AliasDomainService $service): void
    {
        $record = $this->findOwned($aliasDomain);

        $service->setActive($record, ! $record->active);

        unset($this->aliasDomains);
    }

    /**
     * Looks the row up *within this domain*, so a forged parameter cannot reach
     * an alias domain belonging to someone else.
     */
    private function findOwned(string $aliasDomain): AliasDomain
    {
        abort_unless(auth()->user()->canManageDomain($this->domain->domain), 403);

        return AliasDomain::query()
            ->where('target_domain', $this->domain->domain)
            ->whereKey($aliasDomain)
            ->firstOrFail();
    }
};
?>

<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
    <h2 class="text-sm font-semibold">Domain alias</h2>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
        Email ke <code class="text-xs">nama@domain-alias</code> dikirim ke <code class="text-xs">{{ 'nama@'.$domain->domain }}</code>.
        Tidak ada kotak surat yang dibuat di domain alias.
    </p>

    @if ($this->aliasDomains->isNotEmpty())
        <ul class="mt-4 divide-y divide-zinc-100 border-y border-zinc-100 dark:divide-zinc-800/60 dark:border-zinc-800/60">
            @foreach ($this->aliasDomains as $aliasDomain)
                <li class="flex items-center justify-between gap-3 py-3 text-sm">
                    <span class="font-medium">{{ $aliasDomain->alias_domain }}</span>
                    <span class="ml-auto">
                        <span @class([
                            'rounded px-2 py-0.5 text-xs font-medium',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => $aliasDomain->active,
                            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => ! $aliasDomain->active,
                        ])>{{ $aliasDomain->active ? 'Aktif' : 'Nonaktif' }}</span>
                    </span>
                    <button wire:click="toggleActive('{{ $aliasDomain->alias_domain }}')" class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">
                        {{ $aliasDomain->active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button
                        wire:click="remove('{{ $aliasDomain->alias_domain }}')"
                        wire:confirm="Hapus domain alias {{ $aliasDomain->alias_domain }}? Email ke domain ini akan berhenti diterima."
                        class="text-red-600 transition hover:text-red-700 dark:text-red-400"
                    >Hapus</button>
                </li>
            @endforeach
        </ul>
    @endif

    <form wire:submit="add" class="mt-4 flex flex-wrap items-start gap-2">
        <div class="min-w-0 flex-1">
            <label for="new-alias-domain" class="sr-only">Domain alias baru</label>
            <input
                wire:model="newAliasDomain"
                id="new-alias-domain"
                type="text"
                placeholder="domain-lain.com"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950"
            >
            @error('newAliasDomain') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50 disabled:opacity-60 dark:border-zinc-700 dark:hover:bg-zinc-800">
            Tambah
        </button>
    </form>
</div>
