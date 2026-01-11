<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequiredChapter extends Model
{
    protected $fillable = [
        'curriculum_id',
        'volume_id',
        'book_title',
        'chapter_start',
        'chapter_end',
        'sort_order',
    ];

    protected $casts = [
        'volume_id' => 'integer',
        'chapter_start' => 'integer',
        'chapter_end' => 'integer',
        'sort_order' => 'integer',
    ];

    public const VOLUMES = [
        1 => 'Old Testament',
        2 => 'New Testament',
        3 => 'Book of Mormon',
        4 => 'Doctrine and Covenants',
        5 => 'Pearl of Great Price',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function getVolumeNameAttribute(): string
    {
        return self::VOLUMES[$this->volume_id] ?? 'Unknown';
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->chapter_end && $this->chapter_end !== $this->chapter_start) {
            return "{$this->book_title} {$this->chapter_start}-{$this->chapter_end}";
        }
        return "{$this->book_title} {$this->chapter_start}";
    }

    public function getChapterCountAttribute(): int
    {
        return $this->chapter_end 
            ? ($this->chapter_end - $this->chapter_start + 1) 
            : 1;
    }
}
