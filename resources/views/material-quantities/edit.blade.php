<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Adjust Head Office Quantity') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="mb-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <span class="block text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Material') }}</span>
                                <span class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $material->material_name }}</span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Head Office Balance') }}</span>
                                <span class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $material->quantity }} {{ $material->unitOfMeasure?->name }}</span>
                            </div>
                            <div>
                                <span class="block text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Base Pool (Initial Stock)') }}</span>
                                <span class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $material->initial_stock }}</span>
                            </div>
                        </div>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('material-quantities.update', $material) }}"
                        x-data="{
                            type: '{{ old('adjustment_type', 'add') }}',
                            amount: '{{ old('quantity_to_add', '') }}',
                            current: {{ (int) $material->quantity }},
                            get netChange() {
                                const val = parseInt(this.amount, 10);
                                if (isNaN(val)) return 0;
                                if (this.type === 'subtract') return -Math.abs(val);
                                if (this.type === 'add' && val < 0) return val;
                                return val;
                            },
                            get newBalance() {
                                return this.current + this.netChange;
                            },
                            get isInvalid() {
                                return this.amount !== '' && !isNaN(parseInt(this.amount, 10)) && this.newBalance < 0;
                            }
                        }"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="space-y-6">
                            <div>
                                <x-input-label :value="__('Adjustment Action')" />
                                <div class="mt-2 grid grid-cols-2 gap-3">
                                    <label
                                        class="flex items-center justify-center gap-2 rounded-lg border p-3 cursor-pointer transition"
                                        :class="type === 'add' ? 'border-emerald-500 bg-emerald-50/70 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200 dark:border-emerald-600 font-semibold shadow-sm' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300'"
                                    >
                                        <input type="radio" name="adjustment_type" value="add" x-model="type" class="sr-only">
                                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        <span>{{ __('Add Stock (+)') }}</span>
                                    </label>

                                    <label
                                        class="flex items-center justify-center gap-2 rounded-lg border p-3 cursor-pointer transition"
                                        :class="type === 'subtract' ? 'border-rose-500 bg-rose-50/70 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200 dark:border-rose-600 font-semibold shadow-sm' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300'"
                                    >
                                        <input type="radio" name="adjustment_type" value="subtract" x-model="type" class="sr-only">
                                        <svg class="h-5 w-5 text-rose-600 dark:text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                        </svg>
                                        <span>{{ __('Deduct / Reduce Stock (-)') }}</span>
                                    </label>
                                </div>
                                <x-input-error :messages="$errors->get('adjustment_type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="quantity_to_add" :value="__('Quantity Amount')" />
                                <x-text-input
                                    id="quantity_to_add"
                                    name="quantity_to_add"
                                    type="number"
                                    class="mt-1 block w-full"
                                    x-model="amount"
                                    placeholder="e.g. 137"
                                    required
                                    autofocus
                                />
                                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Enter the amount to add or subtract. You can also enter a negative adjustment directly (e.g. -137).') }}
                                </p>
                                <x-input-error :messages="$errors->get('quantity_to_add')" class="mt-2" />
                            </div>

                            {{-- Live preview calculation --}}
                            <div
                                x-show="amount !== '' && !isNaN(parseInt(amount, 10))"
                                class="rounded-lg border p-4 text-xs transition"
                                :class="isInvalid ? 'border-rose-300 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300' : 'border-indigo-100 bg-indigo-50/60 text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-200'"
                            >
                                <div class="flex items-center justify-between font-semibold">
                                    <span>{{ __('Balance Preview') }}:</span>
                                    <span class="text-sm">
                                        <span x-text="current"></span>
                                        <span x-text="netChange >= 0 ? ' + ' + netChange : ' - ' + Math.abs(netChange)"></span>
                                        <span> = </span>
                                        <span x-text="newBalance" class="font-bold underline"></span>
                                    </span>
                                </div>
                                <template x-if="isInvalid">
                                    <p class="mt-2 font-medium text-rose-600 dark:text-rose-400">
                                        {{ __('Reduction cannot exceed current Head Office balance.') }}
                                    </p>
                                </template>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button ::disabled="isInvalid">{{ __('Update Balance') }}</x-primary-button>
                            <a href="{{ route('material-quantities.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
