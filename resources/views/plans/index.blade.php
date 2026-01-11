<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Reading Plans') }}
            </h2>
            <a href="{{ route('plans.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Create New Plan
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($plans->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No reading plans</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new reading plan.</p>
                        <div class="mt-6">
                            <a href="{{ route('plans.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Create Reading Plan
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($plans as $plan)
                            <li class="p-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('plans.show', $plan) }}" class="block">
                                            <h3 class="text-lg font-medium text-gray-900 hover:text-blue-600">
                                                {{ implode(', ', array_map(fn($v) => \App\Livewire\PlanGenerator::VOLUMES[$v] ?? $v, $plan->volumes)) }}
                                            </h3>
                                        </a>
                                        <div class="mt-1 flex items-center gap-4 text-sm text-gray-500">
                                            <span>
                                                {{ $plan->start_date->format('M j, Y') }} - {{ $plan->end_date->format('M j, Y') }}
                                            </span>
                                            <span>
                                                {{ number_format($plan->total_words) }} words
                                            </span>
                                            <span>
                                                {{ $plan->scheduling_method === 'verse' ? 'Verse-level' : 'Chapter-level' }}
                                            </span>
                                        </div>
                                        <div class="mt-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-xs">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $plan->progress_percentage }}%;"></div>
                                                </div>
                                                <span class="text-sm text-gray-600">{{ $plan->progress_percentage }}%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        <a href="{{ route('plans.show', $plan) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            View
                                        </a>
                                        <form action="{{ route('plans.destroy', $plan) }}" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
