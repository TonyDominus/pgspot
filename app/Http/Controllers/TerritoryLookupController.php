<?php

namespace App\Http\Controllers;

use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TerritoryLookupController extends Controller
{
    public function provinces(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ]);

        $provinces = Province::query()
            ->where('region_id', $validated['region_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'code']);

        return response()->json($provinces);
    }

    public function municipalities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
        ]);

        $municipalities = Municipality::query()
            ->where('province_id', $validated['province_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json($municipalities);
    }
}
