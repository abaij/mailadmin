<?php

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Services\MailboxService;
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

    #[Url(except: '')]
    public string $domain = '';

    #[Url(except: '')]
    public string $status = '';

    /** Address awaiting confirmation in the delete dialog. */
    public ?string $deleting = null;

    /** Typed by the admin to confirm a deletion; must match {@see $deleting}. */
    public string $deleteConfirmation = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'domain', 'status'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function mailboxes()
    {
        return ManagedDomains::scope(Mailbox::query())
            ->with(['usedQuota', 'lastLogin'])
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('username', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            }))
            ->when($this->domain !== '', fn ($query) => $query->where('domain', $this->domain))
            ->when($this->status !== '', fn ($query) => $query->where('active', $this->status === 'active' ? 1 : 0))
            ->orderBy('username')
            ->paginate(25);
    }

    #[Computed]
    public function availableDomains()
    {
        return ManagedDomains::scope(Domain::query())->orderBy('domain')->pluck('domain');
    }

    public function confirmDelete(string $username): void
    {
        $this->deleting = $username;
        $this->deleteConfirmation = '';
    }

    public function cancelDelete(): void
    {
        $this->reset('deleting', 'deleteConfirmation');
    }

    public function delete(MailboxService $service): void
    {
        $mailbox = Mailbox::query()->findOrFail($this->deleting);

        abort_unless(auth()->user()->canManageDomain($mailbox->domain), 403);

        // Typing the address is the only guard against deleting the wrong
        // account -- the mail on disk goes with it.
        $this->validate(
            ['deleteConfirmation' => 'same:deleting'],
            ['deleteConfirmation.same' => 'Ketik alamat email persis untuk mengonfirmasi.'],
        );

        $service->delete($mailbox, auth()->user()->username);

        $this->reset('deleting', 'deleteConfirmation');
        unset($this->mailboxes);

        session()->flash('status', "Mailbox {$mailbox->username} dihapus. Maildir-nya diantrikan untuk dibersihkan.");
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold">Mailbox</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->mailboxes->total() }} akun email.</p>
        </div>

        <a href="{{ route('mailboxes.create') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            Tambah mailbox
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

        <select wire:model.live="status" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm outline-none dark:border-zinc-700 dark:bg-zinc-900">
            <option value="">Semua status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Alamat</th>
                        <th class="px-5 py-3 font-medium">Kuota</th>
                        <th class="px-5 py-3 font-medium">Login terakhir</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->mailboxes as $mailbox)
                        @php
                            $used = $mailbox->usedQuota?->bytes ?? 0;
                            $allotted = $mailbox->quotaBytes();
                            $ratio = $allotted ? min($used / $allotted, 1) : null;
                        @endphp
                        <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800/60">
                            <td class="px-5 py-3">
                                <a href="{{ route('mailboxes.edit', $mailbox->username) }}" wire:navigate class="font-medium hover:underline">{{ $mailbox->username }}</a>
                                @if ($mailbox->name)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $mailbox->name }}</p>
                                @endif
                            </td>
                            <td class="w-48 px-5 py-3">
                                <div class="tabular-nums text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ Number::fileSize($used, precision: 1) }} / {{ $allotted ? Number::fileSize($allotted, precision: 0) : 'tanpa batas' }}
                                </div>
                                @if ($ratio !== null)
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div @class([
                                            'h-full rounded-full',
                                            'bg-red-500' => $ratio >= 1,
                                            'bg-amber-500' => $ratio >= 0.8 && $ratio < 1,
                                            'bg-emerald-500' => $ratio < 0.8,
                                        ]) style="width: {{ max(round($ratio * 100), 2) }}%"></div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400">
                                {{ $mailbox->lastLogin?->latest()?->diffForHumans() ?? 'belum pernah' }}
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' => $mailbox->active,
                                    'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' => ! $mailbox->active,
                                ])>{{ $mailbox->active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('mailboxes.edit', $mailbox->username) }}" wire:navigate class="text-zinc-500 transition hover:text-zinc-900 dark:hover:text-white">Ubah</a>
                                <button wire:click="confirmDelete('{{ $mailbox->username }}')" class="ml-3 text-red-600 transition hover:text-red-700 dark:text-red-400">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-zinc-500 dark:text-zinc-400">
                                Tidak ada mailbox yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $this->mailboxes->links() }}

    @if ($deleting)
        <div class="fixed inset-0 z-50 grid place-items-center bg-zinc-900/50 p-4">
            <div class="w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">Hapus mailbox</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Akun <strong class="font-medium text-zinc-900 dark:text-white">{{ $deleting }}</strong> akan dihapus dan
                    seluruh isinya diantrikan untuk dihapus dari disk oleh cron <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-800">cleanup_maildir</code>.
                    Tindakan ini tidak bisa dibatalkan.
                </p>

                <label class="mt-4 block text-sm font-medium">Ketik alamatnya untuk mengonfirmasi</label>
                <input
                    wire:model="deleteConfirmation"
                    type="text"
                    class="mt-1.5 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 dark:border-zinc-700 dark:bg-zinc-950"
                    placeholder="{{ $deleting }}"
                >
                @error('deleteConfirmation')
                    <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="cancelDelete" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">
                        Batal
                    </button>
                    <button wire:click="delete" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                        Hapus permanen
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
