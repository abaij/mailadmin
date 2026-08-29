<?php

use App\Models\Vmail\Domain;
use App\Services\DomainService;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?Domain $domainRecord = null;

    public string $name = '';

    public string $description = '';

    /** Megabytes; 0 means the domain has no ceiling. */
    public int $maxquota = 0;

    /** Maximum number of mailboxes; 0 means unlimited. */
    public int $mailboxLimit = 0;

    /** Maximum number of aliases; 0 means unlimited. */
    public int $aliasLimit = 0;

    public bool $active = true;

    /**
     * Resolved by route-model binding on the `{domain}` parameter; null on the
     * create route.
     */
    public function mount(?Domain $domain = null): void
    {
        // Only a global admin creates domains; a domain admin can still adjust
        // the settings of a domain granted to them.
        if ($domain === null) {
            abort_unless(auth()->user()->isGlobalAdmin(), 403);

            return;
        }

        abort_unless(auth()->user()->canManageDomain($domain->domain), 403);

        $this->domainRecord = $domain;
        $this->name = $this->domainRecord->domain;
        $this->description = (string) $this->domainRecord->description;
        $this->maxquota = $this->domainRecord->maxquota;
        $this->mailboxLimit = $this->domainRecord->mailboxes;
        $this->aliasLimit = $this->domainRecord->aliases;
        $this->active = $this->domainRecord->active;
    }

    public function isEditing(): bool
    {
        return $this->domainRecord !== null;
    }

    public function save(DomainService $service)
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/i'],
            'description' => ['nullable', 'string', 'max:255'],
            'maxquota' => ['required', 'integer', 'min:0'],
            'mailboxLimit' => ['required', 'integer', 'min:0'],
            'aliasLimit' => ['required', 'integer', 'min:0'],
            'active' => ['boolean'],
        ]);

        $attributes = [
            'description' => $data['description'],
            'maxquota' => $data['maxquota'],
            'mailboxes' => $data['mailboxLimit'],
            'aliases' => $data['aliasLimit'],
            'active' => $data['active'],
        ];

        if ($this->isEditing()) {
            $service->update($this->domainRecord, $attributes);
            session()->flash('status', "Domain {$this->domainRecord->domain} diperbarui.");
        } else {
            $created = $service->create([...$attributes, 'domain' => $data['name']]);
            session()->flash('status', "Domain {$created->domain} dibuat.");
        }

        return $this->redirect(route('domains.index'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <a href="{{ route('domains.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">← Kembali ke daftar</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $this->isEditing() ? $name : 'Tambah domain' }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium">Nama domain</label>
            <input
                wire:model="name"
                id="name"
                type="text"
                @disabled($this->isEditing())
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 disabled:bg-zinc-50 disabled:text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:disabled:bg-zinc-900"
                placeholder="contoh.com"
            >
            @if ($this->isEditing())
                <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Nama domain tidak bisa diubah — setiap alamat dan maildir di bawahnya terikat padanya.
                </p>
            @endif
            @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-1.5 block text-sm font-medium">Deskripsi</label>
            <input wire:model="description" id="description" type="text" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950">
            @error('description') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['maxquota', 'Kuota per mailbox maks. (MB)'],
                ['mailboxLimit', 'Jumlah mailbox maks.'],
                ['aliasLimit', 'Jumlah alias maks.'],
            ] as [$field, $label])
                <div>
                    <label for="{{ $field }}" class="mb-1.5 block text-sm font-medium">{{ $label }}</label>
                    <input wire:model="{{ $field }}" id="{{ $field }}" type="number" min="0" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm tabular-nums outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950">
                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">0 = tanpa batas.</p>
                    @error($field) <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input wire:model="active" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
            Domain aktif
        </label>

        <div class="flex items-center gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ $this->isEditing() ? 'Simpan perubahan' : 'Buat domain' }}
            </button>
            <a href="{{ route('domains.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">Batal</a>
        </div>
    </form>

    @if ($this->isEditing())
        <livewire:domains.alias-domains :domain="$domainRecord" :key="'alias-domains-'.$domainRecord->domain" />
    @endif
</div>
