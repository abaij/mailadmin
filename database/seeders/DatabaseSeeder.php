<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The application's own database holds only framework tables (sessions,
     * cache, jobs). Mail accounts live in iRedMail's `vmail` database, which
     * this application never seeds.
     */
    public function run(): void
    {
        //
    }
}
