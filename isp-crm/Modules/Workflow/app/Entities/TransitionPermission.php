<?php

declare(strict_types=1);

namespace Modules\Workflow\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AccessControl\Entities\Role;

class TransitionPermission extends Model
{
    protected $fillable = [
        'transition_id',
        'role_id',
    ];

    public function transition(): BelongsTo
    {
        return $this->belongsTo(Transition::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
