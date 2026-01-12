<div class="max-w-2xl mx-auto">
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Reading Plan</h1>
            <a href="{{ route('plans.show', $plan) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                &larr; Back to Plan
            </a>
        </div>
    </div>

    @if ($isCurriculumPlan)
        <div class="bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-700 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-purple-800 dark:text-purple-200">Curriculum Plan: {{ $curriculumName }}</h3>
            <p class="text-purple-700 dark:text-purple-300 text-sm mt-1">
                Deadline: <strong>{{ $curriculumDeadline }}</strong>
            </p>
            <p class="text-purple-600 dark:text-purple-400 text-xs mt-2">
                Scripture volumes are defined by the curriculum and cannot be changed.
            </p>
        </div>
    @endif

    @if ($completedCount > 0)
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 dark:text-blue-200">Your Progress Will Be Preserved</h3>
            <p class="text-blue-700 dark:text-blue-300 mt-1">
                You have completed <strong>{{ $completedCount }}</strong> reading(s) so far.
            </p>
            @if ($lastCompletedReading)
                <p class="text-blue-600 dark:text-blue-400 text-sm mt-1">
                    Last completed: <strong>{{ $lastCompletedReading['reading'] }}</strong>
                    on {{ \Carbon\Carbon::parse($lastCompletedReading['date'])->format('M j, Y') }}
                </p>
                <p class="text-blue-500 dark:text-blue-500 text-xs mt-1">
                    Your updated plan will automatically start from where you left off.
                </p>
            @endif
        </div>
    @endif

    <form wire:submit="updatePlan" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Plan Details</h3>
            
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Plan Name <span class="text-gray-400 font-normal">(optional)</span>
                </label>
                <input 
                    type="text" 
                    id="name"
                    wire:model="name"
                    placeholder="e.g., Book of Mormon 2026"
                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                >
            </div>
            
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
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                    @error('endDate')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        @unless ($isCurriculumPlan)
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Scripture Volumes</h3>
            
            <div class="space-y-2">
                @php
                    $volumes = [
                        1 => 'Old Testament',
                        2 => 'New Testament',
                        3 => 'Book of Mormon',
                        4 => 'Doctrine and Covenants',
                        5 => 'Pearl of Great Price',
                    ];
                @endphp
                
                @foreach ($volumes as $id => $name)
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input 
                            type="checkbox" 
                            wire:model="selectedVolumes" 
                            value="{{ $id }}"
                            class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"
                        >
                        <span class="text-gray-900 dark:text-white">{{ $name }}</span>
                    </label>
                @endforeach
            </div>
            @error('selectedVolumes')
                <p class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>
        @endunless

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Scheduling Method</h3>
            
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
            @error('schedulingMethod')
                <p class="text-red-600 dark:text-red-400 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex gap-4">
            <a href="{{ route('plans.show', $plan) }}"
               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-center">
                Cancel
            </a>
            <button 
                type="submit"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
            >
                Update Plan
            </button>
        </div>
    </form>
</div>
