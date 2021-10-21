<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NewsController extends Controller
{
    /**
     * Fetch all news from database
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getAllNews(Request $request)
    {
        $news = Cache::remember('news', 3600, function() {
            return News::with('organization')
                ->with('site')
                ->with('animals')
                ->with('province')
                ->with('regency')
                ->with('district')
                ->get();
        });

        return ResponseHelper::response("Successfully get all news", 200, ['news' => $news]);
    }
}
