<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Animal;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnimalController extends Controller
{
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
            $animals = Cache::remember("animals_$query", 3600, function() use ($query) {
                return Animal::with('category')->where('name', 'like', "%$query%")->get();
            });
        } else {
            $animals = Cache::remember('animals', 3600, function() {
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
            $categories = Cache::remember("categories_$query", 3600, function() use ($query) {
                return Category::where('name', 'like', "%$query%")->get();
            });
        } else {
            $categories = Cache::remember('categories', 3600, function() {
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
}
