<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostEventSubmissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Get existing reservation IDs (works for MySQL + Postgres)
        $reservationIds = DB::table('workers_reservations')->pluck('id')->toArray();

        // If none exist, do NOT crash deploy
        if (empty($reservationIds)) {
            $this->command?->info('PostEventSubmissionsSeeder: No workers_reservations found, skipping.');
            return;
        }

        // Pick up to 10 reservations to create submissions for
        $reservationIds = array_slice($reservationIds, 0, 10);

        foreach ($reservationIds as $rid) {

            // Idempotent: don't insert if already exists
            $exists = DB::table('post_event_submissions')
                ->where('reservation_id', $rid)
                ->exists();

            if ($exists) continue;

            DB::table('post_event_submissions')->insert([
                'reservation_id' => $rid,

                // Adjust these column names to match YOUR table exactly:
                'report_text'    => 'Post-event report seeded for demo purposes.',
                'status'         => 'SUBMITTED', // or 'PENDING' based on your system
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }

        $this->command?->info('PostEventSubmissionsSeeder: Seeded submissions successfully.');
    }
}
