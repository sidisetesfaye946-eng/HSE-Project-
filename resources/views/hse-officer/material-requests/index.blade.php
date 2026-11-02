<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Material Requests') }}</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ __('Review material requests from Site Officers.') }}</p>
            </div>

            @php
                $pendingCount = $requests->where(fn ($materialRequest) => $materialRequest->isPending())->count();
            @endphp

            @if ($pendingCount > 0)
                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    {{ $pendingCount }} {{ __('Pending') }}
                </span>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ showReasonModal: false, modalMaterialName: '', modalReasonText: '', openReasonModal(materialName, reason) { this.modalMaterialName = materialName; this.modalReasonText = reason; this.showReasonModal = true; } }">
        <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-900">
            <form method="GET" action="{{ route('hse-officer.material-requests.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        <option value="">{{ __('All') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                        <option value="partial_approved" @selected(request('status') === 'partial_approved')>{{ __('Partially Approved') }}</option>
                        <option value="approved" @selected(request('status') === 'approved')>{{ __('Approved') }}</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>{{ __('Rejected') }}</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="project_id" :value="__('Project')" />
                    <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                        <option value="">{{ __('All Projects') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) request('project_id') === (string) $project->id)>
                                {{ $project->project_code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="from_date" :value="__('From Date')" />
                    <input id="from_date" name="from_date" type="date" value="{{ request('from_date') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div>
                    <x-input-label for="to_date" :value="__('To Date')" />
                    <input id="to_date" name="to_date" type="date" value="{{ request('to_date') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-cyan-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-cyan-600">
                        {{ __('Filter') }}
                    </button>
                    @if (request()->anyFilled(['status', 'from_date', 'to_date', 'project_id']))
                        <a href="{{ route('hse-officer.material-requests.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            {{ __('Clear') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-900">
            @if ($requests->isEmpty())
                <div class="p-12 text-center">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('No material requests') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Material requests from Site Officers will appear here.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Date') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Requested By') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Project') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Material') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Qty') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Employees') }}</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($requests as $materialRequest)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $materialRequest->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $materialRequest->requester->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $materialRequest->project->project_code }}
                                    </td>
                                     <td class="px-5 py-4 text-sm">
                                        <a href="{{ route('hse-officer.material-requests.show', $materialRequest) }}" class="font-medium text-cyan-700 hover:text-cyan-600 dark:text-cyan-300">
                                            {{ $materialRequest->material_name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $materialRequest->total_quantity }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $materialRequest->requestedEmployees->count() }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                                        @if ($materialRequest->isPending())
                                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">{{ __('Pending') }}</span>
                                        @elseif ($materialRequest->isPartiallyApproved())
                                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ __('Partially Approved') }}</span>
                                        @elseif ($materialRequest->isApproved())
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">{{ __('Approved') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">{{ __('Rejected') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                        <a href="{{ route('hse-officer.material-requests.show', $materialRequest) }}" class="font-medium text-cyan-700 hover:text-cyan-600 dark:text-cyan-300">
                                            {{ __('View') }}
                                        </a>
                                        @if ($materialRequest->isRejected() && $materialRequest->rejection_reason)
                                            <button type="button" @click="openReasonModal('{{ e($materialRequest->material_name) }}', '{{ e($materialRequest->rejection_reason) }}')" class="ml-2 inline-flex items-center rounded-md bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-300">
                                                {{ __('View Reason') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Rejection Reason Modal --}}
        <div x-show="showReasonModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showReasonModal = false"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-lg bg-white p-6 text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100" id="modal-title">
                                {{ __('Rejection Reason') }} — <span x-text="modalMaterialName"></span>
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-300" x-text="modalReasonText"></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="showReasonModal = false" class="inline-flex w-full justify-center rounded-md border border-transparent bg-cyan-700 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-cyan-600 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
