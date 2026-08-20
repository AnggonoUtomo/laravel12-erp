<?php

namespace App\Modules\Console\NotificationTemplates\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\UsesSchemaAwareUlids;

class NotificationTemplate extends Model
{
    use UsesSchemaAwareUlids;

    protected $fillable = [
        'key',
        'name',
        'channel',
        'subject',
        'body',
        'variables',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'active' => 'boolean',
        ];
    }
}
