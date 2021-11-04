<?php

namespace Database\Seeders;

use App\Imports\NewsSheetsImport;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class NewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Excel::import(new NewsSheetsImport, base_path('resources/data/newsData.xlsx'));
    }
}
