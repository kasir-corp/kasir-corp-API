<?php

namespace App\Imports;

use App\Models\Regency;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class RegionImport implements ToCollection
{
    private $data;
    public function __construct($model)
    {
        $this->data = $model;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $regions = $this->data;
        foreach ($regions as $key=>$value) {
            $regions[$key]['name'] = $collection[$key][0];
            $regions[$key]->save();
        }
    }
}
