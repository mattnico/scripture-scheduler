<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'enrollment_id',
        'start_date',
        'end_date',
        'scheduling_method',
        'beginning_verse',
        'volumes',
        'total_words',
        'words_per_day',
        'total_days',
        'schedule',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'volumes' => 'array',
        'schedule' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function getTodaysReadingAttribute(): ?array
    {
        $today = now()->toDateString();
        $schedule = $this->schedule ?? [];
        
        foreach ($schedule as $entry) {
            if ($entry['date'] === $today) {
                return $entry;
            }
        }
        
        return null;
    }

    public function getCompletedDaysAttribute(): int
    {
        return $this->progress()->where('completed', true)->count();
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalDays = count($this->schedule ?? []);
        if ($totalDays === 0) return 0;
        
        return round(($this->completed_days / $totalDays) * 100, 1);
    }

    public function isDateCompleted(string $date): bool
    {
        return $this->progress()
            ->where('reading_date', $date)
            ->where('completed', true)
            ->exists();
    }

    public function toggleProgress(string $date): void
    {
        $progress = $this->progress()->firstOrCreate(
            ['reading_date' => $date],
            ['completed' => false]
        );

        $progress->update([
            'completed' => !$progress->completed,
            'completed_at' => !$progress->completed ? now() : null,
        ]);
    }
}
