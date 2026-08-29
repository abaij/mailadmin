<?php

use App\Models\Vmail\Alias;
use App\Models\Vmail\Domain;
use App\Services\AliasService;
use App\Support\IRedMail\AddressRegistry;
use App\Support\Scoping\ManagedDomains;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?Alias $alias = null;

    public string $localPart = '';

    public string $domain = '';

    public string $name = '';

    /** One recipient per line, the way admins paste them. */
    public string $members = '';

    public bool $active = true;

    /**
     * Resolved by route-model binding on the `{alias}` parameter; null on the
     * create route.
     */
    public function mount(?Alias $alias = null): void
    {
        if ($alias === null) {
            $this->domain = (string) $this->availableDomains->first();

            return;
        }

        abort_unless(auth()->user()->canManageDomain($alias->domain), 403);

        $this->alias = $alias;
        $this->localPart = $alias->localPart();
        $this->domain = $alias->domain;
        $this->name = (string) $alias->name;
        $this->active = $alias->active;
        $this->members = $alias->members()->orderBy('forwarding')->pluck('forwarding')->implode("\n");
    }

    public function isEditing(): bool
    {
        return $this->alias !== null;
    }

    #[Computed]
    public function availableDomains()
    {
        return ManagedDomains::scope(Domain::query())->where('active', 1)->orderBy('domain')->pluck('domain');
    }

    /**
     * @return list<string>
     */
    public function parsedMembers(): array
    {
        return Str::of($this->members)
            ->explode("\n")
            ->map(fn (string $line): string => Str::lower(trim($line)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function save(AliasService $service, AddressRegistry $registry)
    {
        $data = $this->validate($this->rules(), $this->messages());

        abort_unless(auth()->user()->canManageDomain($data['domain']), 403);

        try {
            if ($this->isEditing()) {
                $service->update(
                    $this->alias,
                    ['name' => $data['name'], 'active' => $data['active']],
                    $this->parsedMembers(),
                );

                session()->flash('status', "Alias {$this->alias->address} diperbarui.");
            } else {
                $created = $service->create([
                    'address' => $data['localPart'].'@'.$data['domain'],
                    'name' => $data['name'],
                    'active' => $data['active'],
                ], $this->parsedMembers());

                session()->flash('status', "Alias {$created->address} dibuat.");
            }
        } catch (\RuntimeException $exception) {
            // Domain limits and address collisions are only knowable inside the
            // service's transaction, so they surface as errors here.
            $this->addError('localPart', $exception->getMessage());

            return null;
        }

        return $this->redirect(route('aliases.index'), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'localPart' => [
                'required', 'string', 'max:64',
                'regex:/^[a-z0-9]([a-z0-9._-]*[a-z0-9])?$/',
                Rule::when(! $this->isEditing(), [$this->availableAddressRule()]),
            ],
            'domain' => ['required', 'string', Rule::in($this->availableDomains)],
            'name' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
            'members' => ['nullable', 'string', $this->validMemberAddressesRule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'localPart.regex' => 'Gunakan huruf kecil, angka, titik, garis bawah, atau tanda hubung.',
            'domain.in' => 'Anda tidak mengelola domain tersebut.',
        ];
    }

    private function availableAddressRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $address = Str::lower($value).'@'.$this->domain;
            $owner = app(AddressRegistry::class)->addressOwner($address);

            if ($owner !== null) {
                $fail("{$address} sudah dipakai sebagai {$owner}.");
            }
        };
    }

    private function validMemberAddressesRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $invalid = array_filter(
                $this->parsedMembers(),
                fn (string $member): bool => ! filter_var($member, FILTER_VALIDATE_EMAIL),
            );

            if ($invalid !== []) {
                $fail('Bukan alamat email yang valid: '.implode(', ', $invalid));
            }
        };
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <a href="{{ route('aliases.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">← Kembali ke daftar</a>
        <h1 class="mt-2 text-2xl font-semibold">{{ $this->isEditing() ? $alias->address : 'Tambah alias' }}</h1>
    </div>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
            <label class="mb-1.5 block text-sm font-medium">Alamat alias</label>
            <div class="flex gap-2">
                <input
                    wire:model="localPart"
                    type="text"
                    @disabled($this->isEditing())
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 disabled:bg-zinc-50 disabled:text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950 dark:disabled:bg-zinc-900"
                    placeholder="sales"
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
                    Alamat alias tidak bisa diubah — setiap baris penerima terikat padanya.
                </p>
            @endif
            @error('localPart') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            @error('domain') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium">Keterangan</label>
            <input wire:model="name" id="name" type="text" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950" placeholder="Tim penjualan">
            @error('name') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="members" class="mb-1.5 block text-sm font-medium">Penerima</label>
            <textarea
                wire:model.blur="members"
                id="members"
                rows="6"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950"
                placeholder="andi@contoh.com&#10;budi@contoh.com&#10;tim@perusahaan-lain.com"
            ></textarea>
            <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                Satu alamat per baris. Boleh alamat di luar server ini.
                @if (count($this->parsedMembers()) > 0)
                    <span class="text-zinc-700 dark:text-zinc-300">{{ count($this->parsedMembers()) }} penerima.</span>
                @endif
            </p>
            @error('members') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            @if ($this->parsedMembers() === [])
                <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                    Alias tanpa penerima tetap menerima email, lalu membuangnya tanpa jejak.
                </p>
            @endif
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input wire:model="active" type="checkbox" class="rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
            Alias aktif
        </label>

        <div class="flex items-center gap-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:opacity-60 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ $this->isEditing() ? 'Simpan perubahan' : 'Buat alias' }}
            </button>
            <a href="{{ route('aliases.index') }}" wire:navigate class="text-sm text-zinc-500 hover:underline dark:text-zinc-400">Batal</a>
        </div>
    </form>
</div>
