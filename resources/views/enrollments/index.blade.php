<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Enrollments') }}
            </h2>
            <a href="{{ route('enrollments.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Join Curriculum
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($enrollments->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No enrollments</h3>
                        <p class="mt-1 text-sm text-gray-500">Join a curriculum using the enrollment code from your teacher.</p>
                        <div class="mt-6">
                            <a href="{{ route('enrollments.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Join Curriculum
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($enrollments as $enrollment)
                            <li class="p-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            {{ $enrollment->curriculum->name }}
                                        </h3>
                                        <div class="mt-1 flex items-center gap-4 text-sm text-gray-500">
                                            <span>
                                                Deadline: {{ $enrollment->curriculum->deadline->format('M j, Y') }}
                                            </span>
                                            <span>
                                                Enrolled: {{ $enrollment->enrolled_at->format('M j, Y') }}
                                            </span>
                                        </div>
                                        @if ($enrollment->plan)
                                            <div class="mt-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-1 bg-gray-200 rounded-full h-2 max-w-xs">
                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $enrollment->plan->progress_percentage }}%;"></div>
                                                    </div>
                                                    <span class="text-sm text-gray-600">{{ $enrollment->plan->progress_percentage }}%</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        @if ($enrollment->plan)
                                            <a href="{{ route('plans.show', $enrollment->plan) }}" 
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 rounded-md text-sm font-medium text-white hover:bg-blue-700">
                                                View Plan
                                            </a>
                                        @endif
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
