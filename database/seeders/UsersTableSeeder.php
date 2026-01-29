<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['first_name'=>'Ali','last_name'=>'Hassan','email'=>'ali1@gmail.com','phone'=>'70111111','date_of_birth'=>'1998-05-12','role'=>'EMPLOYEE','status'=>'ACTIVE'],
            ['first_name'=>'Sara','last_name'=>'Khalil','email'=>'sara2@gmail.com','phone'=>'70111112','date_of_birth'=>'1999-07-20','role'=>'EMPLOYEE','status'=>'ACTIVE'],
            ['first_name'=>'Omar','last_name'=>'Nasser','email'=>'omar3@gmail.com','phone'=>'70111113','date_of_birth'=>'1997-01-15','role'=>'WORKER','status'=>'ACTIVE'],
            ['first_name'=>'Lina','last_name'=>'Fares','email'=>'lina4@gmail.com','phone'=>'70111114','date_of_birth'=>'2000-03-08','role'=>'EMPLOYEE','status'=>'PENDING'],
            ['first_name'=>'Hadi','last_name'=>'Salem','email'=>'hadi5@gmail.com','phone'=>'70111115','date_of_birth'=>'1996-11-22','role'=>'WORKER','status'=>'ACTIVE'],
            ['first_name'=>'Maya','last_name'=>'Yousef','email'=>'maya6@gmail.com','phone'=>'70111116','date_of_birth'=>'1999-09-30','role'=>'EMPLOYEE','status'=>'ACTIVE'],
            ['first_name'=>'Ziad','last_name'=>'Karam','email'=>'ziad7@gmail.com','phone'=>'70111117','date_of_birth'=>'1995-04-10','role'=>'WORKER','status'=>'ACTIVE'],
            ['first_name'=>'Rana','last_name'=>'Sami','email'=>'rana8@gmail.com','phone'=>'70111118','date_of_birth'=>'2001-02-18','role'=>'EMPLOYEE','status'=>'ACTIVE'],
            ['first_name'=>'Tarek','last_name'=>'Mahmoud','email'=>'tarek9@gmail.com','phone'=>'70111119','date_of_birth'=>'1994-08-05','role'=>'WORKER','status'=>'ACTIVE'],
            ['first_name'=>'Admin','last_name'=>'User','email'=>'admin@gmail.com','phone'=>'70999999','date_of_birth'=>'1990-01-01','role'=>'ADMIN','status'=>'ACTIVE'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    ...$u,
                    'avatar_path'       => null,
                    'email_verified_at' => Carbon::now(),
                    'password'          => Hash::make('password123'),
                    'remember_token'    => null,
                ]
            );
        }
    }
}
