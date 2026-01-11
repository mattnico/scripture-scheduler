<?php

namespace App\Livewire;

use App\Models\Plan;
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
        $this->plan->toggleProgress($date);
        $this->plan->refresh();
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
