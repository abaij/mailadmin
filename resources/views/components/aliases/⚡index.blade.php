<?php

use App\Models\Vmail\Alias;
use App\Models\Vmail\Domain;
use App\Services\AliasService;
use App\Support\Scoping\ManagedDomains;
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

    #[Url(except: '')]
    public string $domain = '';

    /** Address awaiting confirmation in the delete dialog. */
    public ?string $deleting = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'domain'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function aliases()
    {
        return ManagedDomains::scope(Alias::query())
            ->withCount('members')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('address', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            }))
            ->when($this->domain !== '', fn ($query) => $query->where('domain', $this->domain))
            ->orderBy('address')
            ->paginate(25);
    }

    #[Computed]
    public function availableDomains()
    {
        return ManagedDomains::scope(Domain::query())->orderBy('domain')->pluck('domain');
    }

    public function toggleActive(string $address, AliasService $service): void
    {
        $alias = $this->findManageable($address);

        $service->setActive($alias, ! $alias->active);

        unset($this->aliases);
    }

    public function confirmDelete(string $address): void
    {
        $this->deleting = $address;
    }

    public function cancelDelete(): void
    {
        $this->reset('deleting');
    }

    public function delete(AliasService $service): void
    {
        $alias = $this->findManageable((string) $this->deleting);

        $service->delete($alias);

        $this->reset('deleting');
        unset($this->aliases);

        session()->flash('status', "Alias {$alias->address} dihapus.");
    }

    private function findManageable(string $address): Alias
    {
        $alias = Alias::query()->findOrFail($address);

        abort_unless(auth()->user()->canManageDomain($alias->domain), 403);

        return $alias;
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Alias</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Satu alamat yang meneruskan ke beberapa penerima. Alias tidak punya kotak surat sendiri.
            </p>
        </div>

        <a href="{{ route('aliases.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Tambah alias
        </a>
    </div>

    @if (session('status'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Cari alamat atau nama…"
            class="w-full max-w-xs rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:focus:border-white dark:focus:ring-white"
        >

        <select wire:model.live="domain" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none dark:border-zinc-700 dark:bg-zinc-900">
            <option value="">Semua domain</option>
            @foreach ($this->availableDomains as $available)
                <option value="{{ $available }}">{{ $available }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Alamat</th>
                        <th class="px-5 py-3 font-medium">Penerima</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->aliases as $alias)
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('aliases.edit', $alias->address) }}" wire:navigate class="font-medium hover:underline">{{ $alias->address }}</a>
                                @if ($alias->name)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $alias->name }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($alias->members_count === 0)
                                    <span class="rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">
                                        Tanpa penerima — email dibuang
                                    </span>
                                @else
                                    <span class="tabular-nums">{{ $alias->members_count }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => $alias->active,
                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => ! $alias->active,
                                ])>{{ $alias->active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <button wire:click="toggleActive('{{ $alias->address }}')" class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">
                                    {{ $alias->active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                                <a href="{{ route('aliases.edit', $alias->address) }}" wire:navigate class="ml-3 text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">Ubah</a>
                                <button wire:click="confirmDelete('{{ $alias->address }}')" class="ml-3 text-red-600 transition hover:text-red-700 dark:text-red-400">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-zinc-500 dark:text-zinc-400">
                                Belum ada alias.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $this->aliases->links() }}

    @if ($deleting)
        <div class="fixed inset-0 z-50 grid place-items-center bg-zinc-900/50 p-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Hapus alias</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <strong class="font-medium text-zinc-900 dark:text-white">{{ $deleting }}</strong> dan seluruh
                    daftar penerimanya akan dihapus. Tidak ada kotak surat yang terpengaruh.
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="cancelDelete" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                        Batal
                    </button>
                    <button wire:click="delete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        Hapus alias
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
