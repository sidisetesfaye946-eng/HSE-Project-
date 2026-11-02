<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Admin Dashboard') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('System overview and quick statistics') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 pulse-dot"></span>
                    {{ __('System Active') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8 animate-fade-in">

        {{-- ── Stat Cards ─────────────────────────────────────────── --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-dashboard.stat-card
                :label="__('Total Users')"
                :value="number_format($stats['total_users'])"
                :hint="__('Registered system accounts')"
                color="indigo"
                icon='<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0"/>'
            />
            <x-dashboard.stat-card
                :label="__('Total Roles')"
                :value="number_format($stats['total_roles'])"
                :hint="__('Access groups configured')"
                color="slate"
                icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M12 3.75l7.5 3v5.25c0 4.5-3 7.5-7.5 8.25-4.5-.75-7.5-3.75-7.5-8.25V6.75l7.5-3Z"/>'
            />
            <x-dashboard.stat-card
                :label="__('Projects')"
                :value="number_format($stats['total_projects'])"
                :hint="__('Projects under management')"
                color="blue"
                icon='<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M5.25 6.75v12h13.5v-12M8.25 10.5h7.5M8.25 14.25h4.5"/>'
            />
            <x-dashboard.stat-card
                :label="__('Employees')"
                :value="number_format($stats['total_employees'])"
                :hint="__('Available for assignments')"
                color="emerald"
                icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 12a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5ZM4.5 20.25a7.5 7.5 0 0 1 15 0M18.75 4.5v4.5M21 6.75h-4.5"/>'
            />
        </div>

        {{-- ── Charts Row ─────────────────────────────────────────── --}}
        <div class="grid gap-6 xl:grid-cols-3">

            {{-- Users vs Roles Bar Chart --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card dark:border-gray-800 dark:bg-gray-900 xl:col-span-2">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Users vs Roles') }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Number of users assigned to each role') }}</p>
                    </div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-eec-500/10">
                        <svg class="h-4 w-4 text-eec-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                        </svg>
                    </div>
                </div>
                <div class="space-y-4 p-6">
                    @forelse ($stats['roles'] as $role)
                        @php $pct = $stats['max_role_users'] > 0 ? ($role->users_count / $stats['max_role_users']) * 100 : 0; @endphp
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <span class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-eec-500/15 text-[10px] font-bold text-eec-600 dark:text-eec-400">{{ substr($role->name, 0, 1) }}</span>
                                    {{ $role->name }}
                                </span>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ number_format($role->users_count) }}</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-eec-500 to-cyan-400 transition-all duration-700 ease-out"
                                    style="width: {{ $pct }}%"
                                ></div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M12 3.75l7.5 3v5.25c0 4.5-3 7.5-7.5 8.25-4.5-.75-7.5-3.75-7.5-8.25V6.75l7.5-3Z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No roles have been created yet.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Material Overview --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Material Overview') }}</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Current stock and assignment totals') }}</p>
                </div>
                <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                    @php
                        $materialStats = [
                            ['label' => __('Material Types'),         'value' => $stats['total_materials'],       'color' => 'text-eec-600 dark:text-eec-400'],
                            ['label' => __('Head Office Stock'),      'value' => $stats['head_office_stock'],     'color' => 'text-blue-600 dark:text-blue-400'],
                            ['label' => __('Assigned to Projects'),   'value' => $stats['assigned_to_projects'],  'color' => 'text-amber-600 dark:text-amber-400'],
                            ['label' => __('Assigned to Employees'),  'value' => $stats['assigned_to_employees'], 'color' => 'text-emerald-600 dark:text-emerald-400'],
                        ];
                    @endphp
                    @foreach ($materialStats as $stat)
                        <div class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                            <dt class="text-sm text-gray-600 dark:text-gray-400">{{ $stat['label'] }}</dt>
                            <dd class="text-sm font-bold {{ $stat['color'] }}">{{ number_format($stat['value']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        {{-- ── Recent Data Tables ─────────────────────────────────── --}}
        <div class="grid gap-6 xl:grid-cols-2">

            {{-- Recent Users --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Recent Users') }}</h3>
                    <a href="{{ route('users.index') }}" class="text-xs font-medium text-eec-600 hover:text-eec-500 dark:text-eec-400">{{ __('View all →') }}</a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($stats['latest_users'] as $user)
                        <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-eec-500 to-teal-600 text-xs font-bold text-white shadow">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                            @if ($user->roles->first())
                                <span class="shrink-0 rounded-full bg-eec-100 px-2 py-0.5 text-xs font-medium text-eec-800 dark:bg-eec-900/30 dark:text-eec-300">
                                    {{ $user->roles->first()->name }}
                                </span>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No users available yet.') }}</div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Projects --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-card dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Recent Projects') }}</h3>
                    <a href="{{ route('projects.index') }}" class="text-xs font-medium text-eec-600 hover:text-eec-500 dark:text-eec-400">{{ __('View all →') }}</a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($stats['latest_projects'] as $project)
                        <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M5.25 6.75v12h13.5v-12"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $project->project_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project->project_code }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400 truncate max-w-[100px]">
                                {{ $project->siteOfficer?->name ?? __('Unassigned') }}
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No projects available yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
