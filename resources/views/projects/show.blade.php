<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $project->project_name }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Site') }}: {{ $project->project_code }} &nbsp;|&nbsp; {{ __('Site Officer') }}: {{ $project->siteOfficer->name ?? 'N/A' }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('projects.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-900">{{ __('Back to Projects') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Current site balance') }}</h3>
                    @if ($balances->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials on this site yet.') }}</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Received') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Distributed') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Available') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($balances as $balance)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $balance['material']->material_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $balance['received'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $balance['distributed'] }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ $balance['available'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid gap-8 lg:grid-cols-2">
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800 p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Incoming assignments') }}</h3>
                @forelse ($incomingHistory as $assignment)
                    <div class="mb-3 text-sm">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $assignment->material->material_name }}</span>
                        <span class="text-gray-500 dark:text-gray-400">× {{ $assignment->quantity }} · {{ $assignment->created_at->format('M d, Y') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('None yet.') }}</p>
                @endforelse
            </div>

            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800 p-6">
                <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Employee distributions') }}</h3>
                @forelse ($employeeAssignments as $assignment)
                    <div class="mb-3 text-sm">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $assignment->material->material_name }}</span>
                        <span class="text-gray-500 dark:text-gray-400">× {{ $assignment->quantity }} @if($assignment->employee) → {{ $assignment->employee->first_name }} {{ $assignment->employee->last_name }} @else → {{ __('General Site Usage') }} @endif · {{ $assignment->assigned_date->format('M d, Y') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('None yet.') }}</p>
                @endforelse
            </div>
            
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800 p-6 lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Attached Employees') }}</h3>
                </div>
                
                @if ($attachedEmployees->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('No employees') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('There are no employees attached to this project yet.') }}</p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($attachedEmployees as $employee)
                            <div class="flex items-center justify-between rounded-xl border border-gray-200 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $employee->fullName() }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $employee->job_title }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
