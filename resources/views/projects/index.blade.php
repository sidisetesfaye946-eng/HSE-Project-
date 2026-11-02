<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Projects') }}</h2>
                @unless ($isAdmin)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('View and manage projects') }}</p>
                @endunless
            </div>
            @if ($isAdmin)
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-offset-gray-900">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    {{ __('Create Project') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <div x-data="projectSearch()">
                {{-- Real-time search bar --}}
                <div class="mb-4 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800">
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
                </div>

                {{-- Projects table --}}
                <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div x-show="query.length > 0 && visibleCount === 0" x-cloak class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No projects match your search.') }}
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full" id="projects-table">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Project Name') }}</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Project Code') }}</th>
                                        @if ($isAdmin)
                                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Site Officer') }}</th>
                                        @endif
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="projects-tbody">
                                    @forelse ($projects as $project)
                                        <tr class="project-row hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors" data-name="{{ strtolower($project->project_name) }}">
                                            <td class="px-6 py-4">
                                                <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $project->project_name }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $project->project_code }}</span>
                                            </td>
                                            @if ($isAdmin)
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $project->siteOfficer?->name ?? '—' }}
                                                </td>
                                            @endif
                                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    @if ($isAdmin)
                                                        <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-200 transition dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50">
                                                            <svg class="h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            {{ __('View') }}
                                                        </a>
                                                        <a href="{{ route('projects.edit', $project) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-100 px-3 py-2 text-xs font-medium text-blue-700 hover:bg-blue-200 transition dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
                                                                <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
                                                            </svg>
                                                            {{ __('Edit') }}
                                                        </a>
                                                        <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this project?') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-100 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-200 transition dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                                                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                                </svg>
                                                                {{ __('Delete') }}
                                                            </button>
                                                        </form>
                                                    @else
                                                        <a href="{{ route('site-officer.projects.show', $project) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-200 transition dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50">
                                                            <svg class="h-4 w-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            {{ __('View') }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="@if ($isAdmin) 4 @else 3 @endif" class="px-6 py-12">
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0018 4.5h-2.25A2.25 2.25 0 0013.5 6.75v10.5A2.25 2.25 0 0015.75 19.5z" />
                                                    </svg>
                                                    <p class="text-center text-sm font-medium text-gray-600 dark:text-gray-400">
                                                        @if ($isAdmin)
                                                            {{ __('No projects created yet.') }}
                                                        @else
                                                            {{ __('No projects assigned to you.') }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($projects->hasPages())
                            <div class="mt-6 border-t border-gray-200 pt-4 dark:border-gray-700">
                                <div class="mb-4 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                                    <span>{{ __('Showing') }} <span class="font-medium">{{ $projects->firstItem() }}</span> {{ __('to') }} <span class="font-medium">{{ $projects->lastItem() }}</span> {{ __('of') }} <span class="font-medium">{{ $projects->total() }}</span> {{ __('projects') }}</span>
                                </div>
                                {{ $projects->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function projectSearch() {
        return {
            query: '',
            visibleCount: 0,
            init() {
                this.visibleCount = document.querySelectorAll('#projects-tbody .project-row').length;
            },
            filter() {
                const q = this.query.toLowerCase().trim();
                const rows = document.querySelectorAll('#projects-tbody .project-row');
                let count = 0;
                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const match = q === '' || name.startsWith(q);
                    row.style.display = match ? '' : 'none';
                    if (match) count++;
                });
                this.visibleCount = count;
            }
        };
    }
    </script>
</x-app-layout>
