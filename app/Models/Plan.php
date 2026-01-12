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
        'public_token',
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
            ->where('reading_date', \Carbon\Carbon::parse($date)->startOfDay())
            ->where('completed', true)
            ->exists();
    }

    public function toggleProgress(string $date): void
    {
        $carbonDate = \Carbon\Carbon::parse($date)->startOfDay();
        
        $progress = $this->progress()
            ->where('reading_date', $carbonDate)
            ->first();

        if ($progress) {
            $progress->update([
                'completed' => !$progress->completed,
                'completed_at' => !$progress->completed ? now() : null,
            ]);
        } else {
            $this->progress()->create([
                'reading_date' => $carbonDate,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }
    }

    public function generatePublicToken(): string
    {
        if (!$this->public_token) {
            $this->public_token = \Illuminate\Support\Str::random(32);
            $this->save();
        }
        
        return $this->public_token;
    }

    public function getPublicUrl(): ?string
    {
        if (!$this->public_token) {
            return null;
        }
        
        return route('plans.public', $this->public_token);
    }

    public function revokePublicAccess(): void
    {
        $this->public_token = null;
        $this->save();
    }
}
