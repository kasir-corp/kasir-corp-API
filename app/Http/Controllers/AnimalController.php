<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnimalController extends Controller
{
    /**
     * Get all animals
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Helpers\Illuminate\Http\Response;
     */
    public function getAllAnimals(Request $request)
    {
        $animals = null;
        $query = $request->get('query');

        if ($query != null) {
            $animals = Cache::remember("animals_$query", 3600, function() use ($query) {
                return Animal::where('name', 'like', "%$query%")->get(['id', 'name']);
            });
        } else {
            $animals = Cache::remember('animals', 3600, function() {
                return Animal::all(['id', 'name']);
            });
        }

        return ResponseHelper::response(
            "Successfully get animals",
            200,
            ['animals' => $animals]
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:App\Models\Animal,name'
        ]);

        $animal = new Animal([
            'name' => $request->name
        ]);

        if ($animal->save()) {
            Cache::put('animals', Animal::all(['id', 'name']));

            return ResponseHelper::response(
                "Successfully add new animal",
                201,
                ['animal' => $animal]
            );
        } else {
            return ResponseHelper::response("Unknown server error", 500);
        }
    }
}
