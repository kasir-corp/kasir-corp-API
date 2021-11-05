<?php

namespace App\Imports;

use App\Models\Animal;
use App\Models\Category;
use App\Models\News;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\Site;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class NewsImport implements ToCollection
{
    private $labels = ['penyelundupan', 'penyitaan', 'perburuan', 'perdagangan', 'others'];
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        DB::transaction(function () use ($collection) {
            try {
                foreach ($collection as $row) {
                    $date = date('Y-m-d', ($row[0] - 25569) * 86400);
                    $label = strtolower(explode(' ', $row[1])[0]);
                    $url = $row[4];

                    $news = new News([
                        'title' => $row[3],
                        'url' => $url,
                        'date' => $date,
                        'news_date' => $date,
                        'is_trained' => 0,
                        'label' => in_array($label, $this->labels) ? $label : 'others'
                    ]);

                    $baseUrl = explode('/', $url)[2];
                    $site = Site::firstOrCreate(['name' => $baseUrl])->id;
                    $news->site_id = $site;

                    $news->save();

                    if ($row[10]) {
                        $pihakTerkait = Organization::firstOrCreate(['name' => $row[10]])->id;
                        $news->organizations()->attach($pihakTerkait);
                    }

                    if ($row[11]) {
                        $penindak = Organization::firstOrCreate(['name' => $row[11]])->id;
                        $news->organizations()->attach($penindak);
                    }

                    if ($row[12]) {
                        $animalName = strtolower($row[12]);
                        $categoryName = explode(' ', $animalName)[0];
                        $category = Category::firstOrCreate(['name' => $categoryName])->id;

                        $animal = Animal::firstOrCreate(
                            [
                                'name' => $animalName
                            ],
                            [
                                'scientific_name' => $row[13],
                                'category_id' => $category
                            ]
                        )->id;
                        $news->animals()->attach($animal, ['amount' => $row[17] ?? 0]);
                    }

                    $regency = optional(Regency::where('name', strtolower($row[25]))->first())->id;
                    $news->regencies()->attach($regency);
                }
            } catch(Exception $e) {
                DB::rollBack();
                dd($e);
            }
        });
    }
}
