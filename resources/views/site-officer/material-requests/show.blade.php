<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Material Request Details') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('View the full details of your material request') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('site-officer.material-requests.index') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">{{ __('Back to Requests') }}</a>
                <a href="{{ route('site-officer.material-requests.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('New Request') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto space-y-6">
            {{-- Status Banner --}}
            <div class="rounded-xl p-6 shadow-sm
                @if ($request->isPending())
                    bg-yellow-50 border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800
                @elseif ($request->isPartiallyApproved())
                    bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800
                @elseif ($request->isApproved())
                    bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800
                @elseif ($request->isRejected())
                    bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800
                @endif
            ">
                <div class="flex items-center gap-3">
                    @if ($request->isPending())
                        <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @elseif ($request->isApproved())
                        <svg class="h-8 w-8 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @elseif ($request->isRejected())
                        <svg class="h-8 w-8 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold
                            @if ($request->isPending()) text-yellow-800 dark:text-yellow-300
                            @elseif ($request->isApproved()) text-green-800 dark:text-green-300
                            @elseif ($request->isRejected()) text-red-800 dark:text-red-300
                            @endif
                        ">
                            @if ($request->isPending())
                                {{ __('Request Pending Approval') }}
                            @elseif ($request->isPartiallyApproved())
                                {{ __('Request Partially Approved') }}
                            @elseif ($request->isApproved())
                                {{ __('Request Approved') }}
                            @elseif ($request->isRejected())
                                {{ __('Request Rejected') }}
                            @endif
                        </h3>
                        <p class="text-sm
                            @if ($request->isPending()) text-yellow-700 dark:text-yellow-400
                            @elseif ($request->isApproved()) text-green-700 dark:text-green-400
                            @elseif ($request->isRejected()) text-red-700 dark:text-red-400
                            @endif
                        ">
                            @if ($request->isPending())
                                {{ __('Your request is awaiting review by an HSE Officer.') }}
                            @elseif ($request->isPartiallyApproved())
                                {{ __('Some employees are approved. Remaining employees are still pending.') }}
                            @elseif ($request->isApproved())
                                {{ __('Approved by') }} {{ $request->approver?->name ?? '—' }} {{ __('on') }} {{ $request->approved_at?->format('M d, Y \a\t h:i A') ?? '—' }}
                            @elseif ($request->isRejected())
                                {{ __('Rejected by') }} {{ $request->approver?->name ?? '—' }} {{ __('on') }} {{ $request->approved_at?->format('M d, Y \a\t h:i A') ?? '—' }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Request Details --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Request Information') }}</h3>
                </div>
                <div class="p-6">
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Request Date') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $request->created_at->format('F d, Y \a\t h:i A') }}</dd>
                        </div>
                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Project') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                <span class="font-medium">{{ $request->project->project_code }}</span> — {{ $request->project->project_name }}
                            </dd>
                        </div>
                        @if ($request->items->isNotEmpty())
                            <div class="px-4 py-4 sm:px-0">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('Requested Items') }}</dt>
                                <dd class="mt-1">
                                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Material') }}</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Requested Quantity') }}</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Permitted') }}</th>
                                                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                @foreach ($request->items as $item)
                                                    <tr>
                                                        <td class="px-4 py-2 text-gray-900 dark:text-gray-100 font-medium">
                                                            {{ $item->material?->material_name ?? __('Unknown Material') }}
                                                            @if ($item->material?->material_description)
                                                                <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $item->material->material_description }}</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                                {{ $item->quantity }} {{ $item->material?->unitOfMeasure?->name ?? '' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-2 text-gray-700 dark:text-gray-300 font-medium">
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
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Material') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    <span class="font-medium">{{ $request->material?->material_name ?? '—' }}</span>
                                    @if ($request->material?->material_description)
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $request->material->material_description }}</p>
                                    @endif
                                </dd>
                            </div>
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Quantity Requested') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-0.5 text-sm font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        {{ $request->quantity }}
                                    </span>
                                </dd>
                            </div>
                        @endif
                        @if ($request->isApproved() || $request->isPartiallyApproved())
                            <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Permitted Quantity') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-0.5 text-sm font-semibold text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                        {{ $request->permitted_quantity ?? ($request->requestedEmployees->sum('permitted_quantity') ?: 0) }}
                                    </span>
                                </dd>
                            </div>
                        @endif
                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Requested By') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">{{ $request->requester->name }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Employee requests') }}</h3>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Employee') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Req. Qty') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Permitted Qty') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($request->requestedEmployees as $requestedEmployee)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">{{ $requestedEmployee->employee->fullName() }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $requestedEmployee->quantity }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $requestedEmployee->permitted_quantity ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            @if ($requestedEmployee->isApproved())
                                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">{{ __('Approved') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Rejection Reason --}}
            @if ($request->isRejected() && $request->rejection_reason)
                <div class="overflow-hidden rounded-xl bg-red-50 border border-red-200 shadow-sm dark:bg-red-900/20 dark:border-red-800">
                    <div class="border-b border-red-200 px-6 py-4 dark:border-red-800">
                        <h3 class="text-base font-semibold text-red-800 dark:text-red-300">{{ __('Rejection Reason') }}</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ $request->rejection_reason }}</p>
                    </div>
                </div>
            @endif

            {{-- Approval Info --}}
            @if ($request->isApproved())
                <div class="overflow-hidden rounded-xl bg-green-50 border border-green-200 shadow-sm dark:bg-green-900/20 dark:border-green-800">
                    <div class="border-b border-green-200 px-6 py-4 dark:border-green-800">
                        <h3 class="text-base font-semibold text-green-800 dark:text-green-300">{{ __('Approval Information') }}</h3>
                    </div>
                    <div class="p-6">
                        <dl class="divide-y divide-green-100 dark:divide-green-800/50">
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium text-green-600 dark:text-green-400">{{ __('Approved By') }}</dt>
                                <dd class="mt-1 text-sm text-green-900 dark:text-green-100 sm:col-span-2 sm:mt-0">{{ $request->approver?->name ?? '—' }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                <dt class="text-sm font-medium text-green-600 dark:text-green-400">{{ __('Approved At') }}</dt>
                                <dd class="mt-1 text-sm text-green-900 dark:text-green-100 sm:col-span-2 sm:mt-0">{{ $request->approved_at?->format('F d, Y \a\t h:i A') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>