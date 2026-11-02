<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('My Material Requests') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Track your material requests and their approval status') }}</p>
            </div>
            <a href="{{ route('site-officer.material-requests.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('New Request') }}</a>
        </div>
    </x-slot>

    <div class="py-8 space-y-6" x-data="{ showReasonModal: false, modalMaterialName: '', modalReasonText: '', openReasonModal(materialName, reason) { this.modalMaterialName = materialName; this.modalReasonText = reason; this.showReasonModal = true; } }">
        {{-- Filters --}}
        <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800" x-data="{ showFilters: false }">
            <div class="flex items-center justify-between">
                <button type="button" @click="showFilters = !showFilters" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591L16.3 10.44A2.25 2.25 0 0 0 15.5 12v3.75a1.5 1.5 0 0 1-.879 1.366l-2.25 1.125a1.5 1.5 0 0 1-2.121-1.366V12a2.25 2.25 0 0 0-.8-1.56L4.659 6.409A2.25 2.25 0 0 1 4 4.818V3.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                    </svg>
                    {{ __('Filters') }}
                    <span x-show="showFilters" class="text-xs text-gray-400 dark:text-gray-500">({{ __('click to hide') }})</span>
                    <span x-show="!showFilters" class="text-xs text-gray-400 dark:text-gray-500">({{ __('click to show') }})</span>
                </button>
                @if (request()->anyFilled(['status', 'from_date', 'to_date']))
                    <a href="{{ route('site-officer.material-requests.index') }}" class="text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Clear filters') }}</a>
                @endif
            </div>

            <form method="GET" action="{{ route('site-officer.material-requests.index') }}" x-show="showFilters" x-cloak x-transition class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <option value="">{{ __('All') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                        <option value="partial_approved" @selected(request('status') === 'partial_approved')>{{ __('Partially Approved') }}</option>
                        <option value="approved" @selected(request('status') === 'approved')>{{ __('Approved') }}</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>{{ __('Rejected') }}</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="from_date" :value="__('From Date')" />
                    <input id="from_date" name="from_date" type="date" value="{{ request('from_date') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                </div>
                <div>
                    <x-input-label for="to_date" :value="__('To Date')" />
                    <input id="to_date" name="to_date" type="date" value="{{ request('to_date') }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('Filter') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Requests Table --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
            @if ($requests->isEmpty())
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                    </svg>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        @if (request()->anyFilled(['status', 'from_date', 'to_date']))
                            {{ __('No material requests match your filters.') }}
                            <a href="{{ route('site-officer.material-requests.index') }}" class="ml-1 text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Clear filters') }}</a>
                        @else
                            {{ __('No material requests yet.') }}
                            <a href="{{ route('site-officer.material-requests.create') }}" class="ml-1 text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Create your first request') }}</a>
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Project') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Quantity') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Approved By') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Employees') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @foreach ($requests as $request)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $request->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $request->project->project_code }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <a href="{{ route('site-officer.material-requests.show', $request) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition-colors duration-150">
                                            {{ $request->material_name }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $request->total_quantity }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        @if ($request->isPending())
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                {{ __('Pending') }}
                                            </span>
                                        @elseif ($request->isPartiallyApproved())
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                {{ __('Partially Approved') }}
                                            </span>
                                        @elseif ($request->isApproved())
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                {{ __('Approved') }}
                                            </span>
                                        @elseif ($request->isRejected())
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                {{ __('Rejected') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->approver?->name ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $request->requestedEmployees->count() }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        @if ($request->isRejected() && $request->rejection_reason)
                                            <button type="button" @click="openReasonModal('{{ e($request->material_name) }}', '{{ e($request->rejection_reason) }}')" class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50">
                                                {{ __('View Reason') }}
                                            </button>
                                        @else
                                            <a href="{{ route('site-officer.material-requests.show', $request) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                                {{ __('Details') }}
                                            </a>
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
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                        <button type="button" @click="showReasonModal = false" class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>