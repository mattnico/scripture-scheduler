<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Models\Scripture;
use App\Services\ScheduleCalculator;
use Carbon\Carbon;
use Livewire\Component;

class PlanViewer extends Component
{
    public Plan $plan;

    public function mount(Plan $plan): void
    {
        $this->plan = $plan->load('progress');
    }

    public function toggleProgress(string $date): void
    {
        if ($this->plan->user_id !== auth()->id()) {
            return;
        }
        
        $this->plan->toggleProgress($date);
        $this->plan->load('progress');
    }

    public function generateShareLink(): void
    {
        if ($this->plan->user_id !== auth()->id()) {
            return;
        }
        
        $this->plan->generatePublicToken();
        $this->plan->refresh();
    }

    public function revokeShareLink(): void
    {
        if ($this->plan->user_id !== auth()->id()) {
            return;
        }
        
        $this->plan->revokePublicAccess();
        $this->plan->refresh();
    }

    public function recalculate(): void
    {
        if ($this->plan->user_id !== auth()->id()) {
            return;
        }
        
        $today = now()->toDateString();
        $schedule = $this->plan->schedule ?? [];
        $endDate = $this->plan->end_date;
        
        // Find the last completed reading
        $completedDates = $this->plan->progress
            ->where('completed', true)
            ->pluck('reading_date')
            ->map(fn($d) => $d->toDateString())
            ->toArray();
        
        // Find completed entries and remaining entries
        $completedEntries = [];
        $remainingContent = [];
        $lastCompletedReading = null;
        
        foreach ($schedule as $entry) {
            if (in_array($entry['date'], $completedDates)) {
                $completedEntries[] = $entry;
                $lastCompletedReading = $entry['reading'];
            } else {
                $remainingContent[] = $entry;
            }
        }
        
        // Calculate remaining days from today to end date
        $remainingDays = Carbon::parse($today)->diffInDays($endDate) + 1;
        
        if ($remainingDays < 1 || empty($remainingContent)) {
            return;
        }
        
        // Recalculate schedule for remaining content
        $calculator = new ScheduleCalculator();
        
        // Determine what content still needs to be read
        $beginningVerse = null;
        if ($lastCompletedReading) {
            $beginningVerse = $this->calculateNextStartingVerse(
                $lastCompletedReading,
                $this->plan->scheduling_method
            );
        } elseif ($this->plan->beginning_verse) {
            $beginningVerse = $this->plan->beginning_verse;
        }
        
        // Check if this is a curriculum-based plan
        if ($this->plan->enrollment_id && $this->plan->enrollment) {
            $curriculum = $this->plan->enrollment->curriculum;
            if ($curriculum) {
                $result = $calculator->calculate([
                    'start_date' => $today,
                    'end_date' => $endDate->toDateString(),
                    'scheduling_method' => $this->plan->scheduling_method,
                    'required_chapters' => $curriculum->requiredChapters,
                ]);
            } else {
                return;
            }
        } else {
            // Standard volume-based plan
            $result = $calculator->calculate([
                'start_date' => $today,
                'end_date' => $endDate->toDateString(),
                'volumes' => $this->plan->volumes ?? [],
                'scheduling_method' => $this->plan->scheduling_method,
                'beginning_verse' => $beginningVerse,
            ]);
        }
        
        // Merge completed entries with new schedule
        $newSchedule = array_merge($completedEntries, $result['schedule']);
        
        // Update the plan
        $this->plan->update([
            'schedule' => $newSchedule,
            'total_days' => count($newSchedule),
            'words_per_day' => $result['words_per_day'],
        ]);
        
        $this->plan->refresh();
        
        session()->flash('success', 'Schedule recalculated! Your remaining readings have been redistributed.');
    }

    protected function calculateNextStartingVerse(string $lastReading, string $schedulingMethod): ?string
    {
        $scripture = Scripture::query();
        
        if ($schedulingMethod === 'chapter') {
            // Handle chapter-based: "Book Chapter" or "Book Chapter-Chapter" or "Book Chapter - Book Chapter"
            if (preg_match('/^(.+?)\s+(\d+)(?:-\d+)?$/', $lastReading, $matches)) {
                $bookTitle = $matches[1];
                $chapter = (int) $matches[2];
                
                // If it's a range like "1 Nephi 1-3", get the last chapter
                if (preg_match('/(\d+)-(\d+)$/', $lastReading, $rangeMatch)) {
                    $chapter = (int) $rangeMatch[2];
                }
                
                // Find next chapter
                $next = Scripture::where('book_title', $bookTitle)
                    ->where('chapter', $chapter + 1)
                    ->first();
                
                if ($next) {
                    return "{$next->book_title} {$next->chapter}";
                }
                
                // Next book in selected volumes
                $nextBook = Scripture::whereIn('volume_id', $this->plan->volumes ?? [])
                    ->where(function($q) use ($bookTitle, $chapter) {
                        $q->where('book_title', '!=', $bookTitle)
                            ->orWhere('chapter', '>', $chapter);
                    })
                    ->orderBy('verse_id')
                    ->first();
                
                if ($nextBook) {
                    return "{$nextBook->book_title} {$nextBook->chapter}";
                }
            }
        } else {
            // Handle verse-based: "Book Chapter:Verse" or ranges
            if (preg_match('/^(.+?)\s+(\d+):(\d+)(?:-\d+)?/', $lastReading, $matches)) {
                $bookTitle = $matches[1];
                $chapter = (int) $matches[2];
                $verse = (int) $matches[3];
                
                // If it's a range, get the end verse
                if (preg_match('/(\d+):(\d+)-(\d+)$/', $lastReading, $rangeMatch)) {
                    $verse = (int) $rangeMatch[3];
                } elseif (preg_match('/(\d+):(\d+)\s*-\s*(\d+):(\d+)$/', $lastReading, $rangeMatch)) {
                    $chapter = (int) $rangeMatch[3];
                    $verse = (int) $rangeMatch[4];
                }
                
                $current = Scripture::where('book_title', $bookTitle)
                    ->where('chapter', $chapter)
                    ->where('verse', $verse)
                    ->first();
                
                if ($current) {
                    $next = Scripture::whereIn('volume_id', $this->plan->volumes ?? [])
                        ->where('verse_id', '>', $current->verse_id)
                        ->orderBy('verse_id')
                        ->first();
                    
                    if ($next) {
                        return $next->verse_title;
                    }
                }
            }
        }
        
        return null;
    }

    public function getTodaysReading(): ?array
    {
        return $this->plan->todays_reading;
    }

    public function getDaysAheadBehind(): int
    {
        $today = now()->toDateString();
        $schedule = $this->plan->schedule ?? [];
        
        $expectedCompleted = 0;
        foreach ($schedule as $entry) {
            if ($entry['date'] <= $today) {
                $expectedCompleted++;
            }
        }
        
        $actualCompleted = $this->plan->completed_days;
        
        return $actualCompleted - $expectedCompleted;
    }

    public function render()
    {
        $schedule = $this->plan->schedule ?? [];
        $today = now()->toDateString();
        
        $completedDates = $this->plan->progress
            ->where('completed', true)
            ->pluck('reading_date')
            ->map(fn($d) => $d->toDateString())
            ->toArray();

        return view('livewire.plan-viewer', [
            'schedule' => $schedule,
            'today' => $today,
            'completedDates' => $completedDates,
            'todaysReading' => $this->getTodaysReading(),
            'daysAheadBehind' => $this->getDaysAheadBehind(),
            'progressPercentage' => $this->plan->progress_percentage,
            'completedDays' => $this->plan->completed_days,
            'totalDays' => count($schedule),
        ]);
    }
}
