<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Employee Assignment History') }}</h2>
            <div class="flex flex-wrap gap-2">
                @if (Auth::user()->hasAnyRole(['Admin', 'HSE Head Office', 'HSE Officer']))
                    <a href="{{ route('employees.import') }}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">{{ __('Import Employees') }}</a>
                @endif
                <a href="{{ route('site-officer.employee-assignments.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Export Excel') }}</a>
                <a href="{{ route('site-officer.employee-assignments.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Export PDF') }}</a>
                @if (Auth::user()->name !== 'Daniel Girma' && Auth::user()->email !== 'danielg@example.com')
                    <a href="{{ route('site-officer.employee-assignments.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('New assignment') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Filters Section --}}
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800 mb-6">
                <div class="p-6">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Filters') }}</h3>
                    <form method="GET" action="{{ route('site-officer.employee-assignments.index') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Search') }}</label>
                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ request('search') }}"
                                placeholder="{{ __('Material or employee name...') }}"
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
                                href="{{ route('site-officer.employee-assignments.index') }}"
                                class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            >
                                {{ __('Reset') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- All Assignments Table --}}
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('All assignments') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Project') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Quantity') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Employee') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('History') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700" x-data="{ expandedAssignment: null }">
                                @forelse ($assignments as $assignment)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $assignment->assigned_date->format('M d, Y') }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $assignment->project->project_code }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $assignment->material->material_name }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ $assignment->quantity }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $assignment->employee ? ($assignment->employee->first_name . ' ' . $assignment->employee->last_name) : __('General Site Usage') }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
                                                @click="expandedAssignment = expandedAssignment === {{ $assignment->id }} ? null : {{ $assignment->id }}"
                                            >
                                                {{ __('History') }}
                                            </button>
                                        </td>
                                    </tr>
                                    {{-- Expandable details row --}}
                                    <tr x-show="expandedAssignment === {{ $assignment->id }}" x-cloak class="bg-gray-50 dark:bg-gray-750">
                                        <td colspan="6" class="px-6 py-4">
                                            <div class="space-y-2 text-sm">
                                                <p><span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('Project Name:') }}</span> {{ $assignment->project->project_name }}</p>
                                                <p><span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('Material Description:') }}</span> {{ $assignment->material->material_description ?? '—' }}</p>
                                                <p><span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('Employee Job Title:') }}</span> {{ $assignment->employee?->job_title ?? '—' }}</p>
                                                <p><span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('Assignment Date:') }}</span> {{ $assignment->assigned_date->format('M d, Y') }}</p>
                                                <p><span class="font-semibold text-gray-700 dark:text-gray-300">{{ __('Shoe Number:') }}</span> {{ __('To be added') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No assignments found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">{{ $assignments->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
