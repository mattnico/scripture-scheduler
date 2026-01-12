<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Curriculum extends Model
{
    use HasUuids;

    protected $table = 'curricula';

    protected $fillable = [
        'teacher_id',
        'name',
        'enrollment_code',
        'deadline',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Curriculum $curriculum) {
            if (empty($curriculum->enrollment_code)) {
                $curriculum->enrollment_code = strtoupper(Str::random(6));
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function requiredChapters(): HasMany
    {
        return $this->hasMany(RequiredChapter::class)->orderBy('sort_order');
    }

    /**
     * Get a formatted list of all required chapters for display.
     */
    public function getChapterListAttribute(): string
    {
        return $this->requiredChapters
            ->map(fn ($ch) => $ch->chapter_end 
                ? "{$ch->book_title} {$ch->chapter_start}-{$ch->chapter_end}"
                : "{$ch->book_title} {$ch->chapter_start}")
            ->join(', ');
    }

    /**
     * Get total chapter count across all required chapters.
     */
    public function getTotalChaptersAttribute(): int
    {
        return $this->requiredChapters->sum(function ($ch) {
            return $ch->chapter_end 
                ? ($ch->chapter_end - $ch->chapter_start + 1) 
                : 1;
        });
    }

    public function getEnrollmentUrlAttribute(): string
    {
        return route('enrollments.create') . '?code=' . $this->enrollment_code;
    }

    public function getQrCodeUrlAttribute(): string
    {
        $data = urlencode($this->enrollment_url);
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$data}";
    }
}
