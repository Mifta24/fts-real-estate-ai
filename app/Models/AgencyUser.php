<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AgencyUser extends Pivot
{
    public const ROLE_OWNER = 'owner';

    public const ROLE_AGENT = 'agent';

    protected $table = 'agency_users';
}
