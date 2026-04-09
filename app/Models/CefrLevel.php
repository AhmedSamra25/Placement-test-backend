<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class CefrLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'name',
        'cefr_map',
        'score_min',
        'score_max',
        'goals',
        'color',
    ];

    protected $casts = [
        'score_min' => 'integer',
        'score_max' => 'integer',
    ];

    /** The organization this CEFR level belongs to. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /**
     * Map a numeric score to its matching CEFR level from a given collection.
     * Returns a summary array for the frontend, or null if no match found.
     */
    public static function resolveFromScore(?float $score, Collection $levels): ?array
    {
        if ($score === null) {
            return null;
        }

        /** @var self|null $match */
        $match = $levels->first(
            fn (self $level) => $score >= $level->score_min && $score <= $level->score_max
        );

        return $match ? [
            'name'     => $match->name,
            'cefr_map' => $match->cefr_map,
            'color'    => $match->color,
            'goals'    => $match->goals,
        ] : null;
    }
}
