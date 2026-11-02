<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Head Office Dashboard') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Material inventory, project assignments, and site distribution overview') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('material-assignments.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Assign Materials') }}</a>
                <a href="{{ route('material-reports.inventory') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Material Inventory Report') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        @include('partials.material-dashboard-report', ['stats' => $stats])

        <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Recently Created Materials') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Latest materials added to the system') }}</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse ($recentMaterials as $material)
                        <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-md transition duration-150 ease-in-out cursor-pointer" @click="open = !open">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $material->material_name }}</h4>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $material->quantity }} {{ $material->unitOfMeasure?->name ?? 'units' }}
                                </span>
                            </div>
                            <div x-show="open" x-collapse x-cloak class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ $material->material_description }}</p>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex justify-between items-center">
                                    <span>Added: {{ $material->created_at->format('M d, Y') }}</span>
                                    <a href="{{ route('materials.edit', $material) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300" @click.stop>{{ __('Edit') }}</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials found.') }}</p>
                        </div>
                    @endforelse
                </div>
                <div class="mt-6">
                    {{ $recentMaterials->links() }}
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Recent Project Assignments') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Latest materials sent from head office to sites') }}</p>
            </div>
            <div class="p-6">
                @forelse ($recentProjectAssignments as $assignment)
                    <div class="mb-4 border-b border-gray-100 pb-4 last:mb-0 last:border-0 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $assignment->material->material_name }} × {{ $assignment->quantity }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $assignment->project->project_code }} — {{ $assignment->project->project_name }}
                            · {{ $assignment->created_at->format('M d, Y') }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No project assignments yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    @vite('resources/js/dashboard-charts.js')
</x-app-layout>
