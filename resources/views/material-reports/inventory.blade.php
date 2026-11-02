<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Head Office Material Inventory Report') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Total materials created, quantities, and balances across the system') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('material-reports.inventory.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Export Excel') }}</a>
                <a href="{{ route('material-reports.inventory.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Export PDF') }}</a>
                <a href="{{ route('material-reports.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Project Reports') }}</a>
                <a href="{{ route('material-quantities.index') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Manage Quantities') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Section -->
            <div class="rounded-lg bg-white shadow-sm dark:bg-gray-800 p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Filters') }}</h3>
                <form method="GET" action="{{ route('material-reports.inventory') }}" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Search Material') }}</label>
                            <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Material name...') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <!-- From Date -->
                        <div>
                            <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('From Date') }}</label>
                            <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <!-- To Date -->
                        <div>
                            <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('To Date') }}</label>
                            <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <!-- Quarter -->
                        <div>
                            <label for="quarter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Quarter') }}</label>
                            <select id="quarter" name="quarter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">{{ __('All Quarters') }}</option>
                                <option value="1" @if(request('quarter') === '1') selected @endif>{{ __('Q1 (Jan-Mar)') }}</option>
                                <option value="2" @if(request('quarter') === '2') selected @endif>{{ __('Q2 (Apr-Jun)') }}</option>
                                <option value="3" @if(request('quarter') === '3') selected @endif>{{ __('Q3 (Jul-Sep)') }}</option>
                                <option value="4" @if(request('quarter') === '4') selected @endif>{{ __('Q4 (Oct-Dec)') }}</option>
                            </select>
                        </div>

                        <!-- Project Filter -->
                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Project') }}</label>
                            <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">{{ __('All Projects') }}</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}" @if(request('project_id') == $project->id) selected @endif>{{ $project->project_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Filter') }}</button>
                        <a href="{{ route('material-reports.inventory') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Clear Filters') }}</a>
                    </div>
                </form>
            </div>
        </div>

        @include('partials.material-inventory-detail-table', ['materials' => $materials, 'isHeadOffice' => true])
    </div>
</x-app-layout>
