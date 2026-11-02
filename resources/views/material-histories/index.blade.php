<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Material History') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('View head office material movement with opening and current balances, plus export history to Excel or PDF.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('material-histories.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Export Excel') }}</a>
                <a href="{{ route('material-histories.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">{{ __('Export PDF') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total materials tracked') }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $materials->count() }}</p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Opening balance total') }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $materials->sum('opening_balance') }}</p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Current head office balance total') }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $materials->sum('current_balance') }}</p>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
            <form method="GET" action="{{ route('material-histories.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    <div>
                        <x-input-label for="search" :value="__('Search Materials')" />
                        <x-text-input id="search" name="search" type="search" class="mt-1 block w-full" :value="request('search')" placeholder="{{ __('Search by name or description') }}" @input.debounce.350ms="$el.form.requestSubmit()" />
                    </div>
                    <div>
                        <x-input-label for="date" :value="__('Filter by Date')" />
                        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="request('date')" />
                    </div>
                    <div>
                        <x-input-label for="quarter" :value="__('Filter by Quarter')" />
                        <select id="quarter" name="quarter" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            <option value="">{{ __('Select Quarter') }}</option>
                            <option value="Q1" @selected(request('quarter') == 'Q1')>{{ __('Q1 (Jan - Mar)') }}</option>
                            <option value="Q2" @selected(request('quarter') == 'Q2')>{{ __('Q2 (Apr - Jun)') }}</option>
                            <option value="Q3" @selected(request('quarter') == 'Q3')>{{ __('Q3 (Jul - Sep)') }}</option>
                            <option value="Q4" @selected(request('quarter') == 'Q4')>{{ __('Q4 (Oct - Dec)') }}</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="year" :value="__('Quarter Year')" />
                        <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                            @php
                                $currentYear = (int) date('Y');
                                $selectedYear = (int) request('year', $currentYear);
                            @endphp
                            @for ($y = $currentYear; $y >= $currentYear - 5; $y--)
                                <option value="{{ $y }}" @selected($selectedYear == $y)>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit" class="w-full justify-center">{{ __('Apply Filters') }}</x-primary-button>
                        @if (request()->hasAny(['search', 'date', 'quarter']))
                            <a href="{{ route('material-histories.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 w-full text-center">
                                {{ __('Reset') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Event') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Balance Before') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Qty Change') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Balance After') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Description') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Recorded By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse ($histories as $history)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $history->created_at->format('M d, Y H:i') }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">{{ $history->material?->material_name ?? __('Unknown material') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ Illuminate\Support\Str::headline($history->event_type) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">{{ $history->balance_before }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">{{ $history->quantity_change }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-300">{{ $history->balance_after }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $history->description }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $history->createdBy?->name ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No material history records found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($histories, 'links'))
                    <div class="mt-6">
                        {{ $histories->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
