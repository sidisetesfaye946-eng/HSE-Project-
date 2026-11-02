<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Site Material Balance Report') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Material balances for your assigned projects only') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">{{ __('Back to Dashboard') }}</a>
        </div>
    </x-slot>

    <div class="py-12 space-y-8">
        @if ($projects->isEmpty())
            <div class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400">
                {{ __('No projects are assigned to you yet.') }}
            </div>
        @else
            <div class="rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200">
                {{ __('Reporting for') }}:
                @foreach ($projects as $project)
                    <span class="font-medium">{{ $project->project_code }}</span>@if (! $loop->last), @endif
                @endforeach
            </div>

            @include('partials.material-inventory-summary', ['summary' => $summary, 'isHeadOffice' => false])
            @include('partials.material-inventory-detail-table', ['materials' => $materials, 'isHeadOffice' => false])
        @endif
    </div>
</x-app-layout>
