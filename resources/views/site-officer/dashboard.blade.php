<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ Auth::user()->assignedSiteName ? Auth::user()->assignedSiteName . ' Dashboard' : __('Site Dashboard') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('site-officer.material-reports.index') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Material Balance Report') }}</a>
                @if (Auth::user()->name !== 'Daniel Girma' && Auth::user()->email !== 'danielg@example.com')
                    <a href="{{ route('site-officer.employee-assignments.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700">{{ __('Assign to Employees') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        @if ($projects->isEmpty())
            <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400">
                {{ __('No projects are assigned to you yet.') }}
            </div>
        @else
            @include('partials.material-dashboard-report', ['stats' => $stats, 'showProjectLinks' => true])
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Incoming Material History') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Materials received from HSE Officer') }}</p>
                </div>
                <div class="p-6">
                    @forelse ($incomingHistory as $assignment)
                        <div class="mb-4 border-b border-gray-100 pb-4 last:mb-0 last:border-0 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $assignment->material->material_name }} × {{ $assignment->quantity }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $assignment->project->project_code }} · {{ $assignment->created_at->format('M d, Y') }} · {{ __('From') }} {{ $assignment->assignedBy->name }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No incoming assignments yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Employee Assignment History') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Materials you assigned to employees') }}</p>
                </div>
                <div class="p-6">
                    @forelse ($employeeAssignmentHistory as $assignment)
                        <div class="mb-4 border-b border-gray-100 pb-4 last:mb-0 last:border-0 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $assignment->material->material_name }} × {{ $assignment->quantity }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $assignment->employee ? ($assignment->employee->first_name . ' ' . $assignment->employee->last_name) : __('General Site Usage') }} · {{ $assignment->project->project_code }} · {{ $assignment->assigned_date->format('M d, Y') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No employee assignments yet.') }}</p>
                    @endforelse
                    <a href="{{ route('site-officer.employee-assignments.index') }}" class="mt-4 inline-block text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('View all') }}</a>
                </div>
            </div>
        </div>
    </div>

    @if ($projects->isNotEmpty())
        @vite('resources/js/dashboard-charts.js')
    @endif
</x-app-layout>
