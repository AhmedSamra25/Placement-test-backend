<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'answers',
        'draft_answers',
        'writing_essay_path',
        'speaking_audio_paths',
        'pronunciation_audio_path',
        'reading_score',
        'writing_score',
        'speaking_score',
        'listening_score',
        'vocabulary_score',
        'grammar_score',
        'pronunciation_score',
        'ai_status',
        'ai_feedback',
    ];

    protected $casts = [
        'answers'              => 'array',
        'draft_answers'        => 'array',
        'speaking_audio_paths' => 'array',
        'reading_score'        => 'decimal:2',
        'writing_score'        => 'decimal:2',
        'speaking_score'       => 'decimal:2',
        'listening_score'      => 'decimal:2',
        'vocabulary_score'     => 'decimal:2',
        'grammar_score'        => 'decimal:2',
        'pronunciation_score'  => 'decimal:2',
    ];

    /** The student this submission belongs to. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Calculate the average overall score from all skill scores.
     * Returns null if no scores are present.
     */
    public function calculateOverallScore(): ?float
    {
        $scores = array_filter([
            $this->reading_score,
            $this->writing_score,
            $this->speaking_score,
            $this->listening_score,
            $this->vocabulary_score,
            $this->grammar_score,
            $this->pronunciation_score,
        ], fn ($v) => $v !== null);

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 2);
    }
}
