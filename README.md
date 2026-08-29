# Mail Admin

An admin interface for the mail accounts of an [iRedMail](https://www.iredmail.org/)
server, built on Laravel and Livewire. It reads and writes iRedMail's own
`vmail` database directly — there is no separate account store, and iRedAdmin
can keep running alongside it.

## What it manages

- **Domains** — description, per-mailbox quota ceiling, mailbox and alias count
  limits, activate/deactivate, create and delete.
- **Mailboxes** — create, edit, quota, password reset, per-protocol service
  switches (SMTP / IMAP / POP3 / Sieve / SOGo), activate/deactivate, delete.
- **Aliases** — one address fanning out to several recipients, internal or
  external, edited as a plain list.
- **Forwarding** — per-mailbox destinations, with an explicit "keep a copy"
  switch, on the mailbox's own page.
- **Alias domains** — a whole domain mirroring another, on the target domain's
  page.
- **Dashboard** — account and storage totals, accounts nearing their quota,
  recent logins, accounts that have never logged in.

Administrators sign in with their existing mail credentials. A global admin
(`mailbox.isglobaladmin`, or a `domain_admins` row against the literal domain
`ALL`) sees everything; a domain admin sees only the domains granted to them.
Everyone else is refused at the door.

## Architecture

Two database connections, and they never mix:

| Connection | Contents | Migrated by this app |
| --- | --- | --- |
| `vmail` | iRedMail's tables: `domain`, `mailbox`, `alias`, `forwardings`, … | **No** |
| default (`sqlite`/`mysql`) | Sessions, cache, queue | Yes |

`vmail` is owned by iRedMail. This application reads and writes rows in it but
never alters its shape — `database/schema/vmail.sql` is a copy of the schema
kept only for building a test database.

Key pieces:

- `app/Support/IRedMail/PasswordHasher.php` — generates and verifies the
  `{SSHA512}` and `{SHA512-CRYPT}` hashes Dovecot expects. Changing a password
  keeps whatever scheme the account already uses.
- `app/Support/IRedMail/MaildirPath.php` — reproduces iRedMail's hashed maildir
  layout, `<domain>/<c1>/<c2>/<c3>/<localpart>-<Y.m.d.H.i.s>/`.
- `app/Services/MailboxService.php` — the only place that writes to `mailbox`.
  Deleting an account queues its maildir in `deleted_mailboxes` and clears the
  related per-user rows, all inside one transaction.
- `app/Services/AliasService.php` and `app/Services/ForwardingService.php` —
  both write to `forwardings`, and each touches only its own rows (`is_alias`
  versus `is_forwarding`).
- `app/Support/IRedMail/AddressRegistry.php` — checks an address against
  `mailbox`, `alias` and `maillists` at once, and a domain name against
  `domain` and `alias_domain`. Nothing in the schema stops these from
  shadowing each other.
- `app/Support/Scoping/ManagedDomains.php` — restricts every query to the
  domains the signed-in admin may manage. It fails closed.

## Things worth knowing about iRedMail's schema

- `mailbox.quota` is in **megabytes**; `used_quota.bytes` is in **bytes**.
- `1970-01-01 01:01:01` is iRedMail's placeholder for an unknown timestamp.
  Use `VmailModel::createdAt()`, which returns `null` for it.
- A plain mailbox needs **no** row in `forwardings`. Postfix resolves it through
  `virtual_mailbox_maps`. Rows there mean a real forward is configured.
- `forwardings` is one table serving three jobs, told apart by its flags:
  `is_alias` for alias recipients, `is_forwarding` for a mailbox owner's
  forwarding, `is_list`/`is_maillist` for mailing lists. Code that writes one
  kind must scope its deletes so it does not wipe the others.
- Because Postfix expands `virtual_alias_maps` *before* it looks up a mailbox,
  a mailbox with forwarding rows and no row pointing at itself stops receiving
  its own mail. That self-row is the "keep a copy" switch.
- An alias with no recipients still accepts mail and then discards it silently.
- Deleting a `mailbox` row does **not** remove the mail from disk. iRedMail's
  `cleanup_maildir` cron job does that, driven by `deleted_mailboxes`. If that
  cron is not running, deleted accounts leave their files behind.
- Addresses and domain names are immutable here: both are baked into the
  maildir path already on disk.

## Local setup

Requires PHP 8.3+, Node 20+, and a MySQL or MariaDB server.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create two databases from the schema — one to develop against, one for tests:

```bash
mysql -uroot -e "CREATE DATABASE vmail_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
mysql -uroot -e "CREATE DATABASE vmail_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
mysql -uroot vmail_testing < database/schema/vmail.sql
```

Load a dump from the real server into `vmail_dev` to develop against real data,
then point `.env` at it:

```
VMAIL_DB_DATABASE=vmail_dev
VMAIL_DB_USERNAME=root
VMAIL_DB_PASSWORD=
```

Finally, the application's own tables and the dev server:

```bash
php artisan migrate
npm run build
php artisan serve
```

To sign in you need an account in `vmail_dev` whose password you know — the
hashes in a production dump are not reversible. Create one with tinker:

```bash
php artisan tinker --execute="app(App\Services\MailboxService::class)->create(['username' => 'devadmin@example.com', 'password' => 'dev-password', 'isadmin' => 1, 'isglobaladmin' => 1]);"
```

## Tests

```bash
composer test
```

That runs Pint, PHPStan and PHPUnit. The feature tests write to
`vmail_testing` inside a transaction that is rolled back after each test, so
they leave the database empty — but point them at a scratch database, never at
production.

## Deployment notes

Run this on the mail server itself and connect to MySQL over `localhost`. If it
must run elsewhere, restrict the MySQL account by source IP, give it rights on
`vmail` only, and require TLS. Do not expose port 3306 to the internet.
