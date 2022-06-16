<?php

namespace Dominiquevienne\LaravelMagic\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property string $model_name
 * @property string $feature_slug
 * @property string|null $path
 * @property int|null $user_id
 * @property int|null $object_id
 * @property string|null $payload
 * @property string|null $object_before_action
 * @property string|null $ip
 * @property-read string $created_at
 * @property-read string $updated_at
 */
class Statistic extends AbstractModel
{
    protected $fillable = [
        'model_name',
        'feature_slug',
        'path',
        'user_id',
        'object_id',
        'payload',
        'object_before_action',
        'ip',
    ];


    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
