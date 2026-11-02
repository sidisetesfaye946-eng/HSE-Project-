@props(['summary', 'isHeadOffice' => true])

<div @class([
    'grid gap-4',
    'sm:grid-cols-2 lg:grid-cols-3' => $isHeadOffice,
    'sm:grid-cols-2 lg:grid-cols-4' => ! $isHeadOffice,
])>
    <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Materials Created') }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($summary['total_materials']) }}</p>
    </div>

    @if ($isHeadOffice)
        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Opening Stock') }}</p>
            <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($summary['total_stocked_quantity']) }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Head office + sent to projects') }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Physical Balance') }}</p>
            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-gray-100">{{ number_format($summary['total_head_office_available']) }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned to Sites') }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($summary['total_assigned_to_projects']) }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned to Employees') }}</p>
            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($summary['total_assigned_to_employees']) }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total in System') }}</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($summary['total_in_system']) }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Head office available + remaining on sites') }}</p>
        </div>
    @else
        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Received at Site') }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($summary['total_received']) }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Assigned to Employees') }}</p>
            <p class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($summary['total_distributed']) }}</p>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Available Site Balance') }}</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($summary['total_available']) }}</p>
        </div>
    @endif
</div>
