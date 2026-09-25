<?php

namespace App\Http\Controllers;

use App\Services\SearchSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request, SearchSuggestionService $search): JsonResponse
    {
        return response()->json($search->suggest($request->string('q')->toString()));
    }
}
