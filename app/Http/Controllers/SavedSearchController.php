<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SavedSearchController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(auth()->user()->savedSearches);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(
            [
                'name' => 'required|string|max:255',
                'criteria' => 'required|array',
            ]
        );

        $search = auth()->user()->savedSearches()->create(
            [
                'name' => $request->name,
                'criteria' => $request->criteria,
            ]
        );

        return response()->json($search);
    }

    public function share(Request $request): JsonResponse
    {
        $request->validate(
            [
                'criteria' => 'required|array',
            ]
        );

        /** @var SavedSearch $search */
        $search = auth()->user()->savedSearches()->create(
            [
                'name' => 'Shared Search',
                'criteria' => $request->criteria,
                'share_token' => Str::random(32),
            ]
        );

        return response()->json(['token' => $search->share_token]);
    }

    public function loadShared($token): JsonResponse
    {
        $search = SavedSearch::where(
            'share_token',
            $token
        )->firstOrFail();

        return response()->json($search->criteria);
    }
}
