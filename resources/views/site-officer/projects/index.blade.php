<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('My Projects') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('employees.import') }}" class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    {{ __('Import Excel') }}
                </a>
                <a href="{{ route('employees.export') }}" class="inline-flex items-center gap-1.5 rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    {{ __('Export to Excel') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto space-y-8 sm:px-6 lg:px-8" x-data="projectSearch()">

            {{-- Real-time search bar --}}
            <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="project-search"
                        x-model="query"
                        @input="filter()"
                        placeholder="{{ __('Type to search projects...') }}"
                        class="block w-full rounded-md border-gray-300 pl-10 pr-28 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:placeholder-gray-500"
                        autocomplete="off"
                    />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 gap-2">
                        <span x-show="query.length > 0" x-cloak class="text-xs text-gray-400" x-text="visibleCount + ' result' + (visibleCount !== 1 ? 's' : '')"></span>
                        <button
                            x-show="query.length > 0"
                            x-cloak
                            @click="query = ''; filter();"
                            type="button"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            title="{{ __('Clear search') }}"
                        >
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div x-show="query.length > 0 && visibleCount === 0" x-cloak class="mt-3 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('No projects match your search.') }}
                </div>
            </div>

            {{-- Project cards --}}
            @forelse ($projects as $project)
                <div class="project-card overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800" data-name="{{ strtolower($project->project_name) }}">
                    <div class="border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $project->project_name }}</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $project->project_code }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('site-officer.projects.show', $project) }}" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                    {{ __('View Details') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        @php
                            $balances = $balancesByProject->get($project->id, collect());
                        @endphp

                        @if ($balances->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials assigned to this project yet.') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material Name') }}</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Unit of Measurement') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Assigned Balance') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Employees Distributed') }}</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Physical Balance') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($balances as $balance)
                                            <tr>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $balance['material']->material_name }}</td>
                                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $balance['material']->unitOfMeasure?->name ?? __('-') }}</td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">{{ number_format($balance['received']) }}</td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">{{ number_format($balance['distributed']) }}</td>
                                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ number_format($balance['available']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('No projects assigned to you.') }}
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <script>
    function projectSearch() {
        return {
            query: '',
            visibleCount: 0,
            init() {
                this.visibleCount = document.querySelectorAll('.project-card').length;
            },
            filter() {
                const q = this.query.toLowerCase().trim();
                const cards = document.querySelectorAll('.project-card');
                let count = 0;
                cards.forEach(card => {
                    const name = card.getAttribute('data-name') || '';
                    const match = q === '' || name.startsWith(q);
                    card.style.display = match ? '' : 'none';
                    if (match) count++;
                });
                this.visibleCount = count;
            }
        };
    }
    </script>
</x-app-layout>
