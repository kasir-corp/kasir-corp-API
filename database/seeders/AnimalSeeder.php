<?php

namespace Database\Seeders;

use App\Imports\AnimalImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class AnimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Excel::import(new AnimalImport, base_path('resources/data/animalCategory.csv'));
    }
}
