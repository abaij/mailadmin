<?php

use App\Models\Vmail\Mailbox;
use App\Services\ForwardingService;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public Mailbox $mailbox;

    /** One destination per line. */
    public string $destinations = '';

    /** Whether a copy is still delivered into this mailbox. */
    public bool $keepCopy = true;

    /** Shows the confirmation line until the admin touches the form again. */
    public bool $justSaved = false;

    public function mount(ForwardingService $service): void
    {
        abort_unless(auth()->user()->canManageDomain($this->mailbox->domain), 403);

        $this->destinations = implode("\n", $service->destinationsFor($this->mailbox));
        $this->keepCopy = $service->destinationsFor($this->mailbox) === []
            ? true
            : $service->keepsCopy($this->mailbox);
    }

    /**
     * @return list<string>
     */
    public function parsedDestinations(): array
    {
        return Str::of($this->destinations)
            ->explode("\n")
            ->map(fn (string $line): string => Str::lower(trim($line)))
            ->filter()
            ->reject(fn (string $line): bool => $line === Str::lower($this->mailbox->username))
            ->unique()
            ->values()
            ->all();
    }

    public function updated(): void
    {
        $this->justSaved = false;
    }

    public function save(ForwardingService $service): void
    {
        abort_unless(auth()->user()->canManageDomain($this->mailbox->domain), 403);

        $this->validate(
            ['destinations' => ['nullable', 'string', $this->validAddressesRule()]],
        );

        $service->sync($this->mailbox, $this->parsedDestinations(), $this->keepCopy);

        $this->justSaved = true;
    }

    private function validAddressesRule(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $invalid = array_filter(
                $this->parsedDestinations(),
                fn (string $destination): bool => ! filter_var($destination, FILTER_VALIDATE_EMAIL),
            );

            if ($invalid !== []) {
                $fail('Bukan alamat email yang valid: '.implode(', ', $invalid));
            }
        };
    }
};
?>

<div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex items-baseline justify-between gap-4">
        <h2 class="text-sm font-semibold">Penerusan email</h2>
        @if ($justSaved)
            <span class="text-xs text-emerald-600 dark:text-emerald-400">Tersimpan</span>
        @endif
    </div>

    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
        Salinan email yang masuk ke alamat ini dikirim ke tujuan di bawah.
    </p>

    <form wire:submit="save" class="mt-4 space-y-4">
        <div>
            <label for="forwarding-destinations" class="sr-only">Alamat tujuan</label>
            <textarea
                wire:model.blur="destinations"
                id="forwarding-destinations"
                rows="4"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 font-mono text-sm outline-none transition focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950"
                placeholder="tujuan@contoh.com"
            ></textarea>
            <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                Satu alamat per baris. Kosongkan untuk mematikan penerusan.
            </p>
            @error('destinations') <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-2 text-sm">
            <input wire:model.live="keepCopy" type="checkbox" class="mt-0.5 rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950">
            <span>
                Simpan juga salinan di kotak surat ini
                <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                    Postfix memproses penerusan sebelum mencari kotak surat, jadi tanpa opsi ini email tidak tersimpan di sini.
                </span>
            </span>
        </label>

        @if (! $keepCopy && $this->parsedDestinations() !== [])
            <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                Email ke <strong>{{ $mailbox->username }}</strong> akan diteruskan seluruhnya dan
                <strong>tidak</strong> masuk ke kotak surat ini.
            </p>
        @endif

        <button type="submit" wire:loading.attr="disabled" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium transition hover:bg-zinc-50 disabled:opacity-60 dark:border-zinc-700 dark:hover:bg-zinc-800">
            Simpan penerusan
        </button>
    </form>
</div>
