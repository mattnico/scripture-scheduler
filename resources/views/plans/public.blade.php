<!DOCTYPE html>
<html lang="en" x-data x-init="
    Alpine.store('theme', {
        dark: localStorage.getItem('darkMode') === 'true',
        toggle() {
            this.dark = !this.dark;
            localStorage.setItem('darkMode', this.dark);
            document.documentElement.classList.toggle('dark', this.dark);
        }
    });
">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Plan - {{ $plan->start_date->format('M j, Y') }}</title>
    <!-- Dark mode - must run before CSS loads to prevent FOUC -->
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen py-8 transition-colors">
    <div class="max-w-4xl mx-auto px-4">
        <div class="flex justify-end mb-4">
            <button 
                @click="$store.theme.toggle()"
                class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                title="Toggle dark mode"
            >
                <svg x-show="!$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                <svg x-show="$store.theme.dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Scripture Reading Plan</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ $plan->start_date->format('F j, Y') }} - {{ $plan->end_date->format('F j, Y') }}
            </p>
            
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($plan->total_words) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Words</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($plan->words_per_day) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Words/Day</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ count($plan->schedule ?? []) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Days</div>
                </div>
            </div>
        </div>

        @php
            $todaysReading = $plan->todays_reading;
            $today = now()->toDateString();
        @endphp

        @if ($todaysReading)
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Today's Reading</h2>
                <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ $todaysReading['reading'] }}</p>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                    {{ number_format($todaysReading['word_count']) }} words
                </p>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b dark:border-gray-600">
                <h2 class="font-semibold text-gray-900 dark:text-white">Full Schedule</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                @foreach ($plan->schedule ?? [] as $entry)
                    @php
                        $isToday = $entry['date'] === $today;
                    @endphp
                    <div class="flex items-center justify-between px-4 py-3 {{ $isToday ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $entry['reading'] }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($entry['date'])->format('D, M j, Y') }}
                                @if ($isToday)
                                    <span class="ml-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-200 text-xs rounded-full">Today</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ number_format($entry['word_count']) }} words
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Want your own reading plan?</p>
            <a href="{{ route('plans.create') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Create one here</a>
        </div>
    </div>
</body>
</html>
