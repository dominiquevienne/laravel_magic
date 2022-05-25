<?php

namespace Dominiquevienne\LaravelMagic\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $model_name
 * @property string $feature_slug
 * @property int $user_id
 * @property-read string $created_at
 * @property-read string $updated_at
 */
class Statistic extends AbstractModel
{
    protected $fillable = [
        'model_name',
        'feature_slug',
        'user_id',
    ];


    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
