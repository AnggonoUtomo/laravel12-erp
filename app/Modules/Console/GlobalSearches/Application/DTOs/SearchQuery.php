<?php

namespace App\Modules\Console\GlobalSearches\Application\DTOs;

use Illuminate\Http\Request;

final readonly class SearchQuery
{
    public function __construct(
        public string $term,
        public int $limit = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            term: trim($request->string('term')->toString()),
            limit: min(10, max(1, $request->integer('limit', 10))),
        );
    }
}
