@props(['stats', 'showProjectLinks' => false])

<div id="material-dashboard-charts" data-charts="{{ json_encode($stats['charts']) }}" class="space-y-8">
    <div @class([
        'grid gap-4 sm:grid-cols-2',
        'xl:grid-cols-3' => $stats['is_head_office'],
        'xl:grid-cols-4' => ! $stats['is_head_office'],
    ])>
        <x-dashboard.stat-card
            :label="__('Total Projects')"
            :value="number_format($stats['total_projects'])"
            :hint="$stats['is_head_office'] ? __('Across all sites') : __('Assigned to you')"
            color="indigo"
        />

        @if ($stats['is_head_office'])
            <x-dashboard.stat-card
                :label="__('Head Office Stock')"
                :value="number_format($stats['head_office_stock'] ?? 0)"
                :hint="__('Current material balance at head office')"
                color="slate"
            />
        @endif

        <x-dashboard.stat-card
            :label="$stats['is_head_office'] ? __('Assigned to Projects') : __('Received at Site')"
            :value="number_format($stats['total_assigned_to_projects'])"
            :hint="$stats['is_head_office'] ? __('Total sent from head office to sites') : __('Materials received from HSE Officer')"
            color="blue"
        />

        @if (! $stats['is_head_office'])
            <x-dashboard.stat-card
                :label="__('Assigned to Employees')"
                :value="number_format($stats['total_assigned_to_employees'])"
                :hint="__('Distributed to workers on site')"
                color="amber"
            />

            <x-dashboard.stat-card
                :label="__('Remaining Site Balance')"
                :value="number_format($stats['site_remaining_balance'])"
                :hint="__('Received minus distributed — still on site')"
                color="emerald"
            />
        @endif
    </div>

    @if (! $stats['is_head_office'])
        <div @class([
            'grid gap-6',
            'lg:grid-cols-2' => ! auth()->user()->hasRole('HSE Site Officer'),
            'grid-cols-1' => auth()->user()->hasRole('HSE Site Officer'),
        ])>
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Material Balance by Project') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Received, assigned to employees, and remaining balance per project') }}</p>
                </div>
                <div class="p-6">
                    @if (count($stats['project_breakdown']) === 0)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No project data available yet.') }}</p>
                    @else
                        <div class="h-72">
                            <canvas id="projectComparisonChart"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            @unless (auth()->user()->hasRole('HSE Site Officer'))
                <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Assignment Flow Overview') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Assigned to projects vs employees vs remaining balances') }}</p>
                    </div>
                    <div class="p-6">
                        @if ($stats['total_assigned_to_projects'] === 0 && $stats['total_assigned_to_employees'] === 0 && ($stats['head_office_stock'] ?? 0) === 0)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No assignment data available yet.') }}</p>
                        @else
                            <div class="h-72">
                                <canvas id="assignmentFlowChart"></canvas>
                            </div>
                        @endif
                    </div>
                </div>
            @endunless
        </div>
    @endif

    @if (!$stats['is_head_office'])
    <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Material Balance Summary') }}</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Per-material breakdown of assignments and remaining balances') }}</p>
        </div>
        <div class="overflow-x-auto p-6">
            @if (count($stats['material_rows']) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials recorded yet.') }}</p>
            @else
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                            @if ($stats['is_head_office'])
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Head Office Stock') }}</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Assigned to Projects') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Assigned to Employees') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Remaining Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($stats['material_rows'] as $row)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row['name'] }}</td>
                                @if ($stats['is_head_office'])
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($row['head_office_stock'] ?? 0) }}</td>
                                @endif
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($row['assigned_to_projects']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($row['assigned_to_employees']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($row['site_remaining']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    @endif

    @if ($showProjectLinks && count($stats['project_breakdown']) > 0)
        <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Project Snapshot') }}</h3>
            </div>
            <div class="grid gap-4 p-6 sm:grid-cols-2">
                @foreach ($stats['project_breakdown'] as $project)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $project['name'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project['code'] }}</p>
                        <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Received') }}</dt>
                                <dd class="mt-1 font-semibold text-blue-600 dark:text-blue-400">{{ $project['received'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Distributed') }}</dt>
                                <dd class="mt-1 font-semibold text-amber-600 dark:text-amber-400">{{ $project['distributed'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('Available') }}</dt>
                                <dd class="mt-1 font-semibold text-emerald-600 dark:text-emerald-400">{{ $project['available'] }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
