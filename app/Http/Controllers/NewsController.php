<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Animal;
use App\Models\Category;
use App\Models\Edited;
use App\Models\News;
use App\Models\Organization;
use App\Models\Site;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    private $labels = ['penyelundupan', 'penyitaan', 'perburuan', 'perdagangan', 'others'];
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

        return ResponseHelper::response("Successfully get all news", 200, $news);
    }

    /**
     * Fetch all news from database
     *
     * @param  string $start
     * @param  string $end
     * @param  string $queryString
     * @return App\Models\News
     */
    private function fetchNews(string $start, string $end, $queryString)
    {
        $news = News::with(['organizations', 'site', 'animals.category', 'regencies.province']);

        if ($queryString != null) {
            $news->where(function ($query) use ($queryString) {
                $query->where('title', 'like', "%$queryString%")
                    ->orWhere('label', 'like', "%$queryString%")
                    ->orWhereHas('regencies', function ($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('animals', function ($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas('site', function ($query) use ($queryString) {
                        return $query->where('name', 'like', "%$queryString%");
                    })
                    ->orWhereHas(
                        'organizations',
                        function ($query) use ($queryString) {
                            return $query->where('name', 'like', "%$queryString%");
                        }
                    );
            });
        }

        $news = $news->whereBetween('date', [$start, $end])->get();

        $total = count($news);

        return [
            'total' => $total,
            'selected_start' => $start,
            'selected_end' => $end,
            'news' => $news
        ];
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

        $existing = Cache::tags(['news'])->remember($url, 3600, function () use ($url) {
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
        $labels = $this->labels;
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $data = Cache::tags(['trending'])->remember("trending.labels.$start.$end", 300, function () use ($start, $end, $labels) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = [
                    'name' => $label,
                    'news_count' => News::where('label', $label)->whereBetween('date', [$start, $end])->count()
                ];
            }
            return $data;
        });

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

    /**
     * Get trending label where an animal is included
     *
     * @param  int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTrendingLabelById($id, Request $request)
    {
        $labels = $this->labels;
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $category = Category::findOrFail($id);

        $data = Cache::tags(['trending'])->remember("trending.labels.$id.$start.$end", 300, function () use ($id, $start, $end, $labels) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = [
                    'name' => $label,
                    'news_count' => News::whereHas('animals', function ($query) use ($id) {
                        $query->where('category_id', $id);
                    })
                        ->where('label', $label)
                        ->whereBetween('date', [$start, $end])
                        ->count()
                ];
            }

            $total = 0;
            foreach ($data as $label) {
                $total += $label['news_count'];
            }

            return [
                'total' => $total,
                'selected_start' => $start,
                'selected_end' => $end,
                'labels' => $data
            ];
        });

        return ResponseHelper::response("Successfully get trending label where $category->name involved", 200, $data);
    }

    /**
     * Get all news by category ID
     *
     * @param  int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getAllNewsByCategoryId($id, Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $category = Category::findOrFail($id);

        $newsData = Cache::tags(['news'])->remember("news.$id.$start.$end", 300, function () use ($id, $start, $end) {
            $news = News::whereHas('animals', function ($query) use ($id) {
                $query->where('category_id', '=', $id);
            })->orWhereHas('edited.animals', function ($query) use ($id, $start, $end) {
                $query->where('category_id', '=', $id)
                    ->whereBetween('news.date', [$start, $end]);
            })->with([
                'animals.category',
                'regencies.province',
                'site',
                'organizations'
            ])->get();

            $total = count($news);

            return [
                'total' => $total,
                'selected_start' => $start,
                'selected_end' => $end,
                'news' => $news
            ];
        });

        return ResponseHelper::response("Successfully get news where $category->name involved", 200, $newsData);
    }

    /**
     * Edit news
     *
     * @param  int $newsId
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(int $newsId, Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'animals' => 'required',
            'label' => 'required|in:penyelundupan,penyitaan,perburuan,perdagangan,others',
            'regencies.*' => 'numeric'
        ]);

        $edited = Edited::firstOrNew(['user_id' => Auth::id(), 'news_id' => $newsId]);
        $edited->date = $request->date;
        $edited->notes = $request->notes;

        $result = DB::transaction(function () use ($edited, $request) {
            try {
                if ($request->site) {
                    $edited->site_id = Site::firstOrCreate(['name' => $request->site])->id;
                }

                $edited->save();

                $animals = [];
                foreach ($request->animals as $animal) {
                    $animalName = strtolower($animal);
                    $category = explode(" ", $animalName)[0];
                    $categoryId = Category::firstOrCreate(['name' => $category])->id;
                    $newAnimal = Animal::firstOrCreate([
                        'name' => $animal
                    ], [
                        'category_id' => $categoryId
                    ])->id;

                    $animals[] = $newAnimal;
                }
                $edited->animals()->sync($animals);

                $organizations = [];
                foreach ($request->organizations as $organization) {
                    $newOrganization = Organization::firstOrCreate(['name' => $organization])->id;
                    $organizations[] = $newOrganization;
                }
                $edited->organizations()->sync($organizations);

                $regencies = [];
                foreach ($request->regencies as $regency) {
                    $regencies[] = $regency;
                }
                $edited->regencies()->sync($regencies);

                Cache::put('organizations', Organization::all(['id', 'name']));
                Cache::put('sites', Site::all(['id', 'name']));
                Cache::put('animals', Animal::all(['id', 'name']));

                Cache::tags(['news', 'trending'])->flush();

                $edited->load(['organizations', 'site', 'animals.category', 'regencies.province']);

                return ResponseHelper::response("Successfully edit news", 200, ['edited' => $edited]);
            } catch (Exception $e) {
                DB::rollBack();
                return ResponseHelper::response($e->getMessage(), 500);
            }
        });

        return $result;
    }
}
