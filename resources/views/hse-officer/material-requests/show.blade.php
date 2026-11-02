<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{{ __('Material Request Details') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Review request details and approve or reject.') }}</p>
            </div>
            <a href="{{ route('hse-officer.material-requests.index') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">
                {{ __('Back to Requests') }}
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6">
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                <ul class="list-disc pl-5 text-sm text-red-700 dark:text-red-400">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-lg border p-6 shadow-sm
            @if ($request->isPending())
                border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20
            @elseif ($request->isPartiallyApproved())
                border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20
            @elseif ($request->isApproved())
                border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20
            @else
                border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20
            @endif
        ">
            <h3 class="text-lg font-semibold
                @if ($request->isPending()) text-yellow-800 dark:text-yellow-300
                @elseif ($request->isPartiallyApproved()) text-blue-800 dark:text-blue-300
                @elseif ($request->isApproved()) text-green-800 dark:text-green-300
                @else text-red-800 dark:text-red-300
                @endif
            ">
                @if ($request->isPending())
                    {{ __('Request Pending Approval') }}
                @elseif ($request->isPartiallyApproved())
                    {{ __('Request Partially Approved') }}
                @elseif ($request->isApproved())
                    {{ __('Request Approved') }}
                @else
                    {{ __('Request Rejected') }}
                @endif
            </h3>
            <p class="mt-1 text-sm
                @if ($request->isPending()) text-yellow-700 dark:text-yellow-400
                @elseif ($request->isPartiallyApproved()) text-blue-700 dark:text-blue-400
                @elseif ($request->isApproved()) text-green-700 dark:text-green-400
                @else text-red-700 dark:text-red-400
                @endif
            ">
                @if ($request->isPending())
                    {{ __('Awaiting review. You can approve or reject below.') }}
                @elseif ($request->isPartiallyApproved())
                    {{ __('Some employees are already approved. You can approve the remaining employees below.') }}
                @elseif ($request->isApproved())
                    {{ __('Approved by') }} {{ $request->approver?->name ?? '-' }} {{ __('on') }} {{ $request->approved_at?->format('M d, Y \a\t h:i A') ?? '-' }}
                @else
                    {{ __('Rejected by') }} {{ $request->approver?->name ?? '-' }} {{ __('on') }} {{ $request->approved_at?->format('M d, Y \a\t h:i A') ?? '-' }}
                @endif
            </p>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Request Information') }}</h3>
            </div>
            <div class="p-6">
                <dl class="divide-y divide-gray-100 dark:divide-gray-800">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Request Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $request->created_at->format('F d, Y \a\t h:i A') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Requested By') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                            {{ $request->requester->name }}
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $request->requester->email }}</p>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Project / Site') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                            <span class="font-medium">{{ $request->project->project_code }}</span> - {{ $request->project->project_name }}
                        </dd>
                    </div>
                    @if ($request->items->isNotEmpty())
                        <div class="py-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Requested Items') }}</dt>
                            <dd class="mt-1">
                                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Material') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Requested') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('HO Stock') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Permitted') }}</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @foreach ($request->items as $item)
                                                <tr>
                                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                        {{ $item->material?->material_name ?? __('Unknown Material') }}
                                                        @if ($item->material?->material_description)
                                                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $item->material->material_description }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                                        <span class="inline-flex rounded-full bg-cyan-100 px-2.5 py-0.5 text-xs font-semibold text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300">
                                                            {{ $item->quantity }} {{ $item->material?->unitOfMeasure?->name ?? __('units') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2 text-xs font-medium {{ ($item->material?->quantity ?? 0) < $item->quantity ? 'text-red-500 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                                                        {{ $item->material?->quantity ?? 0 }}
                                                    </td>
                                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                                        {{ $item->permitted_quantity !== null ? $item->permitted_quantity : '—' }}
                                                    </td>
                                                    <td class="px-4 py-2 text-xs">
                                                        @if ($item->isApproved())
                                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                                {{ __('Approved') }}
                                                            </span>
                                                        @elseif ($item->isRejected())
                                                            <div>
                                                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 font-semibold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                                                    {{ __('Rejected') }}
                                                                </span>
                                                                @if ($item->rejection_reason)
                                                                    <p class="mt-0.5 text-[11px] text-rose-600 dark:text-rose-400">{{ $item->rejection_reason }}</p>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                                                {{ __('Pending') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </dd>
                        </div>
                    @else
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Material') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                <span class="font-medium">{{ $request->material?->material_name ?? '—' }}</span>
                                @if ($request->material?->material_description)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $request->material->material_description }}</p>
                                @endif
                            </dd>
                        </div>
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Quantity Requested') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                {{ $request->quantity }} {{ $request->material?->unitOfMeasure?->name ?? __('units') }}
                                @if ($request->material)
                                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ __('Head Office Stock') }}: {{ $request->material->quantity }})</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                    @if ($request->isApproved() || $request->isPartiallyApproved())
                        <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Permitted Quantity') }}</dt>
                            <dd class="mt-1 text-sm font-semibold text-emerald-700 dark:text-emerald-400 sm:col-span-2 sm:mt-0">
                                {{ $request->permitted_quantity ?? ($request->requestedEmployees->sum('permitted_quantity') ?: 0) }} {{ $request->material?->unitOfMeasure?->name ?? __('units') }}
                            </dd>
                        </div>
                    @endif
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Requested employees') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $request->requestedEmployees->count() }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-900">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Employee Requests') }}</h3>
            </div>
            <div class="p-6">
                @if ($request->requestedEmployees->isEmpty())
                    <div class="flex items-start gap-3 rounded-md border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z" /></svg>
                        <p class="text-sm text-blue-700 dark:text-blue-300">{{ __('No specific employees assigned. This is a general site / bulk request.') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Employee') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Req. Qty') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Permitted Qty') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($request->requestedEmployees as $requestedEmployee)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $requestedEmployee->employee->fullName() }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $requestedEmployee->quantity }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $requestedEmployee->permitted_quantity ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($requestedEmployee->isApproved())
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('Approved') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @if ($request->isRejected() && $request->rejection_reason)
            <div class="overflow-hidden rounded-lg border border-red-200 bg-red-50 shadow-sm dark:border-red-800 dark:bg-red-900/20">
                <div class="border-b border-red-200 px-6 py-4 dark:border-red-800">
                    <h3 class="text-base font-semibold text-red-800 dark:text-red-300">{{ __('Rejection Reason') }}</h3>
                </div>
                <div class="p-6 text-sm text-red-700 dark:text-red-400">
                    {{ $request->rejection_reason }}
                </div>
            </div>
        @endif

        @php
            $pendingItems = $request->items->where('status', 'pending');
            $hasPendingEmployees = $request->requestedEmployees->isNotEmpty() && $request->requestedEmployees->whereNull('approved_at')->isNotEmpty();
            $canDecide = ($request->isPending() || $request->isPartiallyApproved()) && ($pendingItems->isNotEmpty() || $hasPendingEmployees || ($request->items->isEmpty() && $request->requestedEmployees->isEmpty()));
        @endphp

        @if ($canDecide)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ __('Decision Panel') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Review and handle approval or rejection for each material item individually.') }}
                    </p>
                </div>

                {{-- PER-ITEM DECISION FORM (when items exist and any are pending) --}}
                @if ($pendingItems->isNotEmpty())
                    <form method="POST" action="{{ route('hse-officer.material-requests.decide-items', $request) }}" class="space-y-4">
                        @csrf
                        <div class="space-y-3">
                            @foreach ($pendingItems as $item)
                                @php
                                    $hoStock = (int) ($item->material?->quantity ?? 0);
                                    $defaultAction = $hoStock > 0 ? 'approve' : 'reject';
                                @endphp
                                <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-4 transition-all dark:border-gray-700 dark:bg-gray-800/70" x-data="{ action: '{{ old('items.' . $item->id . '.action', $defaultAction) }}' }">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-gray-200 dark:border-gray-700">
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $item->material?->material_name ?? __('Material Item') }}
                                            </h4>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ __('Requested:') }} <strong class="text-gray-800 dark:text-gray-200">{{ $item->quantity }} {{ $item->material?->unitOfMeasure?->name ?? __('units') }}</strong></span>
                                                <span>&bull;</span>
                                                <span>{{ __('HO Stock:') }}
                                                    <strong class="{{ $hoStock < $item->quantity ? 'text-red-600 dark:text-red-400 font-bold' : 'text-emerald-600 dark:text-emerald-400' }}">
                                                        {{ $hoStock }}
                                                    </strong>
                                                </span>
                                                @if ($hoStock === 0)
                                                    <span class="inline-flex rounded bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                                        {{ __('Out of Stock in Head Office') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Toggle Approve / Reject --}}
                                        <div class="inline-flex rounded-lg border border-gray-300 bg-white p-1 shadow-xs dark:border-gray-600 dark:bg-gray-900">
                                            <button
                                                type="button"
                                                @click="action = 'approve'"
                                                :class="action === 'approve' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
                                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ __('Approve') }}
                                            </button>
                                            <button
                                                type="button"
                                                @click="action = 'reject'"
                                                :class="action === 'reject' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'"
                                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs transition"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                {{ __('Reject') }}
                                            </button>
                                        </div>
                                    </div>

                                    <input type="hidden" :name="'items[' + {{ $item->id }} + '][action]'" :value="action">

                                    {{-- If Approve --}}
                                    <div x-show="action === 'approve'" class="mt-3 grid gap-3 sm:grid-cols-12 sm:items-center">
                                        <div class="sm:col-span-8">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ __('Permitted Quantity to Distribute') }} <span class="text-red-500">*</span>
                                            </label>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ __('Max available in Head Office: :qty', ['qty' => $hoStock]) }}
                                            </p>
                                        </div>
                                        <div class="sm:col-span-4">
                                            <input
                                                type="number"
                                                :name="'items[' + {{ $item->id }} + '][permitted_quantity]'"
                                                value="{{ old('items.' . $item->id . '.permitted_quantity', min($item->quantity, max(1, $hoStock))) }}"
                                                min="1"
                                                max="{{ $hoStock > 0 ? $hoStock : '' }}"
                                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                                :required="action === 'approve'"
                                            >
                                        </div>
                                    </div>

                                    {{-- If Reject --}}
                                    <div x-show="action === 'reject'" x-cloak class="mt-3 space-y-1">
                                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ __('Rejection Reason for this item') }}
                                        </label>
                                        <input
                                            type="text"
                                            :name="'items[' + {{ $item->id }} + '][rejection_reason]'"
                                            placeholder="{{ $hoStock === 0 ? __('Out of stock in Head Office') : __('e.g., Not approved for current project phase') }}"
                                            value="{{ old('items.' . $item->id . '.rejection_reason', $hoStock === 0 ? __('Out of stock in Head Office') : '') }}"
                                            class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                onclick="return confirm('{{ __('Apply decisions for these items? Approved items will be deducted from head office stock.') }}')"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('Apply Item Decisions') }}
                            </button>
                        </div>
                    </form>

                {{-- PER-EMPLOYEE approval form (when employees exist and are pending) --}}
                @elseif ($hasPendingEmployees)
                    <form method="POST" action="{{ route('hse-officer.material-requests.approve', $request) }}" class="space-y-4">
                        @csrf
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Set Permitted Quantity for each pending employee:') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Set to 0 to reject or skip distribution for an employee.') }}</p>
                        </div>
                        <div class="max-h-64 overflow-auto rounded-md border border-gray-200 p-3 dark:border-gray-700 space-y-3">
                            @foreach ($request->requestedEmployees->whereNull('approved_at') as $requestedEmployee)
                                <div class="flex items-center justify-between gap-4">
                                    <label for="qty_{{ $requestedEmployee->employee_id }}" class="text-sm text-gray-700 dark:text-gray-300 flex-1 truncate">
                                        {{ $requestedEmployee->employee->fullName() }}
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ __('Requested:') }} {{ $requestedEmployee->quantity }}</span>
                                    </label>
                                    <div class="w-24">
                                        <input type="number" id="qty_{{ $requestedEmployee->employee_id }}" name="permitted_quantities[{{ $requestedEmployee->employee_id }}]" value="{{ old('permitted_quantities.' . $requestedEmployee->employee_id, $requestedEmployee->quantity) }}" min="0" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" required>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('permitted_quantities')" class="mt-2" />

                        <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                            onclick="return confirm('{{ __('Approve and distribute these quantities? This will immediately deduct from head office stock.') }}')">
                            {{ __('Approve & Distribute') }}
                        </button>
                    </form>

                {{-- Legacy Single Item approval (when items table is empty) --}}
                @elseif ($request->items->isEmpty())
                    <form method="POST" action="{{ route('hse-officer.material-requests.approve', $request) }}" class="space-y-4">
                        @csrf
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Permitted Quantity') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Enter the total quantity to approve and distribute.') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-36">
                                <input
                                    id="bulk_permitted_quantity"
                                    name="bulk_permitted_quantity"
                                    type="number"
                                    min="1"
                                    value="{{ old('bulk_permitted_quantity', $request->quantity) }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                    required
                                >
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('of') }} {{ $request->quantity }} {{ __('requested') }}
                                &mdash; {{ __('Stock:') }} {{ $request->material?->quantity ?? 0 }}
                            </span>
                        </div>
                        <x-input-error :messages="$errors->get('bulk_permitted_quantity')" class="mt-1" />
                        <button type="submit" class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                            onclick="return confirm('{{ __('Approve and distribute this quantity? This will immediately deduct from head office stock.') }}')">
                            {{ __('Approve & Distribute') }}
                        </button>
                    </form>
                @endif

                {{-- Option to reject entire request --}}
                @if ($request->isPending())
                    <div class="border-t border-gray-200 pt-5 dark:border-gray-700">
                        <form method="POST" action="{{ route('hse-officer.material-requests.reject', $request) }}" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="rejection_reason" :value="__('Reject Entire Request')" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('Rejects all items and closes this requisition.') }}</p>
                                <textarea id="rejection_reason" name="rejection_reason" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" placeholder="{{ __('Provide a reason for rejecting the overall request...') }}" required></textarea>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-rose-500" onclick="return confirm('{{ __('Are you sure you want to reject the entire request?') }}')">
                                    {{ __('Reject Entire Request') }}
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
