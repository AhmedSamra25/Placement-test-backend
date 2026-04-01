<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
