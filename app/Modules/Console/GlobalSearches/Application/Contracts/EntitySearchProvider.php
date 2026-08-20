<?php

namespace App\Modules\Console\GlobalSearches\Application\Contracts;

use App\Modules\Console\GlobalSearches\Application\DTOs\SearchContext;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchQuery;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchResult;

interface EntitySearchProvider
{
    public function key(): string;

    public function label(): string;

    public function canSearch(SearchContext $context): bool;

    /**
     * @return list<SearchResult>
     */
    public function search(SearchQuery $query, SearchContext $context): array;
}
