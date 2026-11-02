<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Project Material Reports') }}</h2>
            <a href="{{ route('material-reports.inventory') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Material Inventory Report') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($projects as $project)
                @php
                    $balances = $balancesByProject[$project->id] ?? collect();
                @endphp
                <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $project->project_name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Site') }}: {{ $project->project_code }} · {{ __('Officer') }}: {{ $project->siteOfficer?->name ?? '—' }}</p>
                        </div>
                        <a href="{{ route('material-reports.show', $project) }}" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-500">{{ __('View details') }}</a>
                    </div>
                    <div class="p-6">
                        @if ($balances->isEmpty())
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials assigned to this project yet.') }}</p>
                        @else
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Assigned balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($balances as $balance)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $balance['material']->material_name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $balance['total_quantity'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400">
                    {{ __('No projects found.') }}
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
