<?php

namespace App\Livewire;

use App\Models\Enrollment;
use App\Models\Plan;
use App\Services\ScheduleCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EnrollmentPlanCreator extends Component
{
    public Enrollment $enrollment;
    
    public string $startDate = '';
    public string $endDate = '';
    public string $schedulingMethod = 'chapter';

    public function mount(Enrollment $enrollment): void
    {
        if ($enrollment->user_id !== Auth::id()) {
            abort(403);
        }

        if ($enrollment->plan) {
            redirect()->route('plans.show', $enrollment->plan);
            return;
        }

        $this->enrollment = $enrollment;
        $this->startDate = now()->toDateString();
        $this->endDate = $enrollment->curriculum->deadline->toDateString();
    }

    public function createPlan()
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate|before_or_equal:' . $this->enrollment->curriculum->deadline->toDateString(),
            'schedulingMethod' => 'required|in:chapter,verse',
        ]);

        $requiredChapters = $this->enrollment->curriculum->requiredChapters;
        
        $volumes = $requiredChapters
            ->pluck('volume_id')
            ->unique()
            ->values()
            ->toArray();

        $calculator = new ScheduleCalculator();
        $result = $calculator->calculate([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'required_chapters' => $requiredChapters,
            'scheduling_method' => $this->schedulingMethod,
        ]);

        $plan = Plan::create([
            'user_id' => Auth::id(),
            'enrollment_id' => $this->enrollment->id,
            'name' => $this->enrollment->curriculum->name,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'scheduling_method' => $this->schedulingMethod,
            'volumes' => $volumes,
            'total_words' => $result['total_words'],
            'words_per_day' => $result['words_per_day'],
            'total_days' => $result['total_days'],
            'schedule' => $result['schedule'],
        ]);

        return redirect()->route('plans.show', $plan)
            ->with('success', 'Your reading plan is ready.');
    }

    public function render()
    {
        return view('livewire.enrollment-plan-creator');
    }
}
