<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Category;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SiteController extends Controller
{
    /**
     * Get all sites
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Helpers\Illuminate\Http\Response;
     */
    public function getAllSites(Request $request)
    {
        $sites = null;
        $query = $request->get('query');

        if ($query != null) {
            $sites = Cache::remember("sites_$query", 3600, function () use ($query) {
                return Site::where('name', 'like', "%$query%")->get(['id', 'name']);
            });
        } else {
            $sites = Cache::remember('sites', 3600, function () {
                return Site::all(['id', 'name']);
            });
        }

        return ResponseHelper::response(
            "Successfully get sites",
            200,
            ['sites' => $sites]
        );
    }

    /**
     * Save new site
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Helpers\Illuminate\Http\Response;
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:App\Models\Site,name'
        ]);

        $site = new Site([
            'name' => $request->name
        ]);

        if ($site->save()) {
            Cache::put('sites', Site::all(['id', 'name']));

            return ResponseHelper::response(
                "Successfully add new site",
                201,
                ['site' => $site]
            );
        } else {
            return ResponseHelper::response("Unknown server error", 500);
        }
    }

    /**
     * Get trending sites.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getTrendingSites(Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $sites = Cache::tags(['trending'])
            ->remember("trending.sites.$start.$end", 300, function () use ($start, $end) {
                return Site::withCount(['news' => function ($query) use ($start, $end) {
                    $query->whereBetween('news.date', [$start, $end]);
                }])
                    ->orderBy('news_count', 'desc')
                    ->get();
            });

        $total = 0;
        foreach ($sites as $site) {
            $total += $site->news_count;
        }

        return ResponseHelper::response("Successfully get trending sites", 200, [
            'total' => $total,
            'selected_start' => $start,
            'selected_end' => $end,
            'sites' => $sites
        ]);
    }

    public function getTrendingSitesById($id, Request $request)
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $start = $request->start;
        $end = $request->end;

        $category = Category::findOrFail($id);

        $sites = Cache::tags(['trending'])
            ->remember("trending.sites.$id.$start.$end", 300, function () use ($id, $start, $end) {
                $sites = Site::withCount(['news' => function ($query) use ($id, $start, $end) {
                    $query->join('animal_news', 'animal_news.news_id', '=', 'news.id')
                        ->join('animals', 'animal_news.animal_id', '=', 'animals.id')
                        ->where('animals.category_id', '=', $id)
                        ->whereBetween('news.date', [$start, $end]);
                }])
                    ->orderBy('news_count', 'desc')
                    ->get();

                $total = 0;
                foreach ($sites as $site) {
                    $total += $site->news_count;
                }

                return [
                    'total' => $total,
                    'selected_start' => $start,
                    'selected_end' => $end,
                    'sites' => $sites
                ];
            });


        return ResponseHelper::response("Successfully get trending sites where $category->name involved", 200, $sites);
    }
}
