<?php

namespace App\Http\Controllers;

use App\Events\NewsInserted;
use App\Helpers\ResponseHelper;
use App\Models\Animal;
use App\Models\District;
use App\Models\News;
use App\Models\Organization;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Site;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    /**
     * Fetch all news and return it as Response
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getAllNews(Request $request)
    {
        $request->validate([
            'range' => 'required|in:month,3months,6months,year,week',
        ]);

        $end = date("Y-m-d");
        $start = null;

        if ($request->range == "month") {
            $start = date("Y-m-d", strtotime("-1 month"));
        } else if ($request->range == "3months") {
            $start = date("Y-m-d", strtotime("-3 month"));
        } else if ($request->range == "6months") {
            $start = date("Y-m-d", strtotime("-6 month"));
        } else if ($request->range == "year") {
            $start = date("Y-m-d", strtotime("-1 year"));
        } else {
            $start = date("Y-m-d", strtotime("-1 week"));
        }

        $query = $request->get('query');
        $news = Cache::tags(['news'])->remember("news.$request->range.$query", 300, function () use ($query, $start, $end) {
            return $this->fetchNews($start, $end, $query);
        });

        return ResponseHelper::response("Successfully get all news", 200, ['news' => $news]);
    }

    /**
     * Fetch all news from database
     *
     * @return App\Models\News
     */
    private function fetchNews(string $start, string $end, $queryString)
    {
        $news = News::with(['organization', 'site', 'animals', 'province', 'regency', 'district']);

        if ($queryString != null) {
            $news->where(function($query) use ($queryString) {
                $query->where('title', 'like', "%$queryString%")
                    ->orWhere('label', 'like', "%$queryString%")
                    ->orWhereHas('province', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('regency', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('district', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('animals', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('site', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('organization', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    }
                );
            });
        }

        $news = $news->whereBetween('date', [$start, $end])->get();

        foreach ($news as $singleNews) {
            foreach ($singleNews->animals as $animal) {
                $animal->amount = $animal->pivot->amount;
            }
        }

        return $news;
    }

    /**
     * Save new news data
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'url' => 'required|url',
            'date' => 'date',
            'isTrained' => 'required',
            'animals' => 'required',
            'label' => 'required|in:penyelundupan,penyitaan,perburuan,perdagangan,others',
            'district' => 'required_without_all:regency,province',
            'regency' => 'required_without_all:district,province',
            'province' => 'required_without_all:regency,district',
        ]);

        $news = new News([
            'title' => $request->title,
            'url' => $request->url,
            'date' => $request->date,
            'isTrained' => $request->isTrained,
            'label' => $request->label,
        ]);

        if ($request->district != null) {
            $district = District::with('regency')->where('name', $request->district)->first();
            $news->district_id = $district->id;
            $news->regency_id = $district->regency_id;
            $news->province_id = $district->regency->province_id;
        } else if ($request->regency != null) {
            $regency = Regency::where('name', $request->regency)->first();
            $news->regency_id = $regency->id;
            $news->province_id = $regency->province_id;
        } else if ($request->province != null) {
            $province = Province::where('name', $request->province)->first();
            $news->province_id = $province->id;
        }

        $result = DB::transaction(function () use ($news, $request) {
            try {
                $news->site_id = Site::firstOrCreate(['name' => $request->site])->id;
                $news->organization_id = Organization::firstOrCreate(['name' => $request->organization])->id;
                $news->save();

                foreach ($request->animals as $animal) {
                    $newAnimal = Animal::firstOrCreate(['name' => $animal['name']])->id;

                    $news->animals()->attach($newAnimal, ['amount' => $animal['amount']]);
                }

                Cache::put('organizations', Organization::all(['id', 'name']));
                Cache::put('sites', Site::all(['id', 'name']));
                Cache::put('animals', Animal::all(['id', 'name']));

                Cache::tags(['news'])->flush();

                $news->load(['organization', 'site', 'animals', 'district', 'regency', 'province']);

                return ResponseHelper::response("Successfully store news", 201, ['news' => $news]);
            } catch (Exception $e) {
                return ResponseHelper::response($e->getMessage(), 500);
            }
        });

        return $result;
    }
}
