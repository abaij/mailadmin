<?php

namespace App\Services;

use App\Models\Vmail\Forwarding;
use App\Models\Vmail\Mailbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Per-user mail forwarding.
 *
 * Postfix expands `virtual_alias_maps` before it looks a mailbox up, so as soon
 * as an address has forwarding rows, mail goes only to those destinations. The
 * row whose destination is the address itself is what puts a copy back in the
 * owner's mailbox; drop it and the mailbox stops receiving its own mail.
 */
class ForwardingService
{
    /**
     * Replaces a mailbox's forwarding with exactly this configuration.
     *
     * @param  list<string>  $destinations  Addresses to forward to, excluding the mailbox itself.
     * @param  bool  $keepCopy  Whether to also deliver into the mailbox.
     */
    public function sync(Mailbox $mailbox, array $destinations, bool $keepCopy = true): void
    {
        $wanted = $this->wantedRows($mailbox, $destinations, $keepCopy);

        DB::connection('vmail')->transaction(function () use ($mailbox, $wanted): void {
            $rows = Forwarding::query()->userForwardings()->where('address', $mailbox->username);

            if ($wanted === []) {
                $rows->delete();

                return;
            }

            $existing = (clone $rows)->pluck('forwarding')->all();

            (clone $rows)->whereNotIn('forwarding', $wanted)->delete();

            foreach (array_diff($wanted, $existing) as $destination) {
                Forwarding::query()->create([
                    'address' => $mailbox->username,
                    'forwarding' => $destination,
                    'domain' => $mailbox->domain,
                    'dest_domain' => Str::after($destination, '@'),
                    'is_forwarding' => 1,
                    'active' => 1,
                ]);
            }
        });
    }

    /**
     * The addresses this mailbox forwards to, excluding the keep-a-copy row.
     *
     * @return list<string>
     */
    public function destinationsFor(Mailbox $mailbox): array
    {
        return array_values(
            Forwarding::query()
                ->userForwardings()
                ->where('address', $mailbox->username)
                ->where('forwarding', '!=', $mailbox->username)
                ->orderBy('forwarding')
                ->pluck('forwarding')
                ->map(fn (string $destination): string => $destination)
                ->all()
        );
    }

    public function keepsCopy(Mailbox $mailbox): bool
    {
        return Forwarding::query()
            ->userForwardings()
            ->where('address', $mailbox->username)
            ->where('forwarding', $mailbox->username)
            ->exists();
    }

    /**
     * True when mail reaches this mailbox only by being forwarded away.
     */
    public function forwardsWithoutKeepingACopy(Mailbox $mailbox): bool
    {
        return $this->destinationsFor($mailbox) !== [] && ! $this->keepsCopy($mailbox);
    }

    /**
     * @param  list<string>  $destinations
     * @return list<string>
     */
    private function wantedRows(Mailbox $mailbox, array $destinations, bool $keepCopy): array
    {
        $self = Str::lower($mailbox->username);

        $normalised = array_map(
            fn (string $destination): string => Str::lower(trim($destination)),
            $destinations,
        );

        $rows = array_filter(
            $normalised,
            // The mailbox's own address is not a destination; it is the
            // keep-a-copy marker, governed by $keepCopy.
            fn (string $destination): bool => $destination !== '' && $destination !== $self,
        );

        if ($keepCopy) {
            $rows[] = $self;
        }

        return array_values(array_unique($rows));
    }
}
