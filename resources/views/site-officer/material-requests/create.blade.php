<x-app-layout>
    <style>
        [x-cloak] {
            display: none !important;
        }
        input[type='number']::-webkit-inner-spin-button,
        input[type='number']::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type='number'] {
            -moz-appearance: textfield;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Create Material Request') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Request safety gear and materials for site employees') }}</p>
            </div>
            <a href="{{ route('site-officer.material-requests.index') }}" class="inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">{{ __('My Requests') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        {{-- Flash Session / Error Alerts --}}
        @if (session('error'))
            <div class="mb-6 rounded-md bg-red-50 p-4 dark:bg-red-900/50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            {{ session('error') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-xl bg-white p-8 shadow-sm dark:bg-gray-800" x-data="{ activeTab: '{{ $errors->has('file') || session('error') ? 'upload' : 'manual' }}' }">
            
            {{-- Tab Navigation --}}
            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button
                        type="button"
                        @click="activeTab = 'manual'"
                        :class="activeTab === 'manual' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm focus:outline-none"
                    >
                        {{ __('Request Manually') }}
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'upload'"
                        :class="activeTab === 'upload' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 font-semibold' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm focus:outline-none"
                    >
                        {{ __('Import / Upload Request') }}
                    </button>
                </nav>
            </div>

            {{-- Manual Request Form --}}
            <div x-show="activeTab === 'manual'" x-data="materialRequestForm()">
                <form method="POST" action="{{ route('site-officer.material-requests.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- Project --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="project_id" :value="__('Project')" />
                            <div x-show="loadingProjects" x-cloak class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400">
                                <svg class="h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ __('Loading projects...') }}</span>
                            </div>
                        </div>
                        <select id="project_id" name="project_id" x-model="projectId" @change="resetEmployeeRows()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:focus:border-indigo-400" required>
                            <option value="" x-show="loadingProjects">{{ __('Loading projects...') }}</option>
                            <option value="" x-show="!loadingProjects && projects.length === 0">{{ __('No projects available') }}</option>
                            <option value="" x-show="!loadingProjects && projects.length > 0 && !projectId">-- {{ __('Select Project') }} --</option>
                            <template x-for="project in projects" :key="project.id">
                                <option :value="String(project.id)" x-text="(project.project_code ? project.project_code + ' — ' : '') + project.project_name"></option>
                            </template>
                        </select>
                        <p x-show="projectsError" x-cloak class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="projectsError"></p>
                        <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                    </div>

                    {{-- Requested Materials (Dynamic Item List) --}}
                    <div class="space-y-4 rounded-xl border border-gray-200 bg-gray-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Requested Materials') }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Add one or more materials to request under this submission') }}</p>
                            </div>
                            <button
                                type="button"
                                @click="addMaterialItem()"
                                class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                {{ __('+ Add Item') }}
                            </button>
                        </div>

                        <template x-for="(item, itemIndex) in materialItems" :key="item.id">
                            <div class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-12 sm:items-start">
                                {{-- Material Searchable Dropdown --}}
                                <div class="relative sm:col-span-7" @click.outside="item.showDropdown = false">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Material') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        x-model="item.search"
                                        @input="onMaterialSearch(itemIndex)"
                                        @focus="item.showDropdown = true"
                                        autocomplete="off"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:focus:border-indigo-400"
                                        placeholder="{{ __('Select or search material...') }}"
                                        required
                                    >
                                    <input type="hidden" :name="'items[' + itemIndex + '][material_id]'" :value="item.material_id" required>

                                    <ul
                                        x-show="item.showDropdown && filteredMaterialsForItem(item).length > 0"
                                        x-cloak
                                        class="absolute z-30 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <template x-for="material in filteredMaterialsForItem(item)" :key="material.id">
                                            <li>
                                                <button
                                                    type="button"
                                                    @mousedown.prevent.stop="selectMaterialForItem(itemIndex, material)"
                                                    @click.prevent.stop="selectMaterialForItem(itemIndex, material)"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-gray-800"
                                                    x-text="material.label"
                                                ></button>
                                            </li>
                                        </template>
                                    </ul>

                                    <p x-show="item.showDropdown && item.search && filteredMaterialsForItem(item).length === 0" x-cloak class="absolute z-30 mt-1 w-full rounded-md border border-gray-200 bg-white px-4 py-2 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        {{ __('No materials found.') }}
                                    </p>
                                    <p x-show="item.material_id" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('Material selected.') }}</p>
                                </div>

                                {{-- Quantity --}}
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Quantity') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        :name="'items[' + itemIndex + '][quantity]'"
                                        x-model.number="item.quantity"
                                        value="1"
                                        @wheel.prevent="$event.target.blur()"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                        required
                                    >
                                </div>

                                {{-- Remove Button --}}
                                <div class="flex items-center justify-end sm:col-span-2 sm:pt-6">
                                    <button
                                        type="button"
                                        x-show="materialItems.length > 1"
                                        @click="removeMaterialItem(itemIndex)"
                                        class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/30"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        {{ __('Remove') }}
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between pt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ __('Total materials:') }} <strong class="text-gray-900 dark:text-white" x-text="materialItems.length"></strong></span>
                            <span>{{ __('Total item quantity:') }} <strong class="text-gray-900 dark:text-white" x-text="totalItemsQuantity"></strong></span>
                        </div>
                        <x-input-error :messages="$errors->get('items')" class="mt-2" />
                    </div>

                    {{-- Employee Requests --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <x-input-label :value="__('Employee requests')" />
                            <button type="button" @click="addEmployeeRow()" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('Add row') }}</button>
                        </div>

                        <template x-for="(row, index) in employeeRows" :key="row.id">
                            <div class="grid gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700 sm:grid-cols-5">
                                <div class="relative sm:col-span-3" @click.outside="row.showEmployeeDropdown = false">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Employee name') }}</label>
                                    <input
                                        type="text"
                                        x-model="row.search"
                                        @input="onEmployeeSearch(index)"
                                        @focus="row.showEmployeeDropdown = true"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                        placeholder="{{ __('Search or type employee name...') }}"
                                    >

                                    <input type="hidden" :name="'employee_requests[' + index + '][employee_id]'" :value="row.employee_id || ''">
                                    <input type="hidden" :name="'employee_requests[' + index + '][employee_name]'" :value="row.employee_name || ''">

                                    <ul
                                        x-show="row.showEmployeeDropdown && filteredEmployees(row).length > 0"
                                        x-cloak
                                        class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <template x-for="employee in filteredEmployees(row)" :key="employee.id">
                                            <li>
                                                <button
                                                    type="button"
                                                    @mousedown.prevent.stop="selectEmployee(index, employee)"
                                                    @click.prevent.stop="selectEmployee(index, employee)"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-gray-800"
                                                    x-text="employee.label"
                                                ></button>
                                            </li>
                                        </template>
                                    </ul>

                                    <div x-show="row.showEmployeeDropdown && row.search.trim() && filteredEmployees(row).length === 0" x-cloak class="absolute z-20 mt-1 w-full rounded-md border border-gray-200 bg-white p-3 text-sm text-gray-600 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <p>{{ __('Employee not found.') }}</p>
                                        <button
                                            type="button"
                                            @mousedown.prevent.stop="addTypedEmployee(index)"
                                            @click.prevent.stop="addTypedEmployee(index)"
                                            class="mt-2 inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                        >
                                            {{ __('Add this employee') }}
                                        </button>
                                    </div>
                                    <p x-show="row.employee_id" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('Selected employee from list.') }}</p>
                                    <p x-show="row.employee_name && !row.employee_id" class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">{{ __('New employee to create:') }} <span class="font-semibold" x-text="row.employee_name"></span></p>
                                </div>

                                <div class="sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Requested Quantity') }}</label>
                                    <input type="number" min="1" :name="'employee_requests[' + index + '][quantity]'" x-model="row.quantity" value="1" @wheel.prevent="$event.target.blur()" @dragstart.prevent onwheel="this.blur()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300" required>
                                </div>

                                <div class="flex items-end justify-end sm:col-span-1" x-show="employeeRows.length > 1">
                                    <button type="button" @click="removeEmployeeRow(index)" class="text-sm font-medium text-red-600 hover:text-red-500">{{ __('Remove') }}</button>
                                </div>
                            </div>
                        </template>

                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Total requested quantity:') }} <span class="font-semibold" x-text="totalQuantity"></span></p>
                        <x-input-error :messages="$errors->get('employee_requests')" class="mt-2" />
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="description" :value="__('Description / Justification')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:focus:border-indigo-400" placeholder="{{ __('Optional justification or details for the HSE Officer...') }}">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('site-officer.material-requests.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">{{ __('Cancel') }}</a>
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Import/Upload Form --}}
            <div x-show="activeTab === 'upload'" x-cloak x-data="importRequestForm()" class="space-y-6">
                <form method="POST" action="{{ route('site-officer.material-requests.import') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- Project --}}
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="upload_project_id" :value="__('Project')" />
                            <div x-show="loadingProjects" x-cloak class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400">
                                <svg class="h-4 w-4 animate-spin text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ __('Loading projects...') }}</span>
                            </div>
                        </div>
                        <select id="upload_project_id" name="project_id" x-model="projectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:focus:border-indigo-400" required>
                            <option value="" x-show="loadingProjects">{{ __('Loading projects...') }}</option>
                            <option value="" x-show="!loadingProjects && projects.length === 0">{{ __('No projects available') }}</option>
                            <option value="" x-show="!loadingProjects && projects.length > 0 && !projectId">-- {{ __('Select Project') }} --</option>
                            <template x-for="project in projects" :key="project.id">
                                <option :value="String(project.id)" x-text="(project.project_code ? project.project_code + ' — ' : '') + project.project_name"></option>
                            </template>
                        </select>
                        <p x-show="projectsError" x-cloak class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="projectsError"></p>
                        <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                    </div>

                    {{-- Requested Materials (Dynamic Item List) --}}
                    <div class="space-y-4 rounded-xl border border-gray-200 bg-gray-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Requested Materials') }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Add one or more materials to request alongside the optional spreadsheet') }}</p>
                            </div>
                            <button
                                type="button"
                                @click="addMaterialItem()"
                                class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                {{ __('+ Add Item') }}
                            </button>
                        </div>

                        <template x-for="(item, itemIndex) in materialItems" :key="item.id">
                            <div class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:grid-cols-12 sm:items-start">
                                {{-- Material Searchable Dropdown --}}
                                <div class="relative sm:col-span-7" @click.outside="item.showDropdown = false">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Material') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        x-model="item.search"
                                        @input="onMaterialSearch(itemIndex)"
                                        @focus="item.showDropdown = true"
                                        autocomplete="off"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:focus:border-indigo-400"
                                        placeholder="{{ __('Select or search material...') }}"
                                        required
                                    >
                                    <input type="hidden" :name="'items[' + itemIndex + '][material_id]'" :value="item.material_id" required>

                                    <ul
                                        x-show="item.showDropdown && filteredMaterialsForItem(item).length > 0"
                                        x-cloak
                                        class="absolute z-30 mt-1 max-h-48 w-full overflow-auto rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                                    >
                                        <template x-for="material in filteredMaterialsForItem(item)" :key="material.id">
                                            <li>
                                                <button
                                                    type="button"
                                                    @mousedown.prevent.stop="selectMaterialForItem(itemIndex, material)"
                                                    @click.prevent.stop="selectMaterialForItem(itemIndex, material)"
                                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-200 dark:hover:bg-gray-800"
                                                    x-text="material.label"
                                                ></button>
                                            </li>
                                        </template>
                                    </ul>

                                    <p x-show="item.showDropdown && item.search && filteredMaterialsForItem(item).length === 0" x-cloak class="absolute z-30 mt-1 w-full rounded-md border border-gray-200 bg-white px-4 py-2 text-sm text-gray-500 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                        {{ __('No materials found.') }}
                                    </p>
                                    <p x-show="item.material_id" class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">{{ __('Material selected.') }}</p>
                                </div>

                                {{-- Quantity --}}
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ __('Quantity') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        :name="'items[' + itemIndex + '][quantity]'"
                                        x-model.number="item.quantity"
                                        value="1"
                                        @wheel.prevent="$event.target.blur()"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                        required
                                    >
                                </div>

                                {{-- Remove Button --}}
                                <div class="flex items-center justify-end sm:col-span-2 sm:pt-6">
                                    <button
                                        type="button"
                                        x-show="materialItems.length > 1"
                                        @click="removeMaterialItem(itemIndex)"
                                        class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/30"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        {{ __('Remove') }}
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-between pt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ __('Total materials:') }} <strong class="text-gray-900 dark:text-white" x-text="materialItems.length"></strong></span>
                            <span>{{ __('Total item quantity:') }} <strong class="text-gray-900 dark:text-white" x-text="totalItemsQuantity"></strong></span>
                        </div>
                        <x-input-error :messages="$errors->get('items')" class="mt-2" />
                    </div>

                    {{-- File Input (Optional) --}}
                    <div>
                        <x-input-label for="file" :value="__('Spreadsheet File (Optional: XLS, XLSX)')" />
                        <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input
                                id="file"
                                name="file"
                                type="file"
                                accept=".xls,.xlsx"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300 dark:text-gray-400"
                            >
                            <a href="{{ route('site-officer.material-requests.import.template') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('Download Template') }}
                            </a>
                        </div>
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Optional employee list spreadsheet. Required columns: Employee-Name, Title, Project.') }}
                        </p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="upload_description" :value="__('Description / Notes')" />
                        <textarea
                            id="upload_description"
                            name="description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:focus:border-indigo-400"
                            placeholder="{{ __('Any details or remarks about this request...') }}"
                        >{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <a href="{{ route('site-officer.material-requests.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">{{ __('Cancel') }}</a>
                        <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            {{ __('Submit Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function materialRequestForm() {
            const initialProjects = @json($projects);
            const allMaterials = @json($materialsForJs);
            const allEmployees = @json($allEmployeesForJs);
            const userAssignedProjId = String(@json(auth()->user()->assigned_project_id ?? auth()->user()->project_id ?? auth()->user()->assignedProject?->id ?? ''));
            const defaultProjId = String(@json($defaultProjectId ?? ''));

            return {
                projects: initialProjects || [],
                loadingProjects: false,
                projectsError: null,
                projectId: String(userAssignedProjId || defaultProjId || @json(old('project_id'))),
                materialItems: [{
                    id: Date.now(),
                    material_id: '',
                    search: '',
                    quantity: 1,
                    showDropdown: false,
                }],
                employeeRows: [{
                    id: Date.now(),
                    employee_id: '',
                    employee_name: '',
                    search: '',
                    quantity: 1,
                    showEmployeeDropdown: false,
                }],

                async init() {
                    await this.fetchProjects();
                },

                async fetchProjects() {
                    this.loadingProjects = true;
                    this.projectsError = null;

                    try {
                        // Attempt to fetch fresh project list from backend endpoint
                        const response = await fetch("{{ route('site-officer.projects.index') }}", {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`Failed to load projects (Status: ${response.status})`);
                        }

                        const data = await response.json();
                        const projectList = Array.isArray(data) ? data : (data.projects || data.data || initialProjects);
                        this.projects = projectList;

                        // Auto-select assigned project if available
                        const targetId = userAssignedProjId || defaultProjId;
                        if (targetId && this.projects.some(p => String(p.id) === String(targetId))) {
                            this.projectId = String(targetId);
                        } else if (this.projects.length > 0 && !this.projectId) {
                            this.projectId = String(this.projects[0].id);
                        }
                    } catch (error) {
                        console.error("Error fetching projects:", error);
                        // Fallback to server-rendered project list if API fetch fails
                        this.projects = initialProjects || [];
                        const targetId = userAssignedProjId || defaultProjId;
                        if (targetId && this.projects.some(p => String(p.id) === String(targetId))) {
                            this.projectId = String(targetId);
                        } else if (this.projects.length === 0) {
                            this.projectsError = "Unable to fetch project list. Please refresh the page.";
                        }
                    } finally {
                        this.loadingProjects = false;
                    }
                },

                filteredMaterialsForItem(item) {
                    const query = (item.search || '').toLowerCase().trim();

                    if (! query) {
                        return allMaterials;
                    }

                    return allMaterials.filter((material) => material.search.includes(query));
                },

                onMaterialSearch(index) {
                    const item = this.materialItems[index];
                    item.showDropdown = true;
                    item.material_id = '';
                },

                selectMaterialForItem(index, material) {
                    const item = this.materialItems[index];
                    item.material_id = material.id;
                    item.search = material.label;
                    item.showDropdown = false;
                },

                addMaterialItem() {
                    this.materialItems.push({
                        id: Date.now() + Math.random(),
                        material_id: '',
                        search: '',
                        quantity: 1,
                        showDropdown: false,
                    });
                },

                removeMaterialItem(index) {
                    if (this.materialItems.length > 1) {
                        this.materialItems.splice(index, 1);
                    }
                },

                get totalItemsQuantity() {
                    return this.materialItems.reduce((total, item) => total + Number(item.quantity || 0), 0);
                },
                get availableEmployees() {
                    return allEmployees;
                },
                filteredEmployees(row) {
                    const query = row.search.toLowerCase().trim();

                    if (! query) {
                        return this.availableEmployees.slice(0, 15);
                    }

                    return this.availableEmployees.filter((employee) => employee.search.includes(query)).slice(0, 15);
                },
                onEmployeeSearch(index) {
                    const row = this.employeeRows[index];
                    row.showEmployeeDropdown = true;
                    row.employee_id = '';
                    row.employee_name = row.search.trim();
                },
                selectEmployee(index, employee) {
                    const row = this.employeeRows[index];
                    row.employee_id = employee.id;
                    row.employee_name = '';
                    row.search = employee.name;
                    row.showEmployeeDropdown = false;
                },
                addTypedEmployee(index) {
                    const row = this.employeeRows[index];
                    const typedName = row.search.trim();

                    if (! typedName) {
                        return;
                    }

                    row.employee_id = '';
                    row.employee_name = typedName;
                    row.search = typedName;
                    row.showEmployeeDropdown = false;
                },
                addEmployeeRow() {
                    this.employeeRows.push({
                        id: Date.now() + Math.random(),
                        employee_id: '',
                        employee_name: '',
                        search: '',
                        quantity: 1,
                        showEmployeeDropdown: false,
                    });
                },
                removeEmployeeRow(index) {
                    this.employeeRows.splice(index, 1);
                },
                get totalQuantity() {
                    return this.employeeRows.reduce((total, row) => total + Number(row.quantity || 0), 0);
                },
                resetEmployeeRows() {
                    this.employeeRows = [{
                        id: Date.now(),
                        employee_id: '',
                        employee_name: '',
                        search: '',
                        quantity: 1,
                        showEmployeeDropdown: false,
                    }];
                },
            };
        }

        function importRequestForm() {
            const initialProjects = @json($projects);
            const allMaterials = @json($allMaterialsForJs);
            const userAssignedProjId = String(@json(auth()->user()->assigned_project_id ?? auth()->user()->project_id ?? auth()->user()->assignedProject?->id ?? ''));
            const defaultProjId = String(@json($defaultProjectId ?? ''));

            return {
                projects: initialProjects || [],
                loadingProjects: false,
                projectsError: null,
                projectId: String(userAssignedProjId || defaultProjId || @json(old('project_id'))),
                materialItems: [{
                    id: Date.now(),
                    material_id: '',
                    search: '',
                    quantity: 1,
                    showDropdown: false,
                }],

                async init() {
                    await this.fetchProjects();
                },

                async fetchProjects() {
                    this.loadingProjects = true;
                    this.projectsError = null;

                    try {
                        const response = await fetch("{{ route('site-officer.projects.index') }}", {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error(`Failed to load projects (Status: ${response.status})`);
                        }

                        const data = await response.json();
                        const projectList = Array.isArray(data) ? data : (data.projects || data.data || initialProjects);
                        this.projects = projectList;
                        console.log("Projects loaded (import):", this.projects);

                        const targetId = userAssignedProjId || defaultProjId;
                        if (targetId && this.projects.some(p => String(p.id) === String(targetId))) {
                            this.projectId = String(targetId);
                        } else if (this.projects.length > 0 && !this.projectId) {
                            this.projectId = String(this.projects[0].id);
                        }
                    } catch (error) {
                        console.error("Error fetching projects (import):", error);
                        this.projects = initialProjects || [];
                        console.log("Projects loaded (import fallback):", this.projects);
                        if (this.projects.length === 0) {
                            this.projectsError = "Unable to fetch project list. Please refresh the page.";
                        }
                    } finally {
                        this.loadingProjects = false;
                    }
                },

                filteredMaterialsForItem(item) {
                    const query = (item.search || '').toLowerCase().trim();

                    if (! query) {
                        return allMaterials;
                    }

                    return allMaterials.filter((material) => material.search.includes(query));
                },

                onMaterialSearch(index) {
                    const item = this.materialItems[index];
                    item.showDropdown = true;
                    item.material_id = '';
                },

                selectMaterialForItem(index, material) {
                    const item = this.materialItems[index];
                    item.material_id = material.id;
                    item.search = material.label;
                    item.showDropdown = false;
                },

                addMaterialItem() {
                    this.materialItems.push({
                        id: Date.now() + Math.random(),
                        material_id: '',
                        search: '',
                        quantity: 1,
                        showDropdown: false,
                    });
                },

                removeMaterialItem(index) {
                    if (this.materialItems.length > 1) {
                        this.materialItems.splice(index, 1);
                    }
                },

                get totalItemsQuantity() {
                    return this.materialItems.reduce((total, item) => total + Number(item.quantity || 0), 0);
                },
            };
        }

        document.addEventListener('wheel', function (e) {
            if (document.activeElement && document.activeElement.type === 'number') {
                document.activeElement.blur();
            }
        }, { passive: false });

        document.addEventListener('dragstart', function (e) {
            if (e.target && e.target.type === 'number') {
                e.preventDefault();
            }
        });
    </script>
</x-app-layout>