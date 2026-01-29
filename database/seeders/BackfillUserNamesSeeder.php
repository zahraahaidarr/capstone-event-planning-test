<?php
// database/seeders/BackfillUserNamesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;   // ✅ FIX: import Schema
use App\Models\User;

class BackfillUserNamesSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Safety checks (prevents deploy crashes)
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'name')) {
            return;
        }

        if (!Schema::hasColumn('users', 'first_name') || !Schema::hasColumn('users', 'last_name')) {
            return;
        }

        User::query()->chunkById(500, function ($chunk) {
            foreach ($chunk as $u) {

                // if already filled, skip
                if (!empty($u->first_name) || !empty($u->last_name)) continue;

                $name = trim((string) ($u->name ?? ''));
                if ($name === '') continue;

                // split on last space; supports multi-word first names
                $parts = preg_split('/\s+/', $name);
                $last  = array_pop($parts) ?? '';
                $first = trim(implode(' ', $parts));

                if ($first === '') { $first = $last; $last = ''; } // single-name users

                $u->first_name = $first;
                $u->last_name  = $last;
                $u->save();
            }
        });
    }
}
