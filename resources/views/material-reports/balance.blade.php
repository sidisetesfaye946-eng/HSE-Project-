<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Material Balance Report') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('View all materials and their balance across the system') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('material-reports.balance.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Export Excel') }}</a>
                <a href="{{ route('material-reports.balance.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Export PDF') }}</a>
                <a href="{{ route('material-reports.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Project Reports') }}</a>
                <a href="{{ route('material-reports.inventory') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Inventory Report') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Materials List -->
                <div class="lg:col-span-2">
                    <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Materials') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Click on a material to see its breakdown by project') }}</p>
                        </div>
                        <div class="overflow-x-auto p-6">
                            @if (count($materials) === 0)
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials found.') }}</p>
                            @else
                                <div class="space-y-2">
                                    @foreach ($materials as $material)
                                        <a href="{{ route('material-reports.balance') }}?material_id={{ $material['id'] }}" class="block p-4 rounded-lg border border-gray-200 hover:border-indigo-500 hover:bg-indigo-50 dark:border-gray-700 dark:hover:bg-gray-700 transition-colors @if($selectedMaterial?->id === $material['id']) bg-indigo-50 border-indigo-500 dark:bg-gray-700 @endif">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $material['name'] }}</h4>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $material['description'] }}</p>
                                                    <div class="mt-2 flex flex-wrap gap-4 text-sm">
                                                        <div>
                                                            <span class="text-gray-500 dark:text-gray-400">{{ __('Unit') }}:</span>
                                                            <span class="ml-1 font-medium text-gray-900 dark:text-gray-100">{{ $material['unit_of_measure'] ?? '—' }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-gray-500 dark:text-gray-400">{{ __('Head Office') }}:</span>
                                                            <span class="ml-1 font-medium text-gray-900 dark:text-gray-100">{{ $material['head_office_balance'] }}</span>
                                                        </div>
                                                        <div>
                                                            <span class="text-gray-500 dark:text-gray-400">{{ __('On Sites') }}:</span>
                                                            <span class="ml-1 font-medium text-blue-600 dark:text-blue-400">{{ $material['site_balance'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="ml-4 text-right">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Total Balance') }}</p>
                                                    <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $material['total_balance'] }}</p>
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Breakdown Panel -->
                <div>
                    @if ($selectedMaterial && $selectedMaterialBreakdown)
                        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
                            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Breakdown') }}</h3>
                                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">{{ $selectedMaterialBreakdown['material_name'] }}</p>
                            </div>
                            <div class="p-6 space-y-4">
                                <!-- Head Office -->
                                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Head Office Balance') }}</p>
                                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $selectedMaterialBreakdown['head_office_balance'] }}</p>
                                </div>

                                <!-- Summary Stats -->
                                <div class="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Assigned to Projects') }}:</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $selectedMaterialBreakdown['total_assigned_to_projects'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('Assigned to Employees') }}:</span>
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $selectedMaterialBreakdown['total_assigned_to_employees'] }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-gray-200 pt-3 dark:border-gray-700">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Total Site Balance') }}:</span>
                                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $selectedMaterialBreakdown['total_site_balance'] }}</span>
                                    </div>
                                </div>

                                <!-- Projects List -->
                                @if (count($selectedMaterialBreakdown['projects']) > 0)
                                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('By Project') }}</p>
                                        <div class="space-y-2">
                                            @foreach ($selectedMaterialBreakdown['projects'] as $project)
                                                <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ $project['project_name'] }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project['project_code'] }}</p>
                                                        </div>
                                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ $project['assigned_to_project'] }}</span>
                                                    </div>
                                                    <div class="text-xs space-y-1 pt-2 border-t border-gray-200 dark:border-gray-600">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600 dark:text-gray-400">{{ __('To Employees') }}:</span>
                                                            <span class="text-gray-900 dark:text-gray-100">{{ $project['assigned_to_employees'] }}</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600 dark:text-gray-400">{{ __('Available') }}:</span>
                                                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $project['available_balance'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg bg-gray-50 p-6 text-center dark:bg-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Select a material to see its breakdown') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
