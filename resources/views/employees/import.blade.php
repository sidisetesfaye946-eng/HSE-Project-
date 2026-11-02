<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Import Employees') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6 overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-base font-semibold">{{ __('Step 1: Download template') }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Download the Excel template (.xls), fill in employee data using the exact column headers, then upload the file below.') }}
                    </p>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Columns') }}: <strong>employee_name</strong>, <strong>job_title</strong>, <strong>project</strong>, <strong>material_type</strong>, <strong>date</strong> (YYYY-MM-DD)
                    </p>
                    <a href="{{ route('employees.import.template') }}" class="mt-4 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                        {{ __('Download Template (.xls)') }}
                    </a>
                </div>
            </div>

            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-base font-semibold">{{ __('Step 2: Upload filled template') }}</h3>
                    <form method="POST" action="{{ route('employees.import.store') }}" enctype="multipart/form-data" class="mt-4">
                        @csrf

                        <div>
                            <x-input-label for="file" :value="__('Excel file (.xls or .xlsx)')" />
                            <input id="file" name="file" type="file" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-700" required />
                            <x-input-error :messages="$errors->get('file')" class="mt-2" />
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button>{{ __('Import Employees') }}</x-primary-button>
                            <a href="{{ Auth::user()->hasRole('HSE Site Officer') ? route('site-officer.projects.index') : route('employees.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
