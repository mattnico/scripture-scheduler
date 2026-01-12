<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('My Curricula') }}
            </h2>
            <a href="{{ route('curricula.create') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                Create Curriculum
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($curricula->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No curricula</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new curriculum.</p>
                        <div class="mt-6">
                            <a href="{{ route('curricula.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                Create Curriculum
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($curricula as $curriculum)
                            <li class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                                            {{ $curriculum->name }}
                                        </h3>
                                        <div class="mt-1 flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span>
                                                Deadline: {{ $curriculum->deadline->format('M j, Y') }}
                                            </span>
                                            <span>
                                                {{ $curriculum->requiredChapters->count() }} chapter group(s)
                                            </span>
                                        </div>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                Code: <span class="ml-1 font-mono">{{ $curriculum->enrollment_code }}</span>
                                            </span>
                                            <div x-data="{ showQR: false }">
                                                <button 
                                                    @click="showQR = true"
                                                    type="button"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                    </svg>
                                                    QR
                                                </button>
                                                <template x-teleport="body">
                                                    <div 
                                                        x-show="showQR" 
                                                        x-transition.opacity
                                                        @keydown.escape.window="showQR = false"
                                                        class="fixed inset-0 z-50 flex items-center justify-center"
                                                    >
                                                        <div class="absolute inset-0 bg-black/50" @click="showQR = false"></div>
                                                        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 mx-4" @click.stop>
                                                            <div class="flex justify-between items-center mb-4">
                                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $curriculum->name }}</h3>
                                                                <button @click="showQR = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 text-center">Scan to enroll in this curriculum</p>
                                                            <div class="flex justify-center">
                                                                <img 
                                                                    src="{{ $curriculum->qr_code_url }}" 
                                                                    alt="QR Code for {{ $curriculum->name }}"
                                                                    class="w-48 h-48"
                                                                >
                                                            </div>
                                                            <p class="text-lg text-gray-700 dark:text-gray-300 mt-4 text-center font-mono font-bold">{{ $curriculum->enrollment_code }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">{{ $curriculum->enrollment_url }}</p>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 ml-4">
                                        <a href="{{ route('curricula.edit', $curriculum) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            Edit
                                        </a>
                                        <form action="{{ route('curricula.destroy', $curriculum) }}" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this curriculum?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="inline-flex items-center px-3 py-1.5 border border-red-300 dark:border-red-700 rounded-md text-sm font-medium text-red-700 dark:text-red-400 bg-white dark:bg-gray-800 hover:bg-red-50 dark:hover:bg-red-900/30">
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
