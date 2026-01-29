<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $rows = [
            ['name' => 'Organizer',     'description' => 'Coordinates event planning and volunteer organization.'],
            ['name' => 'Civil Defense', 'description' => 'Handles emergency response and safety procedures.'],
            ['name' => 'Media Staff',   'description' => 'Manages photography, videography, and event media coverage.'],
            ['name' => 'Tech Support',  'description' => 'Responsible for sound systems, lighting, and technical setup.'],
            ['name' => 'Cleaner',       'description' => 'Keeps the event and surrounding areas clean and organized.'],
            ['name' => 'Decorator',     'description' => 'Designs and arranges event decorations.'],
            ['name' => 'Cooking Team',  'description' => 'Prepares and serves food for volunteers and attendees.'],
        ];

        foreach ($rows as $r) {
            DB::table('role_types')->updateOrInsert(
                ['name' => $r['name']],
                [
                    'description' => $r['description'],
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ]
            );
        }
    }
}
