<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Runs each test inside a transaction on the `vmail` connection and rolls it
 * back afterwards, so tests can write to a real MySQL copy of the iRedMail
 * schema without leaving anything behind.
 *
 * The test database is built from `database/schema/vmail.sql`.
 */
trait InteractsWithVmail
{
    protected function setUpInteractsWithVmail(): void
    {
        $connection = DB::connection('vmail');
        $connection->beginTransaction();

        $this->beforeApplicationDestroyed(function () use ($connection): void {
            $connection->rollBack();
        });
    }
}
