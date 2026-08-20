<?php

namespace App\Models;

use App\Models\Concerns\UsesSchemaAwareUlids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use UsesSchemaAwareUlids;
}
