<?php

namespace App\Services;

use App\Models\Scripture;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleCalculator
{
    public function calculate(array $params): array
    {
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);
        $volumes = $params['volumes'] ?? [];
        $schedulingMethod = $params['scheduling_method'] ?? 'verse';
        $beginningVerse = $params['beginning_verse'] ?? null;
        $requiredChapters = $params['required_chapters'] ?? null;

        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($requiredChapters) {
            return $this->calculateFromRequiredChapters($startDate, $totalDays, $requiredChapters, $schedulingMethod);
        }

        if ($schedulingMethod === 'chapter') {
            return $this->calculateChapterSchedule($startDate, $totalDays, $volumes, $beginningVerse);
        }

        return $this->calculateVerseSchedule($startDate, $totalDays, $volumes, $beginningVerse);
    }

    protected function calculateFromRequiredChapters(Carbon $startDate, int $totalDays, Collection $requiredChapters, string $schedulingMethod): array
    {
        if ($schedulingMethod === 'verse') {
            return $this->calculateVerseScheduleFromChapters($startDate, $totalDays, $requiredChapters);
        }

        return $this->calculateChapterScheduleFromChapters($startDate, $totalDays, $requiredChapters);
    }

    protected function calculateChapterScheduleFromChapters(Carbon $startDate, int $totalDays, Collection $requiredChapters): array
    {
        $allChapters = [];

        foreach ($requiredChapters as $req) {
            $chapterStart = $req->chapter_start;
            $chapterEnd = $req->chapter_end ?? $req->chapter_start;

            for ($ch = $chapterStart; $ch <= $chapterEnd; $ch++) {
                $chapterData = Scripture::where('book_title', $req->book_title)
                    ->where('chapter', $ch)
                    ->selectRaw('book_title, chapter, volume_id, SUM(word_count) as word_count, COUNT(*) as verse_count')
                    ->groupBy('book_title', 'chapter', 'volume_id')
                    ->first();

                if ($chapterData) {
                    $allChapters[] = $chapterData;
                }
            }
        }

        if (empty($allChapters)) {
            return $this->emptyResult();
        }

        $totalWords = array_sum(array_map(fn($c) => $c->word_count, $allChapters));
        $wordsPerDay = (int) ceil($totalWords / $totalDays);

        $schedule = [];
        $currentIndex = 0;
        $totalChapterCount = count($allChapters);

        for ($day = 0; $day < $totalDays && $currentIndex < $totalChapterCount; $day++) {
            $date = $startDate->copy()->addDays($day)->toDateString();
            $dayWords = 0;
            $dayChapters = [];

            while ($currentIndex < $totalChapterCount && $dayWords < $wordsPerDay) {
                $chapter = $allChapters[$currentIndex];
                $dayWords += $chapter->word_count;
                $dayChapters[] = "{$chapter->book_title} {$chapter->chapter}";
                $currentIndex++;
            }

            if (!empty($dayChapters)) {
                $schedule[] = [
                    'date' => $date,
                    'reading' => count($dayChapters) === 1 
                        ? $dayChapters[0] 
                        : $this->formatChapterRange($dayChapters),
                    'word_count' => $dayWords,
                    'chapter_count' => count($dayChapters),
                ];
            }
        }

        return [
            'schedule' => $schedule,
            'total_words' => $totalWords,
            'words_per_day' => $wordsPerDay,
            'total_days' => count($schedule),
        ];
    }

    protected function calculateVerseScheduleFromChapters(Carbon $startDate, int $totalDays, Collection $requiredChapters): array
    {
        $allVerses = collect();

        foreach ($requiredChapters as $req) {
            $chapterStart = $req->chapter_start;
            $chapterEnd = $req->chapter_end ?? $req->chapter_start;

            $verses = Scripture::where('book_title', $req->book_title)
                ->whereBetween('chapter', [$chapterStart, $chapterEnd])
                ->orderBy('verse_id')
                ->get();

            $allVerses = $allVerses->concat($verses);
        }

        if ($allVerses->isEmpty()) {
            return $this->emptyResult();
        }

        $totalWords = $allVerses->sum('word_count');
        $wordsPerDay = (int) ceil($totalWords / $totalDays);

        $schedule = [];
        $currentIndex = 0;
        $versesArray = $allVerses->values()->all();
        $totalVerses = count($versesArray);

        for ($day = 0; $day < $totalDays && $currentIndex < $totalVerses; $day++) {
            $date = $startDate->copy()->addDays($day)->toDateString();
            $dayWords = 0;
            $dayVerseCount = 0;
            $startVerseTitle = $versesArray[$currentIndex]->verse_title;
            $endVerseTitle = $startVerseTitle;

            while ($currentIndex < $totalVerses && $dayWords < $wordsPerDay) {
                $verse = $versesArray[$currentIndex];
                $dayWords += $verse->word_count;
                $dayVerseCount++;
                $endVerseTitle = $verse->verse_title;
                $currentIndex++;
            }

            $schedule[] = [
                'date' => $date,
                'reading' => $startVerseTitle === $endVerseTitle 
                    ? $startVerseTitle 
                    : $this->formatRange($startVerseTitle, $endVerseTitle),
                'word_count' => $dayWords,
                'verse_count' => $dayVerseCount,
            ];
        }

        return [
            'schedule' => $schedule,
            'total_words' => $totalWords,
            'words_per_day' => $wordsPerDay,
            'total_days' => count($schedule),
        ];
    }

    protected function calculateVerseSchedule(Carbon $startDate, int $totalDays, array $volumes, ?string $beginningVerse): array
    {
        $query = Scripture::whereIn('volume_id', $volumes)->orderBy('verse_id');

        if ($beginningVerse) {
            $startVerse = Scripture::where('verse_title', $beginningVerse)->first();
            if ($startVerse) {
                $query->where('verse_id', '>=', $startVerse->verse_id);
            }
        }

        $verses = $query->get();

        if ($verses->isEmpty()) {
            return $this->emptyResult();
        }

        $totalWords = $verses->sum('word_count');
        $wordsPerDay = (int) ceil($totalWords / $totalDays);

        $schedule = [];
        $currentIndex = 0;
        $versesArray = $verses->values()->all();
        $totalVerses = count($versesArray);

        for ($day = 0; $day < $totalDays && $currentIndex < $totalVerses; $day++) {
            $date = $startDate->copy()->addDays($day)->toDateString();
            $dayWords = 0;
            $dayVerses = 0;
            $startVerseTitle = $versesArray[$currentIndex]->verse_title;
            $endVerseTitle = $startVerseTitle;

            while ($currentIndex < $totalVerses && $dayWords < $wordsPerDay) {
                $verse = $versesArray[$currentIndex];
                $dayWords += $verse->word_count;
                $dayVerses++;
                $endVerseTitle = $verse->verse_title;
                $currentIndex++;
            }

            $schedule[] = [
                'date' => $date,
                'reading' => $startVerseTitle === $endVerseTitle 
                    ? $startVerseTitle 
                    : $this->formatRange($startVerseTitle, $endVerseTitle),
                'word_count' => $dayWords,
                'verse_count' => $dayVerses,
            ];
        }

        return [
            'schedule' => $schedule,
            'total_words' => $totalWords,
            'words_per_day' => $wordsPerDay,
            'total_days' => count($schedule),
        ];
    }

    protected function calculateChapterSchedule(Carbon $startDate, int $totalDays, array $volumes, ?string $beginningChapter): array
    {
        // Order by MIN(id) to maintain canonical scripture order within each volume
        $chapters = Scripture::whereIn('volume_id', $volumes)
            ->selectRaw('book_title, chapter, volume_id, SUM(word_count) as word_count, COUNT(*) as verse_count, MIN(id) as first_verse_id')
            ->groupBy('book_title', 'chapter', 'volume_id')
            ->orderBy('volume_id')
            ->orderBy('first_verse_id')
            ->get();

        if ($chapters->isEmpty()) {
            return $this->emptyResult();
        }

        $chaptersArray = $chapters->values()->all();
        $startIndex = 0;

        if ($beginningChapter) {
            foreach ($chaptersArray as $index => $chapter) {
                $chapterTitle = "{$chapter->book_title} {$chapter->chapter}";
                if ($chapterTitle === $beginningChapter) {
                    $startIndex = $index;
                    break;
                }
            }
            $chaptersArray = array_slice($chaptersArray, $startIndex);
        }

        $totalWords = array_sum(array_map(fn($c) => $c->word_count, $chaptersArray));
        $wordsPerDay = (int) ceil($totalWords / $totalDays);

        $schedule = [];
        $currentIndex = 0;
        $totalChapters = count($chaptersArray);

        for ($day = 0; $day < $totalDays && $currentIndex < $totalChapters; $day++) {
            $date = $startDate->copy()->addDays($day)->toDateString();
            $dayWords = 0;
            $dayChapters = [];

            while ($currentIndex < $totalChapters && $dayWords < $wordsPerDay) {
                $chapter = $chaptersArray[$currentIndex];
                $dayWords += $chapter->word_count;
                $dayChapters[] = "{$chapter->book_title} {$chapter->chapter}";
                $currentIndex++;
            }

            if (!empty($dayChapters)) {
                $schedule[] = [
                    'date' => $date,
                    'reading' => count($dayChapters) === 1 
                        ? $dayChapters[0] 
                        : $this->formatChapterRange($dayChapters),
                    'word_count' => $dayWords,
                    'chapter_count' => count($dayChapters),
                ];
            }
        }

        return [
            'schedule' => $schedule,
            'total_words' => $totalWords,
            'words_per_day' => $wordsPerDay,
            'total_days' => count($schedule),
        ];
    }

    protected function formatRange(string $start, string $end): string
    {
        preg_match('/^(.+?)\s+(\d+):(\d+)$/', $start, $startMatch);
        preg_match('/^(.+?)\s+(\d+):(\d+)$/', $end, $endMatch);

        if (!$startMatch || !$endMatch) {
            return "$start - $end";
        }

        $startBook = $startMatch[1];
        $startChapter = $startMatch[2];
        $startVerse = $startMatch[3];
        $endBook = $endMatch[1];
        $endChapter = $endMatch[2];
        $endVerse = $endMatch[3];

        if ($startBook === $endBook && $startChapter === $endChapter) {
            return "{$startBook} {$startChapter}:{$startVerse}-{$endVerse}";
        }

        if ($startBook === $endBook) {
            return "{$startBook} {$startChapter}:{$startVerse} - {$endChapter}:{$endVerse}";
        }

        return "{$start} - {$end}";
    }

    protected function formatChapterRange(array $chapters): string
    {
        if (count($chapters) <= 2) {
            return implode(', ', $chapters);
        }

        $first = $chapters[0];
        $last = $chapters[count($chapters) - 1];

        preg_match('/^(.+?)\s+(\d+)$/', $first, $firstMatch);
        preg_match('/^(.+?)\s+(\d+)$/', $last, $lastMatch);

        if ($firstMatch && $lastMatch && $firstMatch[1] === $lastMatch[1]) {
            return "{$firstMatch[1]} {$firstMatch[2]}-{$lastMatch[2]}";
        }

        return "{$first} - {$last}";
    }

    protected function emptyResult(): array
    {
        return [
            'schedule' => [],
            'total_words' => 0,
            'words_per_day' => 0,
            'total_days' => 0,
        ];
    }
}
