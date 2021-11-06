<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Category;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    /**
     * Get all provinces
     *
     * @return \Illuminate\Http\Response;
     */
    public function getProvinces()
    {
        $provinces = Cache::rememberForever('provinces', function() {
            return Province::all();
        });

        return ResponseHelper::response(
            "Successfully get all provinces",
            200,
            ['provinces' => $provinces]
        );
    }

    /**
     * Get all regencies from a province
     *
     * @param  int $provinceId
     * @return Illuminate\Http\Response;
     */
    public function getRegencies(int $provinceId)
    {
        $province = Cache::rememberForever("regencies_$provinceId", function() use ($provinceId) {
            $province = Province::with('regencies:id,name,province_id')->find($provinceId, ['id', 'name']);
            if ($province) {
                foreach ($province->regencies as $regency) {
                    unset($regency->province_id);
                }
            }

            return $province;
        });

        if ($province == null) {
            return ResponseHelper::response("Not found", 404);
        }

        return ResponseHelper::response(
            "Successfully get all regencies in $province->name",
            200,
            ['province' => $province]
        );
    }

    /**
     * Get trending region. Ordered by number of news descendingly
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTrendingProvinces(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $data = Cache::tags(['trending'])
            ->remember("trending.region.$start.$end", 300, function () use ($start, $end) {
                $provinces = DB::table('provinces')
                    ->select('provinces.id', 'provinces.name', 'provinces.latitude', 'provinces.longitude',
                        DB::raw("
                            (SELECT COUNT(*) FROM regencies
                            JOIN news_regency ON news_regency.regency_id=regencies.id
                            JOIN news ON news_regency.news_id=news.id
                            WHERE regencies.province_id=provinces.id
                            AND news.news_date BETWEEN '$start' AND '$end') AS news_count
                        ")
                    )
                    ->orderBy('news_count', 'desc')
                    ->get();

                $total = 0;
                foreach ($provinces as $province) {
                    $total += $province->news_count;
                }

                return [
                    'total' => $total,
                    'selected_start' => $start,
                    'selected_end' => $end,
                    'provinces' => $provinces,
                ];
            });



        return ResponseHelper::response("Successfully get trending region", 200, $data);
    }

    /**
     * Get trending provinces by category id
     *
     * @param  int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTrendingProvincesById($id, Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $category = Category::findOrFail($id);

        $data = Cache::tags(['trending'])
            ->remember("trending.region.$id.$start.$end", 300, function () use ($id, $start, $end) {
                $provinces = DB::table('provinces')
                    ->select('provinces.id', 'provinces.name', 'provinces.latitude', 'provinces.longitude',
                        DB::raw("
                            (SELECT COUNT(*) FROM regencies
                            JOIN news_regency ON news_regency.regency_id=regencies.id
                            JOIN news ON news_regency.news_id=news.id
                            JOIN animal_news ON news.id=animal_news.news_id
                            JOIN animals ON animal_news.animal_id=animals.id
                            WHERE regencies.province_id=provinces.id
                            AND animals.category_id=$id
                            AND news.news_date BETWEEN '$start' AND '$end') AS news_count
                        ")
                    )
                    ->orderBy('news_count', 'desc')
                    ->get();

                $total = 0;
                foreach ($provinces as $province) {
                    $total += $province->news_count;
                }

                return [
                    'total' => $total,
                    'selected_start' => $start,
                    'selected_end' => $end,
                    'provinces' => $provinces,
                ];
            });



        return ResponseHelper::response("Successfully get trending region where $category->name involved", 200, $data);
    }
}
