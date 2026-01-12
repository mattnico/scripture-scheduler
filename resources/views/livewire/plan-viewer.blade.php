<div class="max-w-4xl mx-auto">
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Your Reading Plan</h1>
        <p class="text-gray-600 dark:text-gray-400">
            {{ $plan->start_date->format('M j, Y') }} - {{ $plan->end_date->format('M j, Y') }}
        </p>
    </div>

    @if ($todaysReading)
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Today's Reading</h2>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">
                        {{ $todaysReading['reading'] }}
                        @php
                            $todayVolumeId = $plan->volumes[0] ?? 3;
                            $todayScriptureUrl = \App\Services\ScriptureLinkGenerator::generateUrl($todaysReading['reading'], $todayVolumeId);
                        @endphp
                        @if ($todayScriptureUrl)
                            <a 
                                href="{{ $todayScriptureUrl }}" 
                                target="_blank" 
                                rel="noopener"
                                class="ml-2 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex align-middle"
                                title="Read on ChurchofJesusChrist.org"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        @endif
                    </p>
                    <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                        {{ number_format($todaysReading['word_count']) }} words
                    </p>
                </div>
                <button 
                    wire:click="toggleProgress('{{ $today }}')"
                    wire:loading.attr="disabled"
                    wire:target="toggleProgress('{{ $today }}')"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ in_array($today, $completedDates) ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-gray-600' }} disabled:opacity-50"
                >
                    <span wire:loading wire:target="toggleProgress('{{ $today }}')">
                        <svg class="w-4 h-4 inline animate-spin mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Updating...
                    </span>
                    <span wire:loading.remove wire:target="toggleProgress('{{ $today }}')">
                        {{ in_array($today, $completedDates) ? 'Completed!' : 'Mark Complete' }}
                    </span>
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $completedDays }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Days Completed</div>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalDays - $completedDays }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Days Remaining</div>
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold {{ $daysAheadBehind >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                {{ $daysAheadBehind >= 0 ? '+' : '' }}{{ $daysAheadBehind }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Days {{ $daysAheadBehind >= 0 ? 'Ahead' : 'Behind' }}</div>
            @if ($daysAheadBehind < 0)
                <button 
                    wire:click="recalculate"
                    wire:confirm="This will recalculate your remaining readings to fit the remaining time. Continue?"
                    class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline"
                >
                    Catch up
                </button>
            @endif
        </div>
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $progressPercentage }}%</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Progress</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
            <div 
                class="bg-blue-600 dark:bg-blue-500 h-3 rounded-full transition-all duration-300" 
                style="width: {{ $progressPercentage }}%;"
            ></div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
            <h2 class="font-semibold text-gray-900 dark:text-white">Full Schedule</h2>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
            @foreach ($schedule as $entry)
                @php
                    $isCompleted = in_array($entry['date'], $completedDates);
                    $isToday = $entry['date'] === $today;
                    $isPast = $entry['date'] < $today;
                @endphp
                <div class="flex items-center justify-between px-4 py-3 {{ $isToday ? 'bg-blue-50 dark:bg-blue-900/20' : '' }} {{ $isCompleted ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                    <div class="flex items-center gap-4">
                        <button 
                            wire:click="toggleProgress('{{ $entry['date'] }}')"
                            wire:loading.attr="disabled"
                            wire:target="toggleProgress('{{ $entry['date'] }}')"
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-colors {{ $isCompleted ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 dark:border-gray-500 hover:border-green-400 dark:hover:border-green-500' }}"
                        >
                            <span wire:loading wire:target="toggleProgress('{{ $entry['date'] }}')" class="w-3 h-3 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></span>
                            <span wire:loading.remove wire:target="toggleProgress('{{ $entry['date'] }}')">
                                @if ($isCompleted)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </span>
                        </button>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white {{ $isCompleted ? 'line-through text-gray-500 dark:text-gray-400' : '' }}">
                                {{ $entry['reading'] }}
                                @php
                                    $volumeId = $plan->volumes[0] ?? 3;
                                    $scriptureUrl = \App\Services\ScriptureLinkGenerator::generateUrl($entry['reading'], $volumeId);
                                @endphp
                                @if ($scriptureUrl)
                                    <a 
                                        href="{{ $scriptureUrl }}" 
                                        target="_blank" 
                                        rel="noopener"
                                        class="ml-1 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 inline-flex"
                                        title="Read on ChurchofJesusChrist.org"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($entry['date'])->format('D, M j') }}
                                @if ($isToday)
                                    <span class="ml-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-200 text-xs rounded-full">Today</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        {{ number_format($entry['word_count']) }} words
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-4">
        <a href="{{ route('plans.index') }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
            Back to Plans
        </a>
        <a href="{{ route('plans.edit', $plan) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
            Edit Plan
        </a>
        <a href="{{ route('plans.calendar', $plan) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Subscribe to Calendar
        </a>
        <div class="relative" x-data="{ open: false }">
            <button 
                @click="open = !open"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
            >
                <a href="{{ route('plans.export.csv', $plan) }}" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Download CSV
                </a>
                <a href="{{ route('plans.export.table', $plan) }}" target="_blank" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Print Table
                </a>
                <a href="{{ route('plans.export.calendar', $plan) }}" target="_blank" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Print Calendar
                </a>
            </div>
        </div>
        @if ($daysAheadBehind < 0)
            <button 
                wire:click="recalculate"
                wire:confirm="This will recalculate your remaining readings to fit the remaining time. Your completed readings will be preserved. Continue?"
                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Recalculate Schedule
            </button>
        @endif
        
        <div class="relative" x-data="{ open: false, copied: false }">
            <button 
                @click="open = !open"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                Share
            </button>
            <div 
                x-show="open" 
                @click.away="open = false"
                class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 z-50"
            >
                @if ($plan->public_token)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Share this link with others:</p>
                    <div class="flex gap-2 mb-3">
                        <input 
                            type="text" 
                            value="{{ $plan->getPublicUrl() }}" 
                            readonly 
                            class="flex-1 text-xs border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                            x-ref="shareUrl"
                        >
                        <button 
                            @click="navigator.clipboard.writeText($refs.shareUrl.value); copied = true; setTimeout(() => copied = false, 2000)"
                            class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                            x-text="copied ? 'Copied!' : 'Copy'"
                        ></button>
                    </div>
                    <button 
                        wire:click="revokeShareLink"
                        class="text-xs text-red-600 dark:text-red-400 hover:underline"
                    >
                        Revoke public access
                    </button>
                @else
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Generate a public link to share your reading plan (read-only).</p>
                    <button 
                        wire:click="generateShareLink"
                        class="w-full px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
                    >
                        Generate Share Link
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
