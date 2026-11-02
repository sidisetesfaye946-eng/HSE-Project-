{{-- Requires parent Alpine scope: historyModalOpen, historyLoading, historyEmployee, historyAssignments, closeHistoryModal() --}}
<div
    x-show="historyModalOpen"
    x-cloak
    class="fixed inset-0 z-[100] overflow-y-auto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="employee-history-title"
>
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="historyModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="fixed inset-0 bg-gray-900/60"
            @click="closeHistoryModal()"
        ></div>

        <div
            x-show="historyModalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            class="relative z-[101] w-full max-w-2xl rounded-xl bg-white shadow-2xl dark:bg-gray-800"
            @click.stop
        >
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="employee-history-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="historyEmployee?.name ?? '{{ __('Employee assignment history') }}'"></h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-show="historyEmployee" x-text="historyEmployee?.job_title"></p>
                    </div>
                    <button
                        type="button"
                        @click="closeHistoryModal()"
                        class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    >
                        <span class="sr-only">{{ __('Close') }}</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="max-h-[60vh] overflow-y-auto px-6 py-4">
                <p x-show="historyLoading" class="text-sm text-gray-500 dark:text-gray-400">{{ __('Loading assignment history...') }}</p>

                <p x-show="! historyLoading && historyAssignments.length === 0" class="text-sm text-gray-500 dark:text-gray-400">{{ __('No previous assignments found for this employee on your sites.') }}</p>

                <div x-show="! historyLoading && historyAssignments.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Date') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Project') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Material') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">{{ __('Quantity') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <template x-for="(assignment, idx) in historyAssignments" :key="idx">
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300" x-text="assignment.date"></td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-300">
                                            <span class="font-medium" x-text="assignment.project_code"></span>
                                            <span class="block text-xs text-gray-400" x-text="assignment.project_name"></span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100" x-text="assignment.material"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-300" x-text="assignment.quantity"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                </div>
            </div>

            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <button
                    type="button"
                    @click="closeHistoryModal()"
                    class="inline-flex w-full justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 sm:w-auto"
                >
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>
</div>
