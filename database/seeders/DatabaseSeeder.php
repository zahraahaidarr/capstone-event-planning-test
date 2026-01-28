<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         $this->call([
            UsersTableSeeder::class,
            RoleTypesTableSeeder::class,
            WorkersUsersSeeder::class,
            VenueSeeder::class,
            SeedEventsAndWorkRolesSeeder::class,
            TodayReservationsSeeder::class,
            PostEventSubmissionsSeeder::class,
            PostEventReportsSeeder::class,
            NotificationsSeeder::class,
            BackfillUserNamesSeeder::class,
        ]);
    }
}
