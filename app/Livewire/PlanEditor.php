<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Services\ScheduleCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PlanEditor extends Component
{
    public Plan $plan;
    
    public string $startDate = '';
    public string $endDate = '';
    public string $schedulingMethod = 'chapter';
    public array $selectedVolumes = [];
    
    public int $completedCount = 0;
    public ?array $lastCompletedReading = null;
    
    public bool $isCurriculumPlan = false;
    public ?string $curriculumName = null;
    public ?string $curriculumDeadline = null;

    public function mount(Plan $plan): void
    {
        if ($plan->user_id !== Auth::id()) {
            abort(403);
        }

        $this->plan = $plan->load('enrollment.curriculum');
        $this->startDate = $plan->start_date->toDateString();
        $this->endDate = $plan->end_date->toDateString();
        $this->schedulingMethod = $plan->scheduling_method;
        
        // Cast volumes to strings for HTML checkbox value compatibility
        $this->selectedVolumes = array_map('strval', $plan->volumes ?? []);
        
        if ($plan->enrollment_id && $plan->enrollment && $plan->enrollment->curriculum) {
            $this->isCurriculumPlan = true;
            $this->curriculumName = $plan->enrollment->curriculum->name;
            $this->curriculumDeadline = $plan->enrollment->curriculum->deadline->format('F j, Y');
        }
        
        $this->calculateProgress();
    }

    protected function calculateProgress(): void
    {
        $this->completedCount = $this->plan->progress()
            ->where('completed', true)
            ->count();

        if ($this->completedCount > 0) {
            $lastProgress = $this->plan->progress()
                ->where('completed', true)
                ->orderBy('reading_date', 'desc')
                ->first();

            if ($lastProgress) {
                foreach ($this->plan->schedule as $entry) {
                    if ($entry['date'] === $lastProgress->reading_date) {
                        $this->lastCompletedReading = $entry;
                        break;
                    }
                }
            }
        }
    }

    public function updatePlan()
    {
        if ($this->isCurriculumPlan) {
            $this->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date|after:startDate|before_or_equal:' . $this->plan->enrollment->curriculum->deadline->toDateString(),
                'schedulingMethod' => 'required|in:chapter,verse',
            ]);
            
            $calculator = new ScheduleCalculator();
            $result = $calculator->calculate([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'scheduling_method' => $this->schedulingMethod,
                'required_chapters' => $this->plan->enrollment->curriculum->requiredChapters,
            ]);
            
            $this->plan->update([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'scheduling_method' => $this->schedulingMethod,
                'total_words' => $result['total_words'],
                'words_per_day' => $result['words_per_day'],
                'total_days' => $result['total_days'],
                'schedule' => $result['schedule'],
            ]);
        } else {
            $this->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date|after:startDate',
                'schedulingMethod' => 'required|in:chapter,verse',
                'selectedVolumes' => 'required|array|min:1',
            ]);

            $calculator = new ScheduleCalculator();
            
            $params = [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'volumes' => array_map('intval', $this->selectedVolumes),
                'scheduling_method' => $this->schedulingMethod,
            ];

            if ($this->completedCount > 0 && $this->lastCompletedReading) {
                $params['beginning_verse'] = $this->calculateNextStartingVerse(
                    $this->lastCompletedReading['reading'],
                    $this->schedulingMethod
                );
            }

            $result = $calculator->calculate($params);

            $this->plan->update([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'scheduling_method' => $this->schedulingMethod,
                'volumes' => array_map('intval', $this->selectedVolumes),
                'total_words' => $result['total_words'],
                'words_per_day' => $result['words_per_day'],
                'total_days' => $result['total_days'],
                'schedule' => $result['schedule'],
            ]);
        }

        return redirect()->route('plans.show', $this->plan)
            ->with('success', 'Plan updated successfully.');
    }

    protected function calculateNextStartingVerse(string $lastReading, string $schedulingMethod): string
    {
        $scripture = \App\Models\Scripture::query();
        
        if ($schedulingMethod === 'chapter') {
            if (preg_match('/^(.+?)\s+(\d+)$/', $lastReading, $matches)) {
                $bookTitle = $matches[1];
                $chapter = (int) $matches[2];
                
                $next = $scripture
                    ->where('book_title', $bookTitle)
                    ->where('chapter', $chapter + 1)
                    ->first();
                
                if ($next) {
                    return "{$next->book_title} {$next->chapter}";
                }
                
                $nextBook = $scripture
                    ->where('book_title', '!=', $bookTitle)
                    ->whereIn('volume_id', $this->selectedVolumes)
                    ->orderBy('verse_id')
                    ->first();
                
                if ($nextBook) {
                    return "{$nextBook->book_title} {$nextBook->chapter}";
                }
            }
        } else {
            if (preg_match('/^(.+?)\s+(\d+):(\d+)$/', $lastReading, $matches)) {
                $bookTitle = $matches[1];
                $chapter = (int) $matches[2];
                $verse = (int) $matches[3];
                
                $current = $scripture
                    ->where('book_title', $bookTitle)
                    ->where('chapter', $chapter)
                    ->where('verse', $verse)
                    ->first();
                
                if ($current) {
                    $next = $scripture
                        ->whereIn('volume_id', $this->selectedVolumes)
                        ->where('verse_id', '>', $current->verse_id)
                        ->orderBy('verse_id')
                        ->first();
                    
                    if ($next) {
                        return $next->verse_title;
                    }
                }
            }
        }

        return $lastReading;
    }

    public function render()
    {
        return view('livewire.plan-editor');
    }
}
