<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrganizationController extends Controller
{
    /**
     * Get all organizations
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Helpers\Illuminate\Http\Response;
     */
    public function getAllOrganizations(Request $request)
    {
        $organizations = null;
        $query = $request->get('query');

        if ($query != null) {
            $organizations = Cache::remember("organizations_$query", 3600, function() use ($query) {
                return Organization::where('name', 'like', "%$query%")->get(['id', 'name']);
            });
        } else {
            $organizations = Cache::remember('organizations', 3600, function() {
                return Organization::all(['id', 'name']);
            });
        }

        return ResponseHelper::response(
            "Successfully get organizations",
            200,
            ['organizations' => $organizations]
        );
    }

    /**
     * Save new organization
     *
     * @param  \Illuminate\Http\Request $request
     * @return \App\Helpers\Illuminate\Http\Response;
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:App\Models\Organization,name'
        ]);

        $organization = new Organization([
            'name' => $request->name
        ]);

        if ($organization->save()) {
            Cache::put('organizations', Organization::all(['id', 'name']));

            return ResponseHelper::response(
                "Successfully add new organization",
                201,
                ['organization' => $organization]
            );
        } else {
            return ResponseHelper::response("Unknown server error", 500);
        }
    }
}
