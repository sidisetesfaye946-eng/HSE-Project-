<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Add Employees') }} — {{ $project->project_name }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Select the employees to add to this project. Employees already attached to a project will not appear here.') }}
                </p>
            </div>
            <a href="{{ route('site-officer.projects.show', $project) }}"
               class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-900">
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                {{ __('Back to Project') }}
            </a>
        </div>
    </x-slot>

    @php
        /** Generate a deterministic HSL color from a string */
        $avatarColor = function (string $name): string {
            $hash = 0;
            foreach (str_split($name) as $char) {
                $hash = ord($char) + (($hash << 5) - $hash);
            }
            $hue = abs($hash) % 360;
            return "hsl({$hue}, 55%, 45%)";
        };

        $initials = function (string $firstName, string $lastName): string {
            return strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1));
        };
    @endphp

    <div class="py-12" x-data='{
        search: "",
        selectAll: false,
        checkboxes: @json($allEmployees->mapWithKeys(fn ($e) => [(string) $e->id => false])->all()),
        toggleAll() {
            this.selectAll = !this.selectAll;
            Object.keys(this.checkboxes).forEach(k => this.checkboxes[k] = this.selectAll);
        },
        get selectedCount() {
            return Object.values(this.checkboxes).filter(Boolean).length;
        },
        matchesSearch(name, jobTitle) {
            if (!this.search.trim()) return true;
            const q = this.search.toLowerCase();
            return name.toLowerCase().includes(q) || jobTitle.toLowerCase().includes(q);
        }
    }'>
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 flex items-center gap-3 rounded-xl bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm dark:bg-emerald-900/20 dark:text-emerald-200">
                    <svg class="h-5 w-5 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('site-officer.projects.employees.sync', $project) }}">
                @csrf

                {{-- Header card --}}
                <div class="overflow-hidden rounded-2xl bg-white shadow-sm dark:bg-gray-800">

                    {{-- Toolbar --}}
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                {{-- Select All toggle --}}
                                <button type="button" @click="toggleAll()"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <span x-text="selectAll ? '{{ __('Deselect All') }}' : '{{ __('Select All') }}'"></span>
                                </button>

                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400" x-text="selectedCount"></span>
                                    {{ __('of') }} {{ $allEmployees->count() }} {{ __('selected') }}
                                </span>
                            </div>

                            {{-- Search --}}
                            <div class="relative w-full sm:w-72">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/></svg>
                                </span>
                                <input
                                    type="text"
                                    x-model="search"
                                    placeholder="{{ __('Search by name or job title…') }}"
                                    class="block w-full rounded-lg border-gray-300 py-2 pl-9 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"
                                >
                            </div>
                        </div>
                    </div>

                    {{-- Employee grid --}}
                    <div class="p-6">
                        @if ($allEmployees->isEmpty())
                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                <svg class="mb-4 h-12 w-12 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('No employees in the system yet.') }}</p>
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('Ask the Admin to add employees first.') }}</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($allEmployees as $employee)
                                    @php
                                        $color = $avatarColor($employee->fullName());
                                        $inits = $initials($employee->first_name, $employee->last_name);
                                    @endphp

                                    <label
                                        x-show="matchesSearch('{{ addslashes($employee->fullName()) }}', '{{ addslashes($employee->job_title) }}')"
                                        class="relative flex cursor-pointer items-start gap-4 rounded-xl border-2 p-4 transition-all duration-150"
                                        :class="checkboxes['{{ $employee->id }}']
                                            ? 'border-indigo-500 bg-indigo-50 shadow-sm dark:border-indigo-400 dark:bg-indigo-900/20'
                                            : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm dark:border-gray-700 dark:bg-gray-800/50 dark:hover:border-gray-600'"
                                    >
                                        {{-- Checkbox (hidden, controlled via x-model) --}}
                                        <input
                                            type="checkbox"
                                            name="employee_ids[]"
                                            value="{{ $employee->id }}"
                                            id="employee-{{ $employee->id }}"
                                            class="employee-checkbox sr-only"
                                            x-model="checkboxes['{{ $employee->id }}']"
                                        >

                                        {{-- Avatar --}}
                                        <div class="relative shrink-0">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-bold text-white shadow-inner"
                                                style="background-color: {{ $color }};"
                                            >
                                                {{ $inits }}
                                            </div>
                                            {{-- Checkmark overlay --}}
                                            <span
                                                x-show="checkboxes['{{ $employee->id }}']"
                                                x-transition
                                                x-cloak
                                                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 shadow"
                                            >
                                                <svg class="h-3 w-3 text-white" viewBox="0 0 12 12" fill="currentColor"><path d="M3.5 6.5l2 2 3-4" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
                                            </span>
                                        </div>

                                        {{-- Info --}}
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $employee->fullName() }}
                                            </p>
                                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                                {{ $employee->job_title }}
                                            </p>
                                            <span
                                                x-show="checkboxes['{{ $employee->id }}']"
                                                x-cloak
                                                class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
                                            >
                                                <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                {{ __('Selected') }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            {{-- No search results --}}
                            <div
                                x-show="search && !{{ $allEmployees->count() > 0 ? 'true' : 'false' }}"
                                x-cloak
                                class="hidden py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ __('No employees match your search.') }}
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/60">
                        <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <svg class="me-1 inline-block h-4 w-4 align-text-bottom text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
                                {{ __('Removing an employee preserves all their assignment history.') }}
                            </p>
                            <x-primary-button id="save-project-employees">
                                {{ __('Add Employees') }}
                            </x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
