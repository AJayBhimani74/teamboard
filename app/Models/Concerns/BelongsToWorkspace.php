<?php

namespace App\Models\Concerns;

use App\CurrentWorkspace;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $query) {
            $id = app(CurrentWorkspace::class)->id();

            if ($id !== null) {
                $query->where($query->getModel()->getTable().'.workspace_id', $id);
            }
        });

        static::creating(function ($model) {
            $model->workspace_id ??= app(CurrentWorkspace::class)->id();
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
