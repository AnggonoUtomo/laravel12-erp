<?php

namespace App\Modules\Console\GlobalSearches\Infrastructure\Providers;

use App\Models\User;
use App\Modules\Console\GlobalSearches\Application\Contracts\EntitySearchProvider;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchContext;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchQuery;
use App\Modules\Console\GlobalSearches\Application\DTOs\SearchResult;

class UserSearchProvider implements EntitySearchProvider
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Users';
    }

    public function canSearch(SearchContext $context): bool
    {
        return $context->can('users.view');
    }

    /**
     * @return list<SearchResult>
     */
    public function search(SearchQuery $query, SearchContext $context): array
    {
        if (! $this->canSearch($context)) {
            return [];
        }

        return User::query()
            ->select(['id', 'name', 'email'])
            ->when(! $context->hasRole(User::SUPER_SYSTEM_ROLE), function ($builder) {
                $builder->whereDoesntHave('roles', fn ($roles) => $roles->where('name', User::SUPER_SYSTEM_ROLE));
            })
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('name', 'like', "%{$query->term}%")
                    ->orWhere('email', 'like', "%{$query->term}%");
            })
            ->orderBy('name')
            ->orderBy('email')
            ->orderBy('id')
            ->limit($query->limit)
            ->get()
            ->map(fn (User $user) => new SearchResult(
                id: "users:{$user->id}",
                provider: $this->key(),
                type: 'user',
                title: $user->name,
                group: 'Users',
                url: route('users.index', ['search' => $user->name], false),
                description: 'Akun Console',
                badges: ['read-only'],
                meta: [
                    'userId' => $user->id,
                ],
            ))
            ->values()
            ->all();
    }
}
