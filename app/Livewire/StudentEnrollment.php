<?php

namespace App\Livewire;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Plan;
use App\Services\ScheduleCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StudentEnrollment extends Component
{
    public string $enrollmentCode = '';
    public ?Curriculum $curriculum = null;
    public bool $showCurriculum = false;
    public string $errorMessage = '';
    
    public string $startDate = '';
    public string $endDate = '';
    public string $schedulingMethod = 'chapter';

    public function mount(): void
    {
        $this->startDate = now()->toDateString();
    }

    public function lookupCode(): void
    {
        $this->errorMessage = '';
        $this->curriculum = null;
        $this->showCurriculum = false;

        if (strlen($this->enrollmentCode) < 3) {
            $this->errorMessage = 'Please enter a valid enrollment code.';
            return;
        }

        $this->curriculum = Curriculum::with('requiredChapters')
            ->where('enrollment_code', strtoupper($this->enrollmentCode))
            ->first();

        if (!$this->curriculum) {
            $this->errorMessage = 'No curriculum found with that code. Please check with your teacher.';
            return;
        }

        $existingEnrollment = Enrollment::where('user_id', Auth::id())
            ->where('curriculum_id', $this->curriculum->id)
            ->first();

        if ($existingEnrollment) {
            $this->errorMessage = 'You are already enrolled in this curriculum.';
            return;
        }

        $this->endDate = $this->curriculum->deadline->toDateString();
        $this->showCurriculum = true;
    }

    public function enroll()
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate|before_or_equal:' . $this->curriculum->deadline->toDateString(),
        ], [
            'endDate.before_or_equal' => 'Your end date cannot be after the curriculum deadline (' . $this->curriculum->deadline->format('M j, Y') . ').',
        ]);

        $enrollment = Enrollment::create([
            'user_id' => Auth::id(),
            'curriculum_id' => $this->curriculum->id,
            'enrolled_at' => now(),
        ]);

        $requiredChapters = $this->curriculum->requiredChapters;
        
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
            'enrollment_id' => $enrollment->id,
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
            ->with('success', 'Successfully enrolled! Your reading plan is ready.');
    }

    public function render()
    {
        return view('livewire.student-enrollment');
    }
}
