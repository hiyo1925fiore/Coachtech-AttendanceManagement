<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => '一般ユーザー1',
                'email' => 'general1@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => 0,
            ],
            [
                'name' => '管理者ユーザー',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => 1,
            ],
            [
                'name' => '一般ユーザー2',
                'email' => 'general2@gmail.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => 0,
            ],
        ]);
    }
}
