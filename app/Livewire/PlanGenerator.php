<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Models\Scripture;
use App\Services\ScheduleCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PlanGenerator extends Component
{
    public string $startDate = '';
    public string $endDate = '';
    public string $schedulingMethod = 'verse';
    public string $beginningVerse = '';
    public array $selectedVolumes = [3];
    
    public string $searchQuery = '';
    public array $searchResults = [];
    
    public bool $showPreview = false;
    public array $previewStats = [];

    public const VOLUMES = [
        1 => 'Old Testament',
        2 => 'New Testament',
        3 => 'Book of Mormon',
        4 => 'Doctrine and Covenants',
        5 => 'Pearl of Great Price',
    ];

    public function mount(): void
    {
        $this->startDate = now()->toDateString();
        $this->endDate = now()->addYear()->subDay()->toDateString();
    }

    public function updatedSearchQuery(): void
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            return;
        }

        $query = Scripture::whereIn('volume_id', $this->selectedVolumes);

        if ($this->schedulingMethod === 'chapter') {
            $results = $query
                ->selectRaw('DISTINCT book_title, chapter, volume_id')
                ->where('book_title', 'LIKE', "%{$this->searchQuery}%")
                ->orderBy('volume_id')
                ->orderBy('book_title')
                ->orderBy('chapter')
                ->limit(15)
                ->get()
                ->map(fn($s) => [
                    'display' => "{$s->book_title} {$s->chapter}",
                    'value' => "{$s->book_title} {$s->chapter}",
                ]);
        } else {
            $results = $query
                ->where('verse_title', 'LIKE', "%{$this->searchQuery}%")
                ->orderBy('verse_id')
                ->limit(15)
                ->get()
                ->map(fn($s) => [
                    'display' => $s->verse_title,
                    'value' => $s->verse_title,
                ]);
        }

        $this->searchResults = $results->unique('display')->values()->toArray();
    }

    public function selectVerse(int $index): void
    {
        if (isset($this->searchResults[$index])) {
            $this->beginningVerse = $this->searchResults[$index]['value'];
            $this->searchQuery = '';
            $this->searchResults = [];
        }
    }

    public function clearBeginningVerse(): void
    {
        $this->beginningVerse = '';
    }

    public function toggleVolume(int $volumeId): void
    {
        if (in_array($volumeId, $this->selectedVolumes)) {
            $this->selectedVolumes = array_values(array_diff($this->selectedVolumes, [$volumeId]));
        } else {
            $this->selectedVolumes[] = $volumeId;
        }
        
        $this->beginningVerse = '';
        $this->searchResults = [];
        $this->showPreview = false;
    }

    public function preview(): void
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'selectedVolumes' => 'required|array|min:1',
        ]);

        $calculator = new ScheduleCalculator();
        $result = $calculator->calculate([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'volumes' => $this->selectedVolumes,
            'scheduling_method' => $this->schedulingMethod,
            'beginning_verse' => $this->beginningVerse ?: null,
        ]);

        $this->previewStats = [
            'total_words' => number_format($result['total_words']),
            'words_per_day' => number_format($result['words_per_day']),
            'total_days' => $result['total_days'],
        ];
        $this->showPreview = true;
    }

    public function createPlan()
    {
        $this->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'selectedVolumes' => 'required|array|min:1',
        ]);

        $calculator = new ScheduleCalculator();
        $result = $calculator->calculate([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'volumes' => $this->selectedVolumes,
            'scheduling_method' => $this->schedulingMethod,
            'beginning_verse' => $this->beginningVerse ?: null,
        ]);

        $plan = Plan::create([
            'user_id' => Auth::id(),
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'scheduling_method' => $this->schedulingMethod,
            'beginning_verse' => $this->beginningVerse ?: null,
            'volumes' => $this->selectedVolumes,
            'total_words' => $result['total_words'],
            'words_per_day' => $result['words_per_day'],
            'total_days' => $result['total_days'],
            'schedule' => $result['schedule'],
        ]);

        return redirect()->route('plans.show', $plan);
    }

    public function render()
    {
        return view('livewire.plan-generator', [
            'volumes' => self::VOLUMES,
        ]);
    }
}
