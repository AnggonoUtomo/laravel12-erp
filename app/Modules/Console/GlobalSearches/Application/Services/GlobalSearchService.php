<?php

namespace App\Modules\Console\GlobalSearches\Application\Services;

use App\Modules\Console\GlobalSearches\Application\Contracts\EntitySearchProvider;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchContext;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchQuery;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchResult;
use App\Modules\Console\GlobalSearches\Application\Support\ForbiddenSearchResultGuard;
use App\Modules\Console\GlobalSearches\Infrastructure\Providers\UserSearchProvider;

class GlobalSearchService
{
    /**
     * @var list<EntitySearchProvider>
     */
    private array $providers;

    public function __construct(
        private readonly ForbiddenSearchResultGuard $guard,
        UserSearchProvider $users,
    ) {
        $this->providers = [$users];
    }

    /**
     * @return list<SearchResult>
     */
    public function search(SearchQuery $query, SearchContext $context): array
    {
        $results = [];

        foreach ($this->providers as $provider) {
            if (! $provider->canSearch($context)) {
                continue;
            }

            foreach ($provider->search($query, $context) as $result) {
                if ($this->guard->isSafe($result)) {
                    $results[] = $result;
                }
            }
        }

        return array_slice($results, 0, 20);
    }
}
