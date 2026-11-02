<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Employees') }}</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-900">{{ __('Export Excel') }}</a>
                <a href="{{ route('employees.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">{{ __('Export PDF') }}</a>
                <a href="{{ route('employees.import.template') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-900">{{ __('Download Template') }}</a>
                <a href="{{ route('employees.import') }}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">{{ __('Import') }}</a>
                @unless(auth()->user()->hasRole('HSE Site Officer'))
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">{{ __('Create Employee') }}</a>
                @endunless
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('status'))
                <div class="mb-6 rounded-lg bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg bg-rose-50 p-4 text-sm text-rose-800 dark:bg-rose-900/20 dark:text-rose-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="GET" action="{{ route('employees.index') }}" class="mb-6 space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <x-input-label for="search" :value="__('Search Employees')" />
                                <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="request('search')" placeholder="{{ __('Name, title, project...') }}" />
                            </div>

                            <div>
                                <x-input-label for="project_id" :value="__('Project')" />
                                <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">{{ __('All Projects') }}</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected(request('project_id') == $project->id)>
                                            {{ $project->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="from_date" :value="__('From Date')" />
                                <x-text-input id="from_date" name="from_date" type="date" class="mt-1 block w-full" :value="request('from_date')" />
                            </div>

                            <div>
                                <x-input-label for="to_date" :value="__('To Date')" />
                                <x-text-input id="to_date" name="to_date" type="date" class="mt-1 block w-full" :value="request('to_date')" />
                            </div>

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
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <x-primary-button type="submit">{{ __('Filter') }}</x-primary-button>
                            @if (!empty($hasFilter))
                                <a href="{{ route('employees.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    {{ __('Reset') }}
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Employee Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Title') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Project') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                @forelse ($employees as $employee)
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $employee->employee_name }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $employee->job_title ?: '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $employee->project_name_display ?: '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $employee->material_type_display ?: '-' }}</td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                                            {{ $employee->date ? \Carbon\Carbon::parse($employee->date)->format('M d, Y') : $employee->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                                            <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center rounded-md bg-teal-600 px-3 py-2 text-white hover:bg-teal-500">{{ __('View') }}</a>
                                            <a href="{{ route('employees.edit', $employee) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-white hover:bg-blue-500">{{ __('Edit') }}</a>
                                            <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ __('Delete this employee?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-white hover:bg-red-500">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No employees found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $employees->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
