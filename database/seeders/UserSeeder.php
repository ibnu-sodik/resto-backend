<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Test Pelayan',
            'email' => 'pelayan@email.test',
            'password' => Hash::make('passPelayan'),
            'role' => 'pelayan'
        ]);
        User::create([
            'name' => 'Test Kasir',
            'email' => 'kasir@email.test',
            'password' => Hash::make('passKasir'),
            'role' => 'kasir'
        ]);
    }
}
