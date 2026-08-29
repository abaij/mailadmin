<?php

namespace Tests\Feature\Models;

use App\Models\Vmail\Mailbox;
use Tests\Concerns\InteractsWithVmail;
use Tests\TestCase;

class VmailTimestampTest extends TestCase
{
    use InteractsWithVmail;

    public function test_it_reports_iredmails_placeholder_timestamp_as_unknown(): void
    {
        $mailbox = Mailbox::factory()->create(['created' => '1970-01-01 01:01:01']);

        $this->assertNull($mailbox->createdAt());
    }

    public function test_it_returns_a_real_creation_timestamp(): void
    {
        $mailbox = Mailbox::factory()->create(['created' => '2025-12-26 02:43:37']);

        $this->assertSame('2025-12-26 02:43:37', $mailbox->createdAt()?->format('Y-m-d H:i:s'));
    }
}
