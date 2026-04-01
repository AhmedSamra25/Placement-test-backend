<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class Student extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'org_id',
        'name',
        'email',
        'target_language',
        'status',
        'overall_score',
        'test_date',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'test_date'     => 'date',
    ];

    /** The organization this student belongs to. */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    /** The test submission record for this student. */
    public function testSubmission(): HasOne
    {
        return $this->hasOne(TestSubmission::class, 'student_id');
    }

    /** Helper to check if the student's test is finished. */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
