<?php

namespace App\Models;

use App\Models\Concerns\UsesSchemaAwareUlids;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends SpatieMedia
{
    use UsesSchemaAwareUlids;
}
