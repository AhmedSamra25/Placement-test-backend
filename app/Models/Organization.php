<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'license_limit',
        'license_used',
    ];

    protected $casts = [
        'license_limit' => 'integer',
        'license_used'  => 'integer',
    ];

    /** Users (admins & moderators) belonging to this organization. */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }

    /** CEFR levels configured by this organization. */
    public function cefrLevels(): HasMany
    {
        return $this->hasMany(CefrLevel::class, 'org_id');
    }

    /** Students registered under this organization. */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'org_id');
    }

    /** Remaining licenses available. */
    public function getRemainingLicensesAttribute(): int
    {
        return max(0, $this->license_limit - $this->license_used);
    }
}
