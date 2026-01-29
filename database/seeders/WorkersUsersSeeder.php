<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class WorkersUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ✅ 10 worker users (emails must be unique)
            $users = [
                ['first_name'=>'Rana',   'last_name'=>'Haddad',  'email'=>'worker101@gmail.com', 'phone'=>'70101010', 'dob'=>'2002-01-10'],
                ['first_name'=>'Mira',   'last_name'=>'Khalil',  'email'=>'worker102@gmail.com', 'phone'=>'70101011', 'dob'=>'2001-03-22'],
                ['first_name'=>'Nada',   'last_name'=>'Hussein', 'email'=>'worker103@gmail.com', 'phone'=>'70101012', 'dob'=>'2000-07-19'],
                ['first_name'=>'Hala',   'last_name'=>'Yassin',  'email'=>'worker104@gmail.com', 'phone'=>'70101013', 'dob'=>'2003-05-12'],
                ['first_name'=>'Samer',  'last_name'=>'Mansour', 'email'=>'worker105@gmail.com', 'phone'=>'70101014', 'dob'=>'1999-10-21'],
                ['first_name'=>'Omar',   'last_name'=>'Fadel',   'email'=>'worker106@gmail.com', 'phone'=>'70101015', 'dob'=>'2002-02-14'],
                ['first_name'=>'Lina',   'last_name'=>'Rahhal',  'email'=>'worker107@gmail.com', 'phone'=>'70101016', 'dob'=>'2004-06-18'],
                ['first_name'=>'Kareem', 'last_name'=>'Hassan',  'email'=>'worker108@gmail.com', 'phone'=>'70101017', 'dob'=>'1998-07-23'],
                ['first_name'=>'Yara',   'last_name'=>'Saad',    'email'=>'worker109@gmail.com', 'phone'=>'70101018', 'dob'=>'2001-11-26'],
                ['first_name'=>'Rami',   'last_name'=>'Nasser',  'email'=>'worker110@gmail.com', 'phone'=>'70101019', 'dob'=>'2000-02-16'],
            ];

            /**
             * ✅ IMPORTANT:
             * Do NOT hardcode role_type_id (it differs between DBs).
             * Use role_type_name and map it from DB.
             */
            $workersProfile = [
                ['role_type_name'=>'Civil Defense', 'engagement_kind'=>'VOLUNTEER', 'is_volunteer'=>1, 'location'=>'beirut',   'hourly_rate'=>null],
                ['role_type_name'=>'Cleaner',       'engagement_kind'=>'PAID',      'is_volunteer'=>0, 'location'=>'byblos',   'hourly_rate'=>10.00],
                ['role_type_name'=>'Cooking Team',  'engagement_kind'=>'PAID',      'is_volunteer'=>0, 'location'=>'beirut',   'hourly_rate'=>12.00],
                ['role_type_name'=>'Decorator',     'engagement_kind'=>'VOLUNTEER', 'is_volunteer'=>1, 'location'=>'beirut',   'hourly_rate'=>null],
                ['role_type_name'=>'Media Staff',   'engagement_kind'=>'VOLUNTEER', 'is_volunteer'=>1, 'location'=>'beirut',   'hourly_rate'=>null],
                ['role_type_name'=>'Organizer',     'engagement_kind'=>'VOLUNTEER', 'is_volunteer'=>1, 'location'=>'beirut',   'hourly_rate'=>null],
                ['role_type_name'=>'Tech Support',  'engagement_kind'=>'PAID',      'is_volunteer'=>0, 'location'=>'nabatieh', 'hourly_rate'=>15.00],
                ['role_type_name'=>'Tech Support',  'engagement_kind'=>'PAID',      'is_volunteer'=>0, 'location'=>'beirut',   'hourly_rate'=>14.00],
                ['role_type_name'=>'Civil Defense', 'engagement_kind'=>'VOLUNTEER', 'is_volunteer'=>1, 'location'=>'tyre',     'hourly_rate'=>null],
                ['role_type_name'=>'Cleaner',       'engagement_kind'=>'VOLUNTEER', 'is_volunteer'=>1, 'location'=>'tripoli',  'hourly_rate'=>null],
            ];

            // Map role types from DB: name => role_type_id (works in MySQL + Postgres)
            $roleTypeMap = DB::table('role_types')->pluck('role_type_id', 'name')->toArray();

            // Safety check: all role names exist
            $neededNames = array_values(array_unique(array_column($workersProfile, 'role_type_name')));
            $missing = array_values(array_diff($neededNames, array_keys($roleTypeMap)));
            if (!empty($missing)) {
                throw new \RuntimeException("Missing role types in role_types table: " . implode(', ', $missing));
            }

            $now = now();

            foreach ($users as $idx => $u) {
                $wp = $workersProfile[$idx];
                $roleTypeId = (int)$roleTypeMap[$wp['role_type_name']];

                // ✅ Default certificate path (NOT NULL safe for Postgres)
                // Put any string that matches your app convention.
                $defaultCertificatePath = 'certificates/default.pdf';

                // ✅ Create or get user by email (idempotent)
                $userRow = DB::table('users')->where('email', $u['email'])->first();

                if (!$userRow) {
                    $userId = DB::table('users')->insertGetId([
                        'first_name'        => $u['first_name'],
                        'last_name'         => $u['last_name'],
                        'email'             => $u['email'],
                        'phone'             => $u['phone'],
                        'date_of_birth'     => $u['dob'],
                        'avatar_path'       => null,
                        'role'              => 'WORKER',
                        'status'            => 'ACTIVE',
                        'email_verified_at' => null,
                        'password'          => Hash::make('Password@123'),
                        'remember_token'    => null,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ]);
                } else {
                    $userId = (int)$userRow->id;
                }

                // ✅ Ensure worker profile exists (idempotent)
                $existingWorker = DB::table('workers')->where('user_id', $userId)->first();
                if ($existingWorker) {
                    DB::table('workers')->where('user_id', $userId)->update([
                        'role_type_id'        => $roleTypeId,
                        'engagement_kind'     => $wp['engagement_kind'],
                        'is_volunteer'        => $wp['is_volunteer'],
                        'location'            => $wp['location'],
                        'hourly_rate'         => $wp['hourly_rate'],

                        // ✅ keep it NOT NULL (if old row has null, we fix it)
                        'certificate_path'    => $existingWorker->certificate_path ?: $defaultCertificatePath,

                        'updated_at'          => $now,
                    ]);
                    continue;
                }

                DB::table('workers')->insert([
                    'user_id'             => $userId,
                    'role_type_id'        => $roleTypeId,
                    'engagement_kind'     => $wp['engagement_kind'],
                    'is_volunteer'        => $wp['is_volunteer'],
                    'location'            => $wp['location'],

                    // ✅ FIX: must not be null (Postgres NOT NULL constraint)
                    'certificate_path'    => $defaultCertificatePath,

                    'total_hours'         => 0,
                    'verification_status' => 'PENDING',
                    'hourly_rate'         => $wp['hourly_rate'],
                    'approved_by'         => null,
                    'approved_at'         => null,
                    'joined_at'           => Carbon::now()->toDateString(),
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }
        });
    }
}
