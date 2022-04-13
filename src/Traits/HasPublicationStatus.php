<?php

namespace Dominiquevienne\LaravelMagic\Traits;

use Dominiquevienne\LaravelMagic\Exceptions\PublicationStatusException;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $publication_status_id
 * @property array $fillable
 * @method static Builder published()
 */
trait HasPublicationStatus
{
    private array $publicationStatusAvailable = [
        1 => 'published',
        2 => 'unpublished',
        3 => 'waiting for approval',
        4 => 'waiting for translation',
    ];

    private int $publicationStatusDefault = 1;



    public function initializeHasPublicationStatus()
    {
        $this->fillable[] = 'publication_status_id';
    }

    /**
     * @return string
     * @throws PublicationStatusException
     */
    public function getPublicationStatusAttribute():string
    {
        $publicationStatusId = $this?->publication_status_id;

        if (empty($publicationStatusId)) {
            throw new PublicationStatusException('Publication status does not seem to be set for model ' . self::class);
        }
        if (!array_key_exists($publicationStatusId, $this->publicationStatusAvailable)) {
            throw new PublicationStatusException('Publication status with id ' . $publicationStatusId . ' does not exist');
        }

        return $this->publicationStatusAvailable[$publicationStatusId];
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status_id', '=', 1);
    }
}
