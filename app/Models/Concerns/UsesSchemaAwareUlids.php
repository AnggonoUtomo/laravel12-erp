<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Support\Facades\Schema;

trait UsesSchemaAwareUlids
{
    use HasUlids;

    /** @var array<string, bool> */
    private static array $ulidKeyState = [];

    public function usesUniqueIds(): bool
    {
        $connection = $this->getConnectionName() ?? config('database.default');
        $cacheKey = $connection.':'.$this->getTable().'.'.$this->getKeyName();

        return self::$ulidKeyState[$cacheKey] ??= $this->hasUlidPrimaryKey($connection);
    }

    private function hasUlidPrimaryKey(string $connection): bool
    {
        try {
            return strcasecmp(
                Schema::connection($connection)->getColumnType($this->getTable(), $this->getKeyName()),
                'char'
            ) === 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
