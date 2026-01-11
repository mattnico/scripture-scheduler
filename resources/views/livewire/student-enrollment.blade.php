<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Join a Curriculum</h1>
        <p class="mt-2 text-gray-600">
            Enter the enrollment code from your teacher to join their curriculum and create your reading plan.
        </p>
    </div>

    @if (!$showCurriculum)
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="space-y-4">
                <div>
                    <label for="enrollmentCode" class="block text-sm font-medium text-gray-700 mb-1">
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
                            class="flex-1 border border-gray-300 rounded-lg px-4 py-3 text-lg font-mono uppercase focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
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
                        <p class="text-red-600 text-sm mt-2">{{ $errorMessage }}</p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg font-semibold text-green-800">{{ $curriculum->name }}</h2>
                        <p class="text-green-700 mt-1">
                            Deadline: <strong>{{ $curriculum->deadline->format('F j, Y') }}</strong>
                        </p>
                        <p class="text-green-600 text-sm mt-1">
                            {{ $curriculum->requiredChapters->count() }} chapter group(s) to read
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Required Chapters</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach ($curriculum->requiredChapters as $chapter)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                            <span class="font-medium text-gray-900">
                                {{ $chapter->book_title }} {{ $chapter->chapter_start }}@if($chapter->chapter_end)-{{ $chapter->chapter_end }}@endif
                            </span>
                            <span class="text-sm text-gray-500">
                                {{ \App\Models\RequiredChapter::VOLUMES[$chapter->volume_id] ?? '' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <form wire:submit="enroll" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Create Your Reading Plan</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="startDate" class="block text-sm font-medium text-gray-700 mb-1">
                            Start Date
                        </label>
                        <input 
                            type="date" 
                            id="startDate"
                            wire:model="startDate"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        >
                        @error('startDate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="endDate" class="block text-sm font-medium text-gray-700 mb-1">
                            End Date
                        </label>
                        <input 
                            type="date" 
                            id="endDate"
                            wire:model="endDate"
                            max="{{ $curriculum->deadline->toDateString() }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        >
                        <p class="text-sm text-gray-500 mt-1">
                            Must be on or before {{ $curriculum->deadline->format('M j, Y') }}
                        </p>
                        @error('endDate')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Scheduling Method
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer rounded-lg border p-3 {{ $schedulingMethod === 'chapter' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                            <input type="radio" wire:model="schedulingMethod" value="chapter" class="sr-only">
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">Chapter-level</span>
                                <span class="text-xs text-gray-500">Recommended</span>
                            </span>
                        </label>
                        <label class="relative flex cursor-pointer rounded-lg border p-3 {{ $schedulingMethod === 'verse' ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                            <input type="radio" wire:model="schedulingMethod" value="verse" class="sr-only">
                            <span class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">Verse-level</span>
                                <span class="text-xs text-gray-500">Precise</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button 
                        type="button"
                        wire:click="$set('showCurriculum', false)"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
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
