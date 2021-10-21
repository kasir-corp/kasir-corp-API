<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    /**
     * Get all keywords
     *
     * @return /Illuminate/Http/Response
     */
    public function getKeywords()
    {
        $keywords = Cache::remember('keywords', 3600, function () {
            $users = User::all();
            $keyword = [];
            foreach ($users as $user) {
                $keyword[] = $user->keyword;
            }
            return $keyword;
        });

        return ResponseHelper::response("Successfully get all keywords", 200, ['keywords' => $keywords]);
    }
}
