<?php

namespace App\Modules\Console\GlobalSearches\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchContext;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchQuery;
use App\Modules\Console\GlobalSearches\Application\Services\GlobalSearchService;
use App\Modules\Console\GlobalSearches\Presentation\Http\Requests\GlobalSearchRequest;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $search,
    ) {}

    public function index(GlobalSearchRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $results = $this->search->search(
            SearchQuery::fromRequest($request),
            SearchContext::fromUser($user),
        );

        return response()->json([
            'data' => collect($results)
                ->map(fn ($result) => $result->toArray())
                ->values(),
        ]);
    }
}
