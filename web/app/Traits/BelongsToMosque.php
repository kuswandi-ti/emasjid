<?php

namespace App\Traits;

use App\Models\Mosque;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait BelongsToMosque
 *
 * Provides multi-tenant isolation for mosque-scoped models.
 * - Adds a global scope to filter records by the current mosque_id.
 * - Auto-fills mosque_id on model creation.
 */
trait BelongsToMosque
{
    public static function bootBelongsToMosque(): void
    {
        static::addGlobalScope('mosque', function (Builder $builder) {
            if ($mosqueId = mosque_id()) {
                $builder->where($builder->getModel()->getTable().'.mosque_id', $mosqueId);
            }
        });

        static::creating(function (Model $model) {
            if (empty($model->mosque_id) && $mosqueId = mosque_id()) {
                $model->mosque_id = $mosqueId;
            }
        });
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }
}
