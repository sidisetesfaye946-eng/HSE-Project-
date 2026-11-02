@props(['materials', 'isHeadOffice' => true])

<div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Material Detail Report') }}</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @if ($isHeadOffice)
                {{ __('Balance of each material across head office and all sites') }}
            @else
                {{ __('Balance of each material on your assigned projects') }}
            @endif
        </p>
    </div>
    <div class="overflow-x-auto p-6">
        @if (count($materials) === 0)
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No materials found.') }}</p>
        @else
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Description') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Unit of Measure') }}</th>
                        @if ($isHeadOffice)
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Opening Stock') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Physical Balance') }}</th>
                        @else
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Received') }}</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Assigned to Sites') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Available Balance') }}</th>
                        @if ($isHeadOffice)
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Total in System') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($materials as $material)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $material['name'] }}</td>
                            <td class="max-w-xs px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $material['description'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ $material['unit_of_measure'] ?? '—' }}</td>
                            @if ($isHeadOffice)
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($material['opening_stock']) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($material['physical_balance'] ?? 0) }}</td>
                            @else
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($material['assigned_to_projects']) }}</td>
                            @endif
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300">{{ number_format($material['assigned_to_sites']) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($material['site_remaining']) }}</td>
                            @if ($isHeadOffice)
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ number_format($material['total_in_system']) }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
