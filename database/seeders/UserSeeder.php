<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'email_verified_at' => now(),
            'password' =>  Hash::make('testtest'),
            'remember_token' => Str::random(10),
        ]);

        // User::create([
        //     'name' => 'Lucky Star',
        //     'email' => 'company@luckystar.com',
        //     'email_verified_at' => now(),
        //     'password' =>  Hash::make('Lucky1234.'),
        //     'remember_token' => Str::random(10),
        // ]);
    }
}
