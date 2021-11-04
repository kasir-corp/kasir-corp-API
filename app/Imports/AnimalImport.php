<?php

namespace App\Imports;

use App\Models\Animal;
use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class AnimalImport implements ToCollection, WithCustomCsvSettings
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $animal = new Animal();
            $animal->name = $row[0];
            $animal->category_id = Category::firstOrCreate(['name' => $row[1]])->id;
            $animal->scientific_name = $row[2];

            $animal->save();
        }
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';'
        ];
    }
}
