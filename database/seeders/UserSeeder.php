<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Farrel Najib Anshary',
            'email' => 'farrelnajib@gmail.com',
            'password' => Hash::make('admin123')
        ]);

        User::create([
            'name' => 'Jackie Leonardy',
            'email' => 'jackiezheng2024@gmail.com',
            'password' => Hash::make('admin123')
        ]);

        User::create([
            'name' => 'Veronica Michelle Darmabudi',
            'email' => 'vmichelledb@gmail.com',
            'password' => Hash::make('admin123')
        ]);

        User::create([
            'name' => 'Christian Adiputra',
            'email' => 'adiputrachristian17@gmail.com',
            'password' => Hash::make('admin123')
        ]);

        User::create([
            'name' => 'Puras Handharmahua',
            'email' => 'phandharmahua@gmail.com',
            'password' => Hash::make('admin123')
        ]);

        User::create([
            'name' => 'Muhammad Hanif Handokoputra',
            'email' => 'putra@hanifputra.com',
            'password' => Hash::make('admin123')
        ]);
    }
}
