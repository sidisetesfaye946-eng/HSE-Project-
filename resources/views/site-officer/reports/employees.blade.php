<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Employees Report') }}
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('site-officer.reports.employees.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('Export Excel') }}
                </a>
                <a href="{{ route('site-officer.reports.employees.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">
                    {{ __('Export PDF') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
            {{-- Filter Bar --}}
            <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                <form method="GET" action="{{ route('site-officer.reports.employees') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                    {{-- Search Input --}}
                    <div>
                        <x-input-label for="search" :value="__('Search')" />
                        <x-text-input id="search" name="search" type="text" class="mt-1 block w-full" :value="request('search')" placeholder="{{ __('Search name, title, project...') }}" />
                    </div>

                    {{-- Quarter Filter --}}
                    <div>
                        <x-input-label for="quarter" :value="__('Quarter')" />
                        <select id="quarter" name="quarter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All Quarters') }}</option>
                            <option value="Q1" @selected(request('quarter') === 'Q1')>{{ __('Q1 (Jan - Mar)') }}</option>
                            <option value="Q2" @selected(request('quarter') === 'Q2')>{{ __('Q2 (Apr - Jun)') }}</option>
                            <option value="Q3" @selected(request('quarter') === 'Q3')>{{ __('Q3 (Jul - Sep)') }}</option>
                            <option value="Q4" @selected(request('quarter') === 'Q4')>{{ __('Q4 (Oct - Dec)') }}</option>
                        </select>
                    </div>

                    {{-- Project Filter --}}
                    <div>
                        <x-input-label for="project_id" :value="__('Project')" />
                        <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('All Assigned Projects') }}</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>{{ $project->project_code }} — {{ $project->project_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            {{ __('Filter') }}
                        </button>
                        @if ($hasFilter)
                            <a href="{{ route('site-officer.reports.employees') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Employee Name') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Title') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Projects (Assigned to the user)') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse ($employees as $employee)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $employee->fullName() }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        {{ $employee->job_title ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        {{ $employee->project_name_display ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        {{ $employee->material_type_display ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                        {{ $employee->date ? \Carbon\Carbon::parse($employee->date)->format('M d, Y') : $employee->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center rounded bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50">
                                                {{ __('View') }}
                                            </a>
                                            <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center rounded bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50">
                                                {{ __('Edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('employees.destroy', $employee) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this employee record?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                                                    {{ __('Delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('No employee records found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($employees->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
