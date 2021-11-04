<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NewsSheetsImport implements WithMultipleSheets
{
    /**
    * @return array()
    */
    public function sheets(): array
    {
        return [
            0 => new NewsImport()
        ];
    }
}
