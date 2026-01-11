<?php

namespace App\Livewire;

use App\Models\Curriculum;
use App\Models\RequiredChapter;
use App\Models\Scripture;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CurriculumEditor extends Component
{
    public ?Curriculum $curriculum = null;
    
    public string $name = '';
    public string $deadline = '';
    public array $chapters = [];
    
    public string $searchQuery = '';
    public array $searchResults = [];
    
    public bool $showSuccess = false;
    public string $successMessage = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'deadline' => 'required|date|after:today',
        'chapters' => 'required|array|min:1',
        'chapters.*.book_title' => 'required|string',
        'chapters.*.chapter_start' => 'required|integer|min:1',
        'chapters.*.chapter_end' => 'nullable|integer|min:1',
        'chapters.*.volume_id' => 'required|integer|between:1,5',
    ];

    protected $messages = [
        'chapters.required' => 'At least one chapter is required.',
        'chapters.min' => 'At least one chapter is required.',
        'deadline.after' => 'The deadline must be a future date.',
    ];

    public function mount(?string $curriculumId = null): void
    {
        if ($curriculumId) {
            $this->curriculum = Curriculum::with('requiredChapters')->findOrFail($curriculumId);
            $this->name = $this->curriculum->name;
            $this->deadline = $this->curriculum->deadline->format('Y-m-d');
            $this->chapters = $this->curriculum->requiredChapters->map(fn ($ch) => [
                'id' => $ch->id,
                'book_title' => $ch->book_title,
                'chapter_start' => $ch->chapter_start,
                'chapter_end' => $ch->chapter_end,
                'volume_id' => $ch->volume_id,
            ])->toArray();
        }
    }

    public function updatedSearchQuery(): void
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = Scripture::query()
            ->selectRaw('DISTINCT book_title, chapter, volume_id')
            ->where('book_title', 'LIKE', "%{$this->searchQuery}%")
            ->orWhere('verse_title', 'LIKE', "{$this->searchQuery}%")
            ->orderBy('volume_id')
            ->orderBy('book_title')
            ->orderBy('chapter')
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'display' => "{$s->book_title} {$s->chapter}",
                'book_title' => $s->book_title,
                'chapter' => $s->chapter,
                'volume_id' => $s->volume_id,
            ])
            ->unique('display')
            ->values()
            ->toArray();
    }

    public function addChapter(string $bookTitle, int $chapter, int $volumeId): void
    {
        $this->chapters[] = [
            'book_title' => $bookTitle,
            'chapter_start' => $chapter,
            'chapter_end' => null,
            'volume_id' => $volumeId,
        ];
        
        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function addChapterRange(string $bookTitle, int $startChapter, int $endChapter, int $volumeId): void
    {
        $this->chapters[] = [
            'book_title' => $bookTitle,
            'chapter_start' => $startChapter,
            'chapter_end' => $endChapter,
            'volume_id' => $volumeId,
        ];
        
        $this->searchQuery = '';
        $this->searchResults = [];
    }

    public function removeChapter(int $index): void
    {
        unset($this->chapters[$index]);
        $this->chapters = array_values($this->chapters);
    }

    public function moveChapterUp(int $index): void
    {
        if ($index > 0) {
            $temp = $this->chapters[$index - 1];
            $this->chapters[$index - 1] = $this->chapters[$index];
            $this->chapters[$index] = $temp;
        }
    }

    public function moveChapterDown(int $index): void
    {
        if ($index < count($this->chapters) - 1) {
            $temp = $this->chapters[$index + 1];
            $this->chapters[$index + 1] = $this->chapters[$index];
            $this->chapters[$index] = $temp;
        }
    }

    public function save(): void
    {
        $this->validate();

        if ($this->curriculum) {
            $this->curriculum->update([
                'name' => $this->name,
                'deadline' => $this->deadline,
            ]);
            
            $this->curriculum->requiredChapters()->delete();
        } else {
            $this->curriculum = Curriculum::create([
                'teacher_id' => Auth::id(),
                'name' => $this->name,
                'deadline' => $this->deadline,
            ]);
        }

        foreach ($this->chapters as $index => $chapter) {
            RequiredChapter::create([
                'curriculum_id' => $this->curriculum->id,
                'book_title' => $chapter['book_title'],
                'chapter_start' => $chapter['chapter_start'],
                'chapter_end' => $chapter['chapter_end'],
                'volume_id' => $chapter['volume_id'],
                'sort_order' => $index,
            ]);
        }

        $this->showSuccess = true;
        $this->successMessage = $this->curriculum->wasRecentlyCreated 
            ? "Curriculum created! Enrollment code: {$this->curriculum->enrollment_code}"
            : 'Curriculum updated successfully!';
    }

    public function render()
    {
        return view('livewire.curriculum-editor', [
            'volumes' => RequiredChapter::VOLUMES,
        ]);
    }
}
