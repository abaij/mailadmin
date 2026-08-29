<?php

namespace App\Support\IRedMail;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Builds the hashed maildir path iRedMail stores in `mailbox.maildir`.
 *
 * Canonical shape: `<domain>/<c1>/<c2>/<c3>/<localpart>-<Y.m.d.H.i.s>/`
 * where c1..c3 are the first three characters of the local part, lowercased,
 * with anything outside [a-z0-9] replaced by `_` and short local parts padded
 * with `_`.
 *
 * Existing rows in the database use several older, inconsistent layouts. This
 * class is only ever used for newly created mailboxes -- rewriting the maildir
 * of a live account would orphan the mail already on disk.
 */
class MaildirPath
{
    private const HASH_DEPTH = 3;

    private const PAD = '_';

    public function generate(string $email, ?DateTimeInterface $createdAt = null): string
    {
        [$localPart, $domain] = $this->split($email);

        $createdAt ??= new DateTimeImmutable;

        return implode('/', [
            $domain,
            ...$this->hashSegments($localPart),
            $localPart.'-'.$createdAt->format('Y.m.d.H.i.s'),
        ]).'/';
    }

    /**
     * The three hashed directory levels for a local part.
     *
     * @return list<string>
     */
    public function hashSegments(string $localPart): array
    {
        $normalised = preg_replace('/[^a-z0-9]/', self::PAD, strtolower($localPart)) ?? '';
        $normalised = str_pad($normalised, self::HASH_DEPTH, self::PAD);

        return str_split(substr($normalised, 0, self::HASH_DEPTH));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function split(string $email): array
    {
        $email = strtolower(trim($email));
        $at = strrpos($email, '@');

        if ($at === false || $at === 0 || $at === strlen($email) - 1) {
            throw new InvalidArgumentException("[{$email}] is not a valid email address.");
        }

        return [substr($email, 0, $at), substr($email, $at + 1)];
    }
}
