<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Site Material Reports') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Material movement across projects and employees') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('material-reports.site.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Export Excel') }}</a>
                <a href="{{ route('material-reports.site.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Export PDF') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Filters Section --}}
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800 mb-6">
                <div class="p-6">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Filters') }}</h3>
                    <form method="GET" action="{{ route('material-reports.site') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Search') }}</label>
                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ request('search') }}"
                                placeholder="{{ __('Material name...') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Project') }}</label>
                            <select
                                name="project_id"
                                id="project_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">{{ __('All Projects') }}</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>
                                        {{ $project->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From Date') }}</label>
                            <input
                                type="date"
                                name="from_date"
                                id="from_date"
                                value="{{ request('from_date') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To Date') }}</label>
                            <input
                                type="date"
                                name="to_date"
                                id="to_date"
                                value="{{ request('to_date') }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="quarter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Quarter') }}</label>
                            <select
                                name="quarter"
                                id="quarter"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">{{ __('All Quarters') }}</option>
                                <option value="1" @selected(request('quarter') == '1')>{{ __('Q1') }}</option>
                                <option value="2" @selected(request('quarter') == '2')>{{ __('Q2') }}</option>
                                <option value="3" @selected(request('quarter') == '3')>{{ __('Q3') }}</option>
                                <option value="4" @selected(request('quarter') == '4')>{{ __('Q4') }}</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-5 flex gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                            >
                                {{ __('Filter') }}
                            </button>
                            <a
                                href="{{ route('material-reports.site') }}"
                                class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            >
                                {{ __('Reset') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Report Table --}}
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Material Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Unit of Measurement') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Project') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Assigned Count') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Distributed to Employee') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Physical Available') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($materials as $material)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $material['material_name'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $material['unit_of_measure'] }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $material['project_name'] }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $material['project_code'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $material['assigned_count'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $material['distributed_to_employee'] }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">{{ $material['physical_available'] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25M6.75 7.5V4.5a2.25 2.25 0 012.25-2.25h3a2.25 2.25 0 012.25 2.25v3" />
                                                </svg>
                                                <p class="text-center text-sm font-medium text-gray-600 dark:text-gray-400">
                                                    {{ __('No materials found.') }}
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">{{ $materials->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
