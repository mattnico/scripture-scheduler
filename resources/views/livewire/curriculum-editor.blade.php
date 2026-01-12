<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
        {{ $curriculum ? 'Edit Curriculum' : 'Create Curriculum' }}
    </h1>

    @if ($showSuccess)
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded mb-6">
            {{ $successMessage }}
            @if ($curriculum && $curriculum->enrollment_code)
                <div class="mt-2">
                    <span class="font-mono text-lg bg-green-200 dark:bg-green-800 px-3 py-1 rounded">
                        {{ $curriculum->enrollment_code }}
                    </span>
                    <button 
                        onclick="navigator.clipboard.writeText('{{ $curriculum->enrollment_code }}')"
                        class="ml-2 text-sm underline"
                    >
                        Copy
                    </button>
                </div>
            @endif
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Curriculum Name
            </label>
            <input 
                type="text" 
                id="name"
                wire:model="name"
                placeholder="e.g., Seminary 2026 - Old Testament"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
            >
            @error('name')
                <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Deadline (students must finish by this date)
            </label>
            <input 
                type="date" 
                id="deadline"
                wire:model="deadline"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
            >
            @error('deadline')
                <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="enrollmentCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Enrollment Code (optional)
            </label>
            <input 
                type="text" 
                id="enrollmentCode"
                wire:model="enrollmentCode"
                placeholder="Leave blank for auto-generated code"
                maxlength="20"
                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono uppercase bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
            >
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                @if ($curriculum && $curriculum->enrollment_code)
                    Current code: <span class="font-mono font-medium">{{ $curriculum->enrollment_code }}</span>
                @else
                    A 6-character code will be generated if left blank.
                @endif
            </p>
            @error('enrollmentCode')
                <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Required Chapters
            </label>
            
            <div class="flex gap-2 mb-4">
                <div class="relative flex-1">
                    <input 
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search chapters (e.g., Genesis 1, 1 Nephi, D&C 20)"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                    >
                    
                    @if (count($searchResults) > 0)
                        <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            @foreach ($searchResults as $index => $result)
                                <button 
                                    type="button"
                                    wire:click="selectSearchResult({{ $index }})"
                                    class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 flex justify-between items-center text-gray-900 dark:text-white"
                                >
                                    <span>{{ $result['display'] }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $volumes[$result['volume_id']] ?? '' }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
                <button 
                    type="button"
                    wire:click="toggleBulkImport"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 whitespace-nowrap"
                >
                    {{ $showBulkImport ? 'Cancel Import' : 'Bulk Import' }}
                </button>
            </div>
            
            @if ($showBulkImport)
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Paste chapters (one per line, or comma-separated)
                    </label>
                    <textarea
                        wire:model="bulkImportText"
                        rows="6"
                        placeholder="1 Nephi 1-3
2 Nephi 1, 2 Nephi 2
Mosiah 1-5
Alma 32"
                        class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                    ></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-3">
                        Supports: "Book Chapter", "Book Chapter-Chapter" (ranges)
                    </p>
                    
                    @if (count($importErrors) > 0)
                        <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded text-sm text-red-700 dark:text-red-300">
                            @foreach ($importErrors as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif
                    
                    <button 
                        type="button"
                        wire:click="processBulkImport"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Import Chapters
                    </button>
                </div>
            @endif

            @error('chapters')
                <p class="text-red-600 dark:text-red-400 text-sm mb-2">{{ $message }}</p>
            @enderror

            @if (count($chapters) > 0)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Chapter(s)</th>
                                <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">Volume</th>
                                <th class="px-4 py-2 text-right text-sm font-medium text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($chapters as $index => $chapter)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">
                                        <span class="font-medium">{{ $chapter['book_title'] }}</span>
                                        <span>
                                            {{ $chapter['chapter_start'] }}@if($chapter['chapter_end'])-{{ $chapter['chapter_end'] }}@endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $volumes[$chapter['volume_id']] ?? 'Unknown' }}
                                    </td>
                                    <td class="px-4 py-3 text-right space-x-1">
                                        <button 
                                            type="button"
                                            wire:click="moveChapterUp({{ $index }})"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 px-2"
                                            @if($index === 0) disabled @endif
                                        >
                                            &uarr;
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="moveChapterDown({{ $index }})"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 px-2"
                                            @if($index === count($chapters) - 1) disabled @endif
                                        >
                                            &darr;
                                        </button>
                                        <button 
                                            type="button"
                                            wire:click="removeChapter({{ $index }})"
                                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 px-2"
                                        >
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ count($chapters) }} chapter group(s) selected
                </div>
            @else
                <div class="text-center py-8 bg-gray-50 dark:bg-gray-700 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <p class="text-gray-500 dark:text-gray-400">No chapters selected yet.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Use the search above to add chapters.</p>
                </div>
            @endif
        </div>

        <div class="flex justify-end space-x-4 pt-4">
            <a 
                href="{{ route('curricula.index') }}"
                class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                Cancel
            </a>
            <button 
                type="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            >
                {{ $curriculum ? 'Update Curriculum' : 'Create Curriculum' }}
            </button>
        </div>
    </form>
</div>
