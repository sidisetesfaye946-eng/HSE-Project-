<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Project Material Balance') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $project->project_code }} — {{ $project->project_name }}</p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                <a href="{{ route('material-reports.project.export', array_merge(['project' => $project], request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Export Excel') }}</a>
                <a href="{{ route('material-reports.project.export', array_merge(['project' => $project], request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Export PDF') }}</a>
                <a href="{{ route('material-reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 ml-2">{{ __('Back to all projects') }}</a>
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

            <div class="mb-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                <p><span class="font-semibold">{{ __('Site officer') }}:</span> {{ $project->siteOfficer?->name ?? '—' }}</p>
            </div>

            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($balances->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials assigned to this project yet.') }}</p>
                    @else
                        <div class="space-y-8">
                            @foreach ($balances as $balance)
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $balance['material']->material_name }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Total assigned balance') }}: {{ $balance['total_quantity'] }}</p>

                                    <table class="mt-4 min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Quantity') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Receiver') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Assigned by') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                            @foreach ($balance['assignments'] as $assignment)
                                                <tr>
                                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $assignment->created_at->format('M d, Y H:i') }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $assignment->quantity }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $assignment->receiver->name }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $assignment->assignedBy->name }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
