<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Your Reading Plan</h1>
        <p class="text-gray-600">
            {{ $plan->start_date->format('M j, Y') }} - {{ $plan->end_date->format('M j, Y') }}
        </p>
    </div>

    @if ($todaysReading)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-blue-900">Today's Reading</h2>
                    <p class="text-2xl font-bold text-blue-700 mt-1">{{ $todaysReading['reading'] }}</p>
                    <p class="text-sm text-blue-600 mt-1">
                        {{ number_format($todaysReading['word_count']) }} words
                    </p>
                </div>
                <button 
                    wire:click="toggleProgress('{{ $today }}')"
                    class="px-4 py-2 rounded-lg font-medium {{ in_array($today, $completedDates) ? 'bg-green-600 text-white' : 'bg-white border border-blue-300 text-blue-700 hover:bg-blue-50' }}"
                >
                    {{ in_array($today, $completedDates) ? 'Completed!' : 'Mark Complete' }}
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $completedDays }}</div>
            <div class="text-sm text-gray-500">Days Completed</div>
        </div>
        <div class="bg-white shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-gray-900">{{ $totalDays - $completedDays }}</div>
            <div class="text-sm text-gray-500">Days Remaining</div>
        </div>
        <div class="bg-white shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold {{ $daysAheadBehind >= 0 ? 'text-green-600' : 'text-red-600' }}">
                {{ $daysAheadBehind >= 0 ? '+' : '' }}{{ $daysAheadBehind }}
            </div>
            <div class="text-sm text-gray-500">Days {{ $daysAheadBehind >= 0 ? 'Ahead' : 'Behind' }}</div>
        </div>
        <div class="bg-white shadow-sm rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $progressPercentage }}%</div>
            <div class="text-sm text-gray-500">Progress</div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-4 mb-6">
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div 
                class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                style="width: {{ $progressPercentage }}%;"
            ></div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b">
            <h2 class="font-semibold text-gray-900">Full Schedule</h2>
        </div>
        <div class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
            @foreach ($schedule as $entry)
                @php
                    $isCompleted = in_array($entry['date'], $completedDates);
                    $isToday = $entry['date'] === $today;
                    $isPast = $entry['date'] < $today;
                @endphp
                <div class="flex items-center justify-between px-4 py-3 {{ $isToday ? 'bg-blue-50' : '' }} {{ $isCompleted ? 'bg-green-50' : '' }}">
                    <div class="flex items-center gap-4">
                        <button 
                            wire:click="toggleProgress('{{ $entry['date'] }}')"
                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center {{ $isCompleted ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 hover:border-green-400' }}"
                        >
                            @if ($isCompleted)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </button>
                        <div>
                            <div class="font-medium text-gray-900 {{ $isCompleted ? 'line-through text-gray-500' : '' }}">
                                {{ $entry['reading'] }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($entry['date'])->format('D, M j') }}
                                @if ($isToday)
                                    <span class="ml-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">Today</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ number_format($entry['word_count']) }} words
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6 flex gap-4">
        <a href="{{ route('plans.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
            Back to Plans
        </a>
    </div>
</div>
