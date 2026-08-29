<?php

use App\Models\Vmail\Domain;
use App\Models\Vmail\Mailbox;
use App\Services\MailboxService;
use App\Support\Scoping\ManagedDomains;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?Mailbox $mailbox = null;

    public string $localPart = '';

    public string $domain = '';

    public string $name = '';

    public int $quota = 1024;

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $active = true;

    /** Coarse per-protocol switches, expanded to iRedMail's columns on save. */
    public array $services = ['smtp' => true, 'imap' => true, 'pop3' => true, 'sieve' => true, 'sogo' => true];

    /**
     * Resolved by route-model binding on the `{mailbox}` parameter; null on
     * the create route.
     */
    public function mount(?Mailbox $mailbox = null): void
    {
        if ($mailbox === null) {
            $this->domain = (string) $this->availableDomains->first();

            return;
        }

        abort_unless(auth()->user()->canManageDomain($mailbox->domain), 403);

        $this->mailbox = $mailbox;
        $this->localPart = $this->mailbox->localPart();
        $this->domain = $this->mailbox->domain;
        $this->name = $this->mailbox->name;
        $this->quota = $this->mailbox->quota;
        $this->active = $this->mailbox->active;

        foreach (Mailbox::SERVICE_GROUPS as $group => $columns) {
            $this->services[$group] = (bool) $this->mailbox->{$columns[0]};
        }
    }

    public function isEditing(): bool
    {
        return $this->mailbox !== null;
    }

    #[Computed]
    public function availableDomains()
    {
        return ManagedDomains::scope(Domain::query())->where('active', 1)->orderBy('domain')->pluck('domain');
    }

    public function save(MailboxService $service)
    {
        $data = $this->validate($this->rules());

        abort_unless(auth()->user()->canManageDomain($data['domain']), 403);

        if ($this->isEditing()) {
            $service->update($this->mailbox, [
                'name' => $data['name'],
                'quota' => $data['quota'],
                'active' => $data['active'],
                ...$this->serviceColumns(),
            ]);

            if ($this->password !== '') {
                $service->changePassword($this->mailbox, $this->password);
            }

            session()->flash('status', "Mailbox {$this->mailbox->username} diperbarui.");
        } else {
            $created = $service->create([
                'username' => $data['localPart'].'@'.$data['domain'],
                'password' => $this->password,
                'name' => $data['name'],
                'quota' => $data['quota'],
                'active' => $data['active'],
                ...$this->serviceColumns(),
            ]);

            session()->flash('status', "Mailbox {$created->username} dibuat.");
        }

        return $this->redirect(route('mailboxes.index'), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            // Postfix accepts far more, but iRedMail's own admin restricts new
            // addresses to this set and the maildir hash assumes it.
            'localPart' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9]([a-z0-9._-]*[a-z0-9])?$/'],
            'domain' => ['required', 'string', Rule::in($this->availableDomains)],
            'name' => ['nullable', 'string', 'max:255'],
            'quota' => ['required', 'integer', 'min:0', 'max:10485760'],
            'active' => ['boolean'],
            'password' => [
                $this->isEditing() ? 'nullable' : 'required',
                'string',
                'min:10',
                'same:passwordConfirmation',
            ],
        ];
    }

    /**
     * Expands the five coarse toggles into the individual iRedMail columns.
     *
     * @return array<string, int>
     */
    private function serviceColumns(): array
    {
        $columns = [];

        foreach (Mailbox::SERVICE_GROUPS as $group => $names) {
            foreach ($names as $name) {
                // The SOGo columns are char(1) 'y'/'n' rather than tinyint.
                $columns[$name] = str_starts_with($name, 'enablesogo') && $name !== 'enablesogo'
                    ? ($this->services[$group] ? 'y' : 'n')
                    : (int) $this->services[$group];
            }
        }

        return $columns;
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <a href="{{ route('mailboxes.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">← Kembali ke daftar</a>
        <h1 class="mt-2 text-2xl font-semibold">
            {{ $this->isEditing() ? $mailbox->username : 'Tambah mailbox' }}
        </h1>
        @if ($this->isEditing())
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Dibuat {{ $mailbox->createdAt()?->translatedFormat('j F Y') ?? 'tidak diketahui' }} ·
                terpakai {{ Number::fileSize($mailbox->usedQuota?->bytes ?? 0, precision: 1) }} ·
                <code class="text-xs">{{ $mailbox->maildir }}</code>
            </p>
        @endif
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label class="mb-1.5 block text-sm font-medium">Alamat email</label>
            <div class="flex gap-2">
                <input
                    wire:model="localPart"
                    type="text"
                    @disabled($this->isEditing())
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 disabled:bg-zinc-50 disabled:text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:disabled:bg-zinc-900"
                    placeholder="nama.pengguna"
                >
                <select
                    wire:model="domain"
                    @disabled($this->isEditing())
                    class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none disabled:bg-zinc-50 disabled:text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:disabled:bg-zinc-900"
                >
                    @foreach ($this->availableDomains as $available)
                        <option value="{{ $available }}">{{ '@'.$available }}</option>
                    @endforeach
                </select>
            </div>
            @if ($this->isEditing())
                <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                    Alamat tidak bisa diubah — maildir yang sudah ada di disk terikat pada alamat ini.
                </p>
            @endif
            @error('localPart') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            @error('domain') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium">Nama tampilan</label>
            <input wire:model="name" id="name" type="text" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950">
            @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="quota" class="mb-1.5 block text-sm font-medium">Kuota (MB)</label>
            <input wire:model="quota" id="quota" type="number" min="0" class="w-40 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm tabular-nums outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950">
            <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">0 berarti tanpa batas.</p>
            @error('quota') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium">
                    {{ $this->isEditing() ? 'Kata sandi baru' : 'Kata sandi' }}
                </label>
                <input wire:model="password" id="password" type="password" autocomplete="new-password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950">
                @if ($this->isEditing())
                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">Kosongkan jika tidak diubah.</p>
                @endif
                @error('password') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password-confirmation" class="mb-1.5 block text-sm font-medium">Ulangi kata sandi</label>
                <input wire:model="passwordConfirmation" id="password-confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950">
            </div>
        </div>

        <fieldset>
            <legend class="mb-2 text-sm font-medium">Layanan</legend>
            <div class="flex flex-wrap gap-x-6 gap-y-2">
                @foreach (['smtp' => 'SMTP', 'imap' => 'IMAP', 'pop3' => 'POP3', 'sieve' => 'Sieve', 'sogo' => 'SOGo'] as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input wire:model="services.{{ $key }}" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <label class="flex items-center gap-2 text-sm">
            <input wire:model="active" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
            Akun aktif
        </label>

        <div class="flex items-center gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ $this->isEditing() ? 'Simpan perubahan' : 'Buat mailbox' }}
            </button>
            <a href="{{ route('mailboxes.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">Batal</a>
        </div>
    </form>

    @if ($this->isEditing())
        <livewire:mailboxes.forwarding :mailbox="$mailbox" :key="'forwarding-'.$mailbox->username" />
    @endif
</div>
