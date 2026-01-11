<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scripture extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id',
        'volume_id',
        'volume_title',
        'book_title',
        'chapter',
        'verse',
        'verse_title',
        'word_count',
        'scripture_text',
    ];

    protected $casts = [
        'volume_id' => 'integer',
        'chapter' => 'integer',
        'verse' => 'integer',
        'word_count' => 'integer',
    ];

    public const VOLUMES = [
        1 => 'Old Testament',
        2 => 'New Testament',
        3 => 'Book of Mormon',
        4 => 'Doctrine and Covenants',
        5 => 'Pearl of Great Price',
    ];

    public function scopeInVolume($query, int $volumeId)
    {
        return $query->where('volume_id', $volumeId);
    }

    public function scopeInBook($query, string $bookTitle)
    {
        return $query->where('book_title', $bookTitle);
    }

    public function scopeInChapter($query, int $chapter)
    {
        return $query->where('chapter', $chapter);
    }

    public function scopeChapters($query)
    {
        return $query->selectRaw('
            volume_id,
            volume_title,
            book_title,
            chapter,
            MIN(id) as first_verse_id,
            MAX(id) as last_verse_id,
            SUM(word_count) as total_words,
            COUNT(*) as verse_count
        ')->groupBy('volume_id', 'volume_title', 'book_title', 'chapter');
    }

    public static function getDistinctBooks(): array
    {
        return static::query()
            ->select('volume_id', 'book_title')
            ->distinct()
            ->orderBy('volume_id')
            ->orderByRaw('MIN(id)')
            ->groupBy('volume_id', 'book_title')
            ->get()
            ->groupBy('volume_id')
            ->map(fn ($books) => $books->pluck('book_title')->toArray())
            ->toArray();
    }

    public static function getChaptersForBook(string $bookTitle): array
    {
        return static::query()
            ->where('book_title', $bookTitle)
            ->select('chapter')
            ->distinct()
            ->orderBy('chapter')
            ->pluck('chapter')
            ->toArray();
    }
}
