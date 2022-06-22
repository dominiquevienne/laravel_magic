<?php

namespace Dominiquevienne\LaravelMagic\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;


/**
 * @mixin Builder
 *
 * @method static Builder|static query()
 * @method static static make(array $attributes = [])
 * @method static static create(array $attributes = [])
 * @method static static insert(array $attributes = [])
 * @method static static truncate()
 * @method static static forceCreate(array $attributes)
 * @method static firstOrNew(array $attributes = [], array $values = [])
 * @method static firstOrFail($columns = ['*'])
 * @method static firstOrCreate(array $attributes, array $values = [])
 * @method static firstOr($columns = ['*'], \Closure $callback = null)
 * @method static firstWhere($column, $operator = null, $value = null, $boolean = 'and')
 * @method static updateOrCreate(array $attributes, array $values = [])
 * @method null|static first($columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static static findOrNew($id, $columns = ['*'])
 * @method static null|static find($id, $columns = ['*'])
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder whereIn($column, $values)
 * @method static Builder inRandomOrder($seed = '')
 * @method static Builder orderBy($column, $direction = 'asc')
 */
abstract class AbstractModel extends Model
{
    use HasFactory;

    /**
     * Determine if the given relationship (method) exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasRelation(string $key): bool
    {
        // If the key already exists in the relationships array, it just means the
        // relationship has already been loaded, so we'll just return it out of
        // here because there is no need to query within the relations twice.
        if ($this->relationLoaded($key)) {
            return true;
        }

        // If the "attribute" exists as a method on the model, we will just assume
        // it is a relationship and will load and return results from the query
        // and hydrate the relationship's value on the "relationships" array.
        if (method_exists($this, $key)) {
            //Uses PHP built in function to determine whether the returned object is a laravel relation
            return is_a($this->$key(), Relation::class);
        }

        return false;
    }
}
