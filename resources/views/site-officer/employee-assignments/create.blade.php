<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Assign Materials to Employees') }}</h2>
    </x-slot>

    <div
        class="py-12"
        x-data="employeeAssignmentForm()"
        @keydown.escape.window="closeHistoryModal()"
    >
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Only materials approved via Material Requests are shown. Select a material, quantity, and date to assign.') }}
                    </p>

                    <form method="POST" action="{{ route('site-officer.employee-assignments.store') }}">
                        @csrf
                        <input type="hidden" name="project_id" :value="projectId">

                        <div class="space-y-6">
                            <div class="sm:col-span-2">
                                <x-input-label for="project_select" :value="__('Site (Project)')" />
                                <select id="project_select" x-model="projectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required @if($defaultProjectId) disabled @endif>
                                    @if(!$defaultProjectId)
                                        <option value="">{{ __('Select project') }}</option>
                                    @endif
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" @selected(old('project_id', $defaultProjectId) == $project->id)>{{ $project->project_code }} — {{ $project->project_name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                                <p x-show="projectId && availableMaterials.length === 0" x-cloak class="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                    ⚠ {{ __('No approved materials available for this project yet.') }}
                                    <a href="{{ route('site-officer.material-requests.create') }}" class="underline">{{ __('Submit a material request.') }}</a>
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Assignments') }}</h3>
                                    <button type="button" @click="addRow()" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Add row') }}</button>
                                </div>

                                <template x-for="(row, index) in rows" :key="row.id">
                                    <div class="grid gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700 sm:grid-cols-3">
                                        {{-- Material (Searchable) --}}
                                        <div class="relative sm:col-span-1" @click.outside="closeMaterialDropdown(index)">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" x-text="'{{ __('Material') }} ' + (index + 1)"></label>
                                            <input
                                                type="text"
                                                x-model="row.materialSearch"
                                                @input="onMaterialSearch(index)"
                                                @focus="row.showMaterialDropdown = true"
                                                autocomplete="off"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                placeholder="{{ __('Search material...') }}"
                                            >
                                            <input type="hidden" :name="'assignments[' + index + '][material_id]'" :value="row.material_id" required>
                                            <ul
                                                x-show="row.showMaterialDropdown && filteredMaterials(row).length > 0"
                                                x-cloak
                                                class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                            >
                                                <template x-for="material in filteredMaterials(row)" :key="material.material_id">
                                                    <li>
                                                        <button
                                                            type="button"
                                                            @click="selectMaterial(index, material)"
                                                            class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-gray-800"
                                                            x-text="material.label"
                                                        ></button>
                                                    </li>
                                                </template>
                                            </ul>
                                            <p x-show="row.showMaterialDropdown && row.materialSearch && filteredMaterials(row).length === 0" x-cloak class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white px-4 py-2 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                                {{ __('No materials found.') }}
                                            </p>
                                            <p x-show="row.material_id" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('Material selected.') }}</p>
                                        </div>

                                        {{-- Quantity --}}
                                        <div class="sm:col-span-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Quantity') }}</label>
                                            <input type="number" min="1" :name="'assignments[' + index + '][quantity]'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        </div>

                                        {{-- Date --}}
                                        <div class="sm:col-span-1">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Date') }}</label>
                                            <input type="date" :name="'assignments[' + index + '][assigned_date]'" :value="today" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        </div>

                                        <div class="sm:col-span-3" x-show="rows.length > 1">
                                            <button type="button" @click="removeRow(index)" class="text-sm text-red-600 hover:text-red-500">{{ __('Remove') }}</button>
                                        </div>
                                    </div>
                                </template>

                                <x-input-error :messages="$errors->get('assignments')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button>{{ __('Assign') }}</x-primary-button>
                            <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('site-officer.partials.employee-history-popup')
    </div>

    @include('site-officer.partials.employee-history-script')

    <script>
        function employeeAssignmentForm() { 
            const balancesByProject = @json($balancesForJs);
            const employeesByProject = @json($employeesByProjectForJs);

            return {
                ...employeeHistoryMixin(),
                projectId: String(@json($defaultProjectId ?? auth()->user()->assigned_project_id ?? auth()->user()->project_id ?? auth()->user()->assignedProject?->id ?? '')),
                rows: [{
                    id: Date.now(),
                    material_id: '',
                    materialSearch: '',
                    showMaterialDropdown: false,
                    employee_id: '',
                    employeeSearch: '',
                    showEmployeeDropdown: false,
                }],
                init() {
                    // Trigger computed properties on load
                    if (this.projectId) {
                        // No additional action needed as computed getters use projectId
                    }
                },
                today: new Date().toISOString().slice(0, 10),
                get availableMaterials() {
                    return balancesByProject[this.projectId] || [];
                },
                get availableEmployees() {
                    return employeesByProject[this.projectId] || [];
                },
                newRow() {
                    return {
                        id: Date.now() + Math.random(),
                        material_id: '',
                        materialSearch: '',
                        showMaterialDropdown: false,
                        employee_id: '',
                        employeeSearch: '',
                        showEmployeeDropdown: false,
                    };
                },
                closeMaterialDropdown(index) {
                    this.rows[index].showMaterialDropdown = false;
                },
                filteredMaterials(row) {
                    const materials = this.availableMaterials;
                    const query = row.materialSearch.toLowerCase().trim();

                    if (! query) {
                        return materials.slice(0, 15);
                    }

                    return materials.filter((material) => material.search.includes(query)).slice(0, 15);
                },
                filteredEmployees(row) {
                    const employees = this.availableEmployees;
                    const query = row.employeeSearch.toLowerCase().trim();

                    if (! query) {
                        return employees.slice(0, 15);
                    }

                    return employees.filter((employee) => employee.search.includes(query)).slice(0, 15);
                },
                onMaterialSearch(index) {
                    this.rows[index].showMaterialDropdown = true;
                    this.rows[index].material_id = '';
                },
                onEmployeeSearch(index) {
                    this.rows[index].showEmployeeDropdown = true;
                    this.rows[index].employee_id = '';
                },
                selectMaterial(index, material) {
                    this.rows[index].material_id = material.material_id;
                    this.rows[index].materialSearch = material.label;
                    this.rows[index].showMaterialDropdown = false;
                },
                selectEmployee(index, employee) {
                    this.rows[index].employee_id = employee.id;
                    this.rows[index].employeeSearch = employee.label;
                    this.rows[index].showEmployeeDropdown = false;
                    this.openHistoryModal(employee.id);
                },
                addRow() {
                    this.rows.push(this.newRow());
                },
                removeRow(index) {
                    this.rows.splice(index, 1);
                },
            };
        };
    </script>
</x-app-layout>