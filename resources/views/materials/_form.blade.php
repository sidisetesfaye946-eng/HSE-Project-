<div class="space-y-6">
    <div>
        <x-input-label for="material_name" :value="__('Material Name')" />
        <x-text-input id="material_name" name="material_name" type="text" class="mt-1 block w-full" :value="old('material_name', $material->material_name)" required autofocus />
        <x-input-error :messages="$errors->get('material_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="material_description" :value="__('Material Description')" />
        <textarea id="material_description" name="material_description" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>{{ old('material_description', $material->material_description) }}</textarea>
        <x-input-error :messages="$errors->get('material_description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="unit_of_measure_id" :value="__('Unit of Measure')" />
        <select id="unit_of_measure_id" name="unit_of_measure_id" class="mt-1 block w-full rounded-md border-gray-300 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
            <option value="">{{ __('Select a unit') }}</option>
            @foreach($unitOfMeasures as $unitOfMeasure)
                <option value="{{ $unitOfMeasure->id }}" @selected(old('unit_of_measure_id', $material->unit_of_measure_id) == $unitOfMeasure->id)>{{ $unitOfMeasure->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('unit_of_measure_id')" class="mt-2" />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Choose the unit used to track this material quantity.') }}</p>
    </div>

    <div>
        <x-input-label for="quantity" :value="__('Head Office Quantity')" />
        <x-text-input id="quantity" name="quantity" type="number" min="0" class="mt-1 block w-full" :value="old('quantity', $material->quantity ?? 0)" required />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Current stock available in Head Office for this material.') }}</p>
        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />

        @if ($material->exists)
            @php
                $distributedToProjects = (int) $material->assignments()->sum('quantity');
            @endphp
            <div class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50/60 p-3.5 text-xs text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-300">
                <div class="flex items-center gap-2 font-semibold">
                    <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <span>{{ __('Stock Pool Synchronization') }}</span>
                </div>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Total Distributed to Projects:') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $distributedToProjects }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">{{ __('Current Base Pool (Initial Stock):') }}</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $material->initial_stock }}</span>
                    </div>
                </div>
                <p class="mt-2 text-[11px] text-gray-600 dark:text-gray-400">
                    {{ __('Saving will automatically recalculate the base stock pool as (Head Office Quantity + Distributed Stock), keeping the Head Office Material Report available balance accurate.') }}
                </p>
            </div>
        @endif
    </div>
</div>
