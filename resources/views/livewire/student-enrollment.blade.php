<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Join a Curriculum</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Enter the enrollment code from your teacher to join their curriculum and create your reading plan.
        </p>
    </div>

    @if (!$showCurriculum)
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <div class="space-y-4">
                <div>
                    <label for="enrollmentCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Enrollment Code
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="enrollmentCode"
                            wire:model="enrollmentCode"
                            wire:keydown.enter="lookupCode"
                            placeholder="e.g., IBYPIX"
                            maxlength="20"
                            class="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-lg font-mono uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                        >
                        <button 
                            type="button"
                            wire:click="lookupCode"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Find
                        </button>
                    </div>
                    @if ($errorMessage)
                        <p class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $errorMessage }}</p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-green-800 dark:text-green-200">{{ $curriculum->name }}</h2>
                        <p class="text-green-700 dark:text-green-300 mt-1">
                            Deadline: <strong>{{ $curriculum->deadline->format('F j, Y') }}</strong>
                        </p>
                        <p class="text-green-600 dark:text-green-400 text-sm mt-1">
                            {{ $curriculum->requiredChapters->count() }} chapter group(s) to read
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Required Chapters</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach ($curriculum->requiredChapters as $chapter)
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

            <form wire:submit="enroll" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create Your Reading Plan</h3>
                
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
                            max="{{ $curriculum->deadline->toDateString() }}"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Must be on or before {{ $curriculum->deadline->format('M j, Y') }}
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
                    <button 
                        type="button"
                        wire:click="$set('showCurriculum', false)"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Back
                    </button>
                    <button 
                        type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                    >
                        Enroll & Create Plan
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
