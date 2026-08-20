<?php

namespace App\Modules\Console\SystemSettings\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\UsesSchemaAwareUlids;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SystemSetting extends Model implements HasMedia
{
    use InteractsWithMedia, UsesSchemaAwareUlids;

    protected $fillable = [
        'group',
        'key',
        'value',
        'encrypted',
    ];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('branding_logo')->singleFile();
        $this->addMediaCollection('branding_favicon')->singleFile();
    }
}
