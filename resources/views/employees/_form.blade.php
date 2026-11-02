<div class="space-y-6">
    <div>
        <x-input-label for="employee_name" :value="__('Employee Name')" />
        <x-text-input id="employee_name" name="employee_name" type="text" class="mt-1 block w-full" :value="old('employee_name', old('name', $employee->name ?? $employee->fullName()))" required autofocus placeholder="e.g. John Doe" />
        <x-input-error :messages="$errors->get('employee_name')" class="mt-2" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="project_id" :value="__('Project')" />
        @if (isset($projects) && $projects->isNotEmpty())
            <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                <option value="">{{ __('Select Project') }}</option>
                @foreach ($projects as $proj)
                    <option value="{{ $proj->id }}" @selected(old('project_id', $employee->project_id) == $proj->id || old('project', $employee->project) == $proj->project_name)>
                        {{ $proj->project_name }} ({{ $proj->project_code }})
                    </option>
                @endforeach
            </select>
        @else
            <x-text-input id="project" name="project" type="text" class="mt-1 block w-full" :value="old('project', $employee->project)" required placeholder="e.g. Alpha Project" />
        @endif
        <x-input-error :messages="$errors->get('project')" class="mt-2" />
        <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="material_id" :value="__('Material Type')" />
        @if (isset($materials) && $materials->isNotEmpty())
            <select id="material_id" name="material_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                <option value="">{{ __('Select Material Type') }}</option>
                @foreach ($materials as $mat)
                    <option value="{{ $mat->id }}" @selected(old('material_id', $employee->material_id) == $mat->id || old('material_type', $employee->material_type) == $mat->material_name)>
                        {{ $mat->material_name }}
                    </option>
                @endforeach
            </select>
        @else
            <x-text-input id="material_type" name="material_type" type="text" class="mt-1 block w-full" :value="old('material_type', $employee->material_type)" required placeholder="e.g. Safety Helmet" />
        @endif
        <x-input-error :messages="$errors->get('material_type')" class="mt-2" />
        <x-input-error :messages="$errors->get('material_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date" :value="__('Date')" />
        <x-text-input id="date" name="date" type="date" class="mt-1 block w-full" :value="old('date', $employee->date ?? date('Y-m-d'))" required />
        <x-input-error :messages="$errors->get('date')" class="mt-2" />
    </div>
</div>
