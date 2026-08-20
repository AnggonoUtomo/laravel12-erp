<?php

namespace App\Models;

use App\Models\Concerns\UsesSchemaAwareUlids;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use UsesSchemaAwareUlids;
}
