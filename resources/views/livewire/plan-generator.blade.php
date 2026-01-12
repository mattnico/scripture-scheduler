<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            <span class="text-amber-600 dark:text-amber-500">Word-Based</span> Scripture Scheduler
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Create a personalized reading schedule balanced by actual word count for consistent daily portions.
        </p>
    </div>

    <form wire:submit="createPlan" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reading Schedule</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        When will you start reading?
                    </label>
                    <input 
                        type="date" 
                        id="startDate"
                        wire:model.live="startDate"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                    @error('startDate')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        When will you finish reading?
                    </label>
                    <input 
                        type="date" 
                        id="endDate"
                        wire:model.live="endDate"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                    @error('endDate')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Scripture Selection</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Which volumes do you want to read?
                    </label>
                    <div class="space-y-2">
                        @foreach ($volumes as $id => $name)
                            <label class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    wire:click="toggleVolume({{ $id }})"
                                    @checked(in_array($id, $selectedVolumes))
                                    class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 bg-white dark:bg-gray-700"
                                >
                                <span class="ml-2 text-gray-700 dark:text-gray-300">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedVolumes')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Starting Point (optional)
                    </label>
                    
                    @if ($beginningVerse)
                        <div class="flex items-center gap-2 mb-2 p-2 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <span class="font-medium text-blue-800 dark:text-blue-200">{{ $beginningVerse }}</span>
                            <button 
                                type="button"
                                wire:click="clearBeginningVerse"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                            >
                                &times;
                            </button>
                        </div>
                    @endif
                    
                    <div class="relative">
                        <input 
                            type="text"
                            wire:model.live.debounce.300ms="searchQuery"
                            placeholder="{{ $schedulingMethod === 'chapter' ? 'e.g., 1 Nephi 3' : 'e.g., 1 Nephi 3:7' }}"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                        >
                        
                        @if (count($searchResults) > 0)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach ($searchResults as $index => $result)
                                    <button 
                                        type="button"
                                        wire:click="selectVerse({{ $index }})"
                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white"
                                    >
                                        {{ $result['display'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Already started? Enter the verse you'll read next.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Scheduling Method</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none {{ $schedulingMethod === 'verse' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-600' : 'border-gray-300 dark:border-gray-600' }}">
                    <input 
                        type="radio" 
                        wire:model.live="schedulingMethod" 
                        value="verse"
                        class="sr-only"
                    >
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Verse-level</span>
                            <span class="mt-1 text-sm text-gray-500 dark:text-gray-400">Precise word count balancing</span>
                        </span>
                    </span>
                    @if ($schedulingMethod === 'verse')
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </label>
                
                <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none {{ $schedulingMethod === 'chapter' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 dark:border-blue-600' : 'border-gray-300 dark:border-gray-600' }}">
                    <input 
                        type="radio" 
                        wire:model.live="schedulingMethod" 
                        value="chapter"
                        class="sr-only"
                    >
                    <span class="flex flex-1">
                        <span class="flex flex-col">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white">Chapter-level</span>
                            <span class="mt-1 text-sm text-gray-500 dark:text-gray-400">Balanced by complete chapters</span>
                        </span>
                    </span>
                    @if ($schedulingMethod === 'chapter')
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </label>
            </div>
        </div>

        @if ($showPreview)
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-3">Plan Preview</h3>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $previewStats['total_words'] }}</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Total Words</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $previewStats['words_per_day'] }}</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Words/Day</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $previewStats['total_days'] }}</div>
                        <div class="text-sm text-green-600 dark:text-green-400">Days</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="flex gap-4">
            <button 
                type="button"
                wire:click="preview"
                class="flex-1 px-6 py-3 border border-blue-600 dark:border-blue-500 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30 font-medium"
            >
                Preview Plan
            </button>
            <button 
                type="submit"
                class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
            >
                Create Reading Plan
            </button>
        </div>
    </form>
</div>
