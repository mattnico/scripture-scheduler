<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Reading Plan</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Create a reading plan for <strong>{{ $enrollment->curriculum->name }}</strong>
        </p>
    </div>

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Required Chapters</h3>
            <div class="space-y-2 max-h-48 overflow-y-auto">
                @foreach ($enrollment->curriculum->requiredChapters as $chapter)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $chapter->book_title }} {{ $chapter->chapter_start }}@if($chapter->chapter_end)-{{ $chapter->chapter_end }}@endif
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ \App\Models\RequiredChapter::VOLUMES[$chapter->volume_id] ?? '' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <form wire:submit="createPlan" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Plan Settings</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Start Date
                    </label>
                    <input 
                        type="date" 
                        id="startDate"
                        wire:model="startDate"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                    @error('startDate')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        End Date
                    </label>
                    <input 
                        type="date" 
                        id="endDate"
                        wire:model="endDate"
                        max="{{ $enrollment->curriculum->deadline->toDateString() }}"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Must be on or before {{ $enrollment->curriculum->deadline->format('M j, Y') }}
                    </p>
                    @error('endDate')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Scheduling Method
                </label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="radio" name="schedulingMethod" wire:model.live="schedulingMethod" value="chapter" class="w-4 h-4 text-blue-600">
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Chapter-level</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Recommended - readings end at chapter boundaries</span>
                            </span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="radio" name="schedulingMethod" wire:model.live="schedulingMethod" value="verse" class="w-4 h-4 text-blue-600">
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Verse-level</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Precise - readings may end mid-chapter</span>
                            </span>
                        </label>
                    </div>
            </div>

            <div class="flex gap-4 pt-4">
                <a href="{{ route('enrollments.index') }}"
                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-center">
                    Cancel
                </a>
                <button 
                    type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                    Create Plan
                </button>
            </div>
        </form>
    </div>
</div>
