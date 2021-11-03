<?php

namespace App\Imports;

use App\Models\Regency;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class RegencyImport implements ToModel, WithCustomCsvSettings
{
    /**
     * @param array $row
     *
     * @return User|null
     */
    public function model(array $row)
    {
        return new Regency([
            'id' => $row[0],
            'province_id' => $row[1],
            'name' => $row[2]
        ]);
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';'
        ];
    }
}
