<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Animal;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnimalController extends Controller
{
    private $labels = ['penyelundupan', 'penyitaan', 'perburuan', 'perdagangan', 'others'];

    /**
     * Get all animals
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response;
     */
    public function getAllAnimals(Request $request)
    {
        $animals = null;
        $query = $request->get('query');

        if ($query != null) {
            $animals = Cache::remember("animals_$query", 3600, function () use ($query) {
                return Animal::with('category')->where('name', 'like', "%$query%")->get();
            });
        } else {
            $animals = Cache::remember('animals', 3600, function () {
                return Animal::with('category')->get();
            });
        }

        return ResponseHelper::response(
            "Successfully get animals",
            200,
            ['animals' => $animals]
        );
    }

    public function getAllCategories(Request $request)
    {
        $categories = null;
        $query = $request->get('query');

        if ($query != null) {
            $categories = Cache::remember("categories_$query", 3600, function () use ($query) {
                return Category::where('name', 'like', "%$query%")->get();
            });
        } else {
            $categories = Cache::remember('categories', 3600, function () {
                return Category::all();
            });
        }

        return ResponseHelper::response(
            "Successfully get categories",
            200,
            ['categories' => $categories]
        );
    }

    /**
     * Save new animal
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response;
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:App\Models\Animal,name',
            'category' => 'required'
        ]);

        $category = Category::firstOrCreate(['name' => $request->category])->id;

        $animal = new Animal([
            'name' => $request->name,
            'category_id' => $category,
            'scientific_name' => $request->scientific_name
        ]);

        if ($animal->save()) {
            Cache::put('animals', Animal::with('category')->get());
            $animal->load('category');

            return ResponseHelper::response(
                "Successfully add new animal",
                201,
                ['animal' => $animal]
            );
        } else {
            return ResponseHelper::response("Unknown server error", 500);
        }
    }

    /**
     * Get numbers of cases by category
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getNumbersOfCases(Request $request)
    {
        $request->validate([
            'start' => 'required_with:end|date',
            'end' => 'required_with:start|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $filter = null;
        if ($start && $end) {
            $filter = " AND news.news_date BETWEEN '$start' AND '$end'";
        }

        $data = Cache::tags(['trending'])
            ->remember("trending.cases.$start.$end", 300, function () use ($start, $end, $filter) {
                $categories = DB::table('categories')
                    ->select(
                        'categories.id',
                        'categories.name',
                        DB::raw("(
                            SELECT count(*) FROM animals
                            JOIN `animal_news` ON `animal_news`.`animal_id` = `animals`.`id`
                            JOIN `news` ON `animal_news`.`news_id` = `news`.`id`
                            WHERE animals.category_id=categories.id" . $filter . ") AS news_count")
                    )
                    ->orderBy('news_count', 'desc')
                    ->orderBy('categories.name')
                    ->get();

                $total = 0;
                foreach ($categories as $category) {
                    $total += $category->news_count;
                }

                return [
                    'selected_start' => $start,
                    'selected_end' => $end,
                    'total' => $total,
                    'categories' => $categories,
                ];
            });


        return ResponseHelper::response("Successfully get animal trending", 200, $data);
    }

    public function getNumbersOfCasesWithLabels(Request $request)
    {
        $request->validate([
            'start' => 'required_with:end|date',
            'end' => 'required_with:start|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $filter = null;
        if ($start && $end) {
            $filter = " AND news.news_date BETWEEN '$start' AND '$end'";
        }

        $data = Cache::tags(['trending'])
            ->remember("trending.cases.$start.$end", 300, function () use ($start, $end, $filter) {
                $subQuery = [
                    'categories.id',
                    'categories.name',
                ];

                foreach ($this->labels as $label) {
                    $subQuery[] = DB::raw("(
                        SELECT count(*) FROM animals
                        JOIN `animal_news` ON `animal_news`.`animal_id` = `animals`.`id`
                        JOIN `news` ON `animal_news`.`news_id` = `news`.`id`
                        WHERE animals.category_id=categories.id" . $filter . "
                        AND `news`.`label` = '$label') AS $label");
                }

                $categories = DB::table('categories')
                    ->select($subQuery)
                    ->addSelect(DB::raw("(
                            SELECT penyelundupan + penyitaan + perdagangan + perburuan + others
                        ) AS total"))
                    ->orderBy('total', 'desc')
                    ->orderBy('categories.name')
                    ->get();

                $total = 0;
                foreach ($categories as $category) {
                    $total += $category->total;
                }

                return [
                    'selected_start' => $start,
                    'selected_end' => $end,
                    'total' => $total,
                    'categories' => $categories,
                ];
            });

        return $data;
    }

    /**
     * Get numbers of cases by ID
     *
     * @param  int $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getNumbersOfCasesById($id, Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $startRecentDate = Carbon::parse($start);
        $startOldDate = Carbon::parse($start);
        $endRecentDate = Carbon::parse($end);
        $endOldDate = Carbon::parse($end);

        $timeDifferences = $startRecentDate->diffInDays($endRecentDate);

        $startOldDate->subDays($timeDifferences + 1);
        $endOldDate->subDays($timeDifferences + 1);

        $category = Cache::tags(['trending'])
            ->remember("trending.animal.$id.$start.$end", 300, function () use ($id, $startRecentDate, $endRecentDate, $startOldDate, $endOldDate) {
                return DB::table('categories')
                    ->select(
                        'categories.id',
                        'categories.name',
                        DB::raw("(
                            SELECT count(*) FROM animals
                            JOIN `animal_news` ON `animal_news`.`animal_id` = `animals`.`id`
                            JOIN `news` ON `animal_news`.`news_id` = `news`.`id`
                            WHERE animals.category_id=categories.id
                            AND news.news_date BETWEEN '$startRecentDate' AND '$endRecentDate'
                        ) AS new_count"),
                        DB::raw("(
                            SELECT count(*) FROM animals
                            JOIN `animal_news` ON `animal_news`.`animal_id` = `animals`.`id`
                            JOIN `news` ON `animal_news`.`news_id` = `news`.`id`
                            WHERE animals.category_id=categories.id
                            AND news.news_date BETWEEN '$startOldDate' AND '$endOldDate'
                        ) AS old_count")
                    )
                    ->where('categories.id', '=', $id)
                    ->orderBy('categories.name')
                    ->first();
            });

        if ($category == null) {
            return ResponseHelper::response("Animal not found", 404);
        }

        return ResponseHelper::response("Successfully get $category->name cases", 200, [
            'selected_start' => $start,
            'selected_end' => $end,
            'category' => $category,
        ]);
    }

    public function getRisingCasesById($id, Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $startRecentDate = Carbon::parse($start);
        $startOldDate = Carbon::parse($start);
        $endRecentDate = Carbon::parse($end);
        $endOldDate = Carbon::parse($end);

        $timeDifferences = $startRecentDate->diffInDays($endRecentDate);

        $startOldDate->subDays($timeDifferences + 1);
        $endOldDate->subDays($timeDifferences + 1);

        $category = Category::findOrFail($id);

        $animals = Cache::tags(['trending'])
            ->remember("trending.animal.v2.$id.$start.$end", 500, function () use ($id, $startRecentDate, $endRecentDate, $startOldDate, $endOldDate) {
            $animals = DB::table('animals')
                ->select('id', 'name', 'scientific_name')
                ->selectRaw("(
                    SELECT COUNT(*) FROM animal_news
                    JOIN news ON animal_news.news_id = news.id
                    WHERE animal_news.animal_id=animals.id
                    AND news.news_date BETWEEN '$startRecentDate' AND '$endRecentDate'
                ) as recent")
                ->selectRaw("(
                    SELECT COUNT(*) FROM animal_news
                    JOIN news ON animal_news.news_id = news.id
                    WHERE animal_news.animal_id=animals.id
                    AND news.news_date BETWEEN '$startOldDate' AND '$endOldDate'
                ) as old")
                ->where('animals.category_id', $id)
                ->get();

            foreach ($animals as $animal) {
                $differences = $animal->recent - $animal->old;
                $animal->differences = $differences;
                $animal->total = $animal->old + $animal->recent;

                $percentage = $differences / ($animal->old == 0 ? 1 : $animal->old) * 100;
                $animal->percentage = $percentage;
            }

            return $animals->sortBy('percentage', SORT_REGULAR, true)->values();;
        });

        return ResponseHelper::response(
            "Successfully get numbers of cases of $category->name",
            200,
            [
                'selected_start' => $startRecentDate->format('Y-m-d'),
                'selected_end' => $endRecentDate->format('Y-m-d'),
                'old_start' => $startOldDate->format('Y-m-d'),
                'old_end' => $endOldDate->format('Y-m-d'),
                'animals' => $animals
            ]
        );
    }

    /**
     * Get percentage of cases by category, sorted by percentage
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRisingCases(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $startRecentDate = Carbon::parse($start);
        $startOldDate = Carbon::parse($start);
        $endRecentDate = Carbon::parse($end);
        $endOldDate = Carbon::parse($end);

        $timeDifferences = $startRecentDate->diffInDays($endRecentDate);

        $startOldDate->subDays($timeDifferences + 1);
        $endOldDate->subDays($timeDifferences + 1);

        $categories = Cache::tags(['trending'])
            ->remember("trending.rising.$start.$end", 300, function () use ($startOldDate, $endOldDate, $startRecentDate, $endRecentDate) {
                return $this->getRankings($startOldDate, $endOldDate, $startRecentDate, $endRecentDate);
            });

        return ResponseHelper::response("Successfully get rising cases", 200, [
            'selected_start' => $startRecentDate->format('Y-m-d'),
            'selected_end' => $endRecentDate->format('Y-m-d'),
            'old_start' => $startOldDate->format('Y-m-d'),
            'old_end' => $endOldDate->format('Y-m-d'),
            'categories' => $categories,
        ]);
    }

    /**
     * Get rank of an animal by category ID
     *
     * @param  integer $id
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getRisingRankById($id, Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $category = Category::findOrFail($id);

        $startRecentDate = Carbon::parse($start);
        $startOldDate = Carbon::parse($start);
        $startVeryOldDate = Carbon::parse($start);
        $endRecentDate = Carbon::parse($end);
        $endOldDate = Carbon::parse($end);
        $endVeryOldDate = Carbon::parse($end);

        $timeDifferences = $startRecentDate->diffInDays($endRecentDate);

        $startOldDate->subDays($timeDifferences + 1);
        $endOldDate->subDays($timeDifferences + 1);

        $startVeryOldDate->subDays(($timeDifferences * 2) + 2);
        $endVeryOldDate->subDays(($timeDifferences * 2) + 2);

        $newRankings = Cache::tags(['trending'])
            ->remember("trending.rising.$startRecentDate.$endRecentDate", 300, function () use ($startOldDate, $endOldDate, $startRecentDate, $endRecentDate) {
                return $this->getRankings($startOldDate, $endOldDate, $startRecentDate, $endRecentDate);
            });

        $oldRankings = Cache::tags(['trending'])
            ->remember("trending.rising.$startVeryOldDate.$endVeryOldDate", 300, function () use ($startVeryOldDate, $endVeryOldDate, $startOldDate, $endOldDate) {
                return $this->getRankings($startVeryOldDate, $endVeryOldDate, $startOldDate, $endOldDate);
            });

        $newRank = null;
        $oldRank = null;

        foreach ($newRankings as $index => $category) {
            if ($category->id == $id) {
                $newRank = $index + 1;
                break;
            }
        }

        foreach ($oldRankings as $index => $category) {
            if ($category->id == $id) {
                $oldRank = $index + 1;
                break;
            }
        }

        return ResponseHelper::response(
            "Successfully get rank of $category->name",
            200,
            [
                'selected_end' => $endRecentDate->format('Y-m-d'),
                'selected_start' => $startRecentDate->format('Y-m-d'),
                'old_end' => $endOldDate->format('Y-m-d'),
                'old_start' => $startOldDate->format('Y-m-d'),
                'very_old_end' => $endVeryOldDate->format('Y-m-d'),
                'very_old_start' => $startVeryOldDate->format('Y-m-d'),
                'new_rank' => $newRank,
                'old_rank' => $oldRank,
            ]
        );
    }

    /**
     * Get rankings from database based on date
     *
     * @param  \Carbon\Carbon $startOldDate
     * @param  \Carbon\Carbon $endOldDate
     * @param  \Carbon\Carbon $startRecentDate
     * @param  \Carbon\Carbon $endRecentDate
     * @return \Illuminate\Support\Collection
     */
    private function getRankings(\Carbon\Carbon $startOldDate, \Carbon\Carbon $endOldDate, \Carbon\Carbon $startRecentDate, \Carbon\Carbon $endRecentDate)
    {
        $categories = DB::table('categories')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw("
                        (SELECT COUNT(*) FROM animals
                        JOIN animal_news ON animal_news.animal_id=animals.id
                        JOIN news ON animal_news.news_id=news.id
                        WHERE animals.category_id=categories.id
                        AND news.news_date BETWEEN '$startOldDate' AND '$endOldDate'
                    ) AS old
                "),
                DB::raw("
                        (SELECT COUNT(*) FROM animals
                        JOIN animal_news ON animal_news.animal_id=animals.id
                        JOIN news ON animal_news.news_id=news.id
                        WHERE animals.category_id=categories.id
                        AND news.news_date BETWEEN '$startRecentDate' AND '$endRecentDate'
                    ) AS recent
                ")
            )
            ->get();

        foreach ($categories as $category) {
            $differences = $category->recent - $category->old;
            $category->total = $category->old + $category->recent;

            $percentage = $differences / ($category->old == 0 ? 1 : $category->old) * 100;
            $category->percentage = $percentage;
        }

        return $categories->sortBy('percentage', SORT_REGULAR, true)->values();
    }

    public function get5YearsPattern($id, Request $request)
    {
        $data = Cache::tags(['trending'])->remember("pattern.$id", 300, function () use ($id) {
            $news = DB::table('news')
                ->selectRaw("MONTH(news.date) as month")
                ->selectRaw("YEAR(news.date) as year")
                ->selectSub(
                    DB::table('news')->selectRaw('COUNT(*)')
                        ->join('animal_news', 'animal_news.news_id', '=', 'news.id')
                        ->join('animals', 'animal_news.animal_id', '=', 'animals.id')
                        ->where('animals.category_id', '=', $id)
                        ->whereRaw("MONTH(news.date) = month")
                        ->whereRaw("YEAR(news.date) = year"),
                    "total"
                )
                ->groupBy('month')
                ->groupBy('year')
                ->get();



            $data = [];
            $newsCount = count($news);

            for ($i = 1; $i < $newsCount; $i++) {
                $thisMonth = $news[$i];
                $lastMonth = $news[$i - 1];

                if ($thisMonth->total > $lastMonth->total) {
                    $data[$thisMonth->month][] = [
                        "year" => $thisMonth->year,
                        "pattern" => "up"
                    ];
                } else if ($thisMonth->total < $lastMonth->total) {
                    $data[$thisMonth->month][] = [
                        "year" => $thisMonth->year,
                        "pattern" => "down"
                    ];
                } else {
                    $data[$thisMonth->month][] = [
                        "year" => $thisMonth->year,
                        "pattern" => "-"
                    ];
                }
            }

            return $data;
        });

        return ResponseHelper::response("Successfully get pattern", 200, [
            'pattern' => $data
        ]);
    }
}
