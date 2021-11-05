<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Animal;
use App\Models\Category;
use App\Models\News;
use App\Models\Organization;
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
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;
        $query = $request->get('query');

        $news = Cache::tags(['news'])->remember("news.$start.$end.$query", 300, function () use ($query, $start, $end) {
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
        $news = News::with(['organizations', 'site', 'animals.category', 'regencies.province']);

        if ($queryString != null) {
            $news->where(function($query) use ($queryString) {
                $query->where('title', 'like', "%$queryString%")
                    ->orWhere('label', 'like', "%$queryString%")
                    ->orWhereHas('regencies', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('animals', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('site', function($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('organizations', function($query) use ($queryString) {
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
            'url' => 'required|url|unique:App\Models\News,url',
            'date' => 'date',
            'is_trained' => 'required',
            'animals' => 'required',
            'label' => 'required|in:penyelundupan,penyitaan,perburuan,perdagangan,others',
            'regencies' => 'required',
            'regencies.*' => 'numeric'
        ]);

        $news = new News([
            'title' => $request->title,
            'url' => $request->url,
            'date' => $request->date,
            'is_trained' => $request->is_trained,
            'label' => $request->label,
        ]);

        $news->news_date = date('Y-m-d', strtotime($request->news_date));

        $result = DB::transaction(function () use ($news, $request) {
            try {
                if ($request->site) {
                    $news->site_id = Site::firstOrCreate(['name' => $request->site])->id;
                }

                $news->save();

                foreach ($request->animals as $animal) {
                    $category = Category::firstOrCreate(['name' => $animal['category']])->id;
                    $newAnimal = Animal::firstOrCreate([
                        'name' => $animal['name']
                    ], [
                        'scientific_name' => $animal['scientific_name'],
                        'category_id' => $category
                    ])->id;

                    $news->animals()->attach($newAnimal, ['amount' => $animal['amount']]);
                }

                foreach ($request->organizations as $organization) {
                    $newOrganization = Organization::firstOrCreate(['name' => $organization])->id;
                    $news->organizations()->attach($newOrganization);
                }

                foreach ($request->regencies as $regency) {
                    $news->regencies()->attach($regency);
                }

                Cache::put('organizations', Organization::all(['id', 'name']));
                Cache::put('sites', Site::all(['id', 'name']));
                Cache::put('animals', Animal::all(['id', 'name']));

                Cache::tags(['news', 'trending'])->flush();

                $news->load(['organizations', 'site', 'animals.category', 'regencies.province']);

                return ResponseHelper::response("Successfully store news", 201, ['news' => $news]);
            } catch (Exception $e) {
                DB::rollBack();
                return ResponseHelper::response($e->getMessage(), 500);
            }
        });

        return $result;
    }

    /**
     * Check if link is existing or not
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function checkLink(Request $request)
    {
        $request->validate([
            'url' => 'url|required'
        ]);

        $url = $request->get('url');

        $existing = Cache::tags(['news'])->remember($url, 3600, function() use ($url) {
            return News::where('url', $url)->exists();
        });

        return ResponseHelper::response("Successfully check URL", 200, [
            'is_existing' => $existing
        ]);
    }

    /**
     * Get trending news label. Ordered by numbers of news descendingly
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTrendingLabel(Request $request)
    {
        $labels = ['penyelundupan', 'penyitaan', 'perburuan', 'perdagangan', 'others'];

        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $data = [];
        foreach ($labels as $label) {
            $data[] = [
                'name' => $label,
                'news_count' => News::where('label', $label)->whereBetween('date', [$start, $end])->count()
            ];
        }

        $total = 0;
        foreach ($data as $label) {
            $total += $label['news_count'];
        }

        $newsCount = array_column($data, 'news_count');
        array_multisort($newsCount, SORT_DESC, $data);

        return ResponseHelper::response('Successfully get trending label', 200, [
            'total' => $total,
            'selected_start' => $start,
            'selected_end' => $end,
            'labels' => $data
        ]);
    }
}
