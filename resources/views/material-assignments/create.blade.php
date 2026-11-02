<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ __('Assign Materials to Project') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm sm:rounded-lg bg-white dark:bg-gray-800">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('material-assignments.store') }}" x-data="assignmentForm()">
                        @csrf

                        <div class="space-y-6">
                            <div>
                                <x-input-label for="project_id" :value="__('Site (Project Code)')" />
                                <select id="project_id" name="project_id" x-model="projectId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                    <option value="">{{ __('Select project') }}</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" data-officer="{{ $project->siteOfficer?->name ?? '' }}" @selected(old('project_id') == $project->id)>
                                            {{ $project->project_code }} — {{ $project->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :value="__('Receiver (Site Officer)')" />
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="receiverName || '{{ __('Select a project to see the assigned site officer.') }}'"></p>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Materials') }}</h3>
                                    <button type="button" @click="addRow()" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ __('Add another material') }}</button>
                                </div>

                                <template x-for="(row, index) in rows" :key="row.id">
                                    <div class="grid gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" x-text="'{{ __('Material') }} ' + (index + 1)"></label>
                                            <select :name="'assignments[' + index + '][material_id]'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                                <option value="">{{ __('Select material') }}</option>
                                                @foreach ($materials as $material)
                                                    <option value="{{ $material->id }}">{{ $material->material_name }} ({{ __('Available') }}: {{ $material->quantity }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Quantity') }}</label>
                                            <input type="number" min="1" :name="'assignments[' + index + '][quantity]'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
                                        </div>
                                        <div class="sm:col-span-2" x-show="rows.length > 1">
                                            <button type="button" @click="removeRow(index)" class="text-sm text-red-600 hover:text-red-500">{{ __('Remove') }}</button>
                                        </div>
                                    </div>
                                </template>

                                <x-input-error :messages="$errors->get('assignments')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 flex items-center gap-4">
                            <x-primary-button>{{ __('Assign Materials') }}</x-primary-button>
                            <a href="{{ route('material-reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Cancel') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function assignmentForm() {
            const projects = @json($projects->map(fn ($p) => ['id' => $p->id, 'officer' => $p->siteOfficer?->name ?? '']));

            return {
                projectId: @json(old('project_id', '')),
                receiverName: '',
                rows: [{ id: Date.now() }],
                init() {
                    this.updateReceiver();
                    this.$watch('projectId', () => this.updateReceiver());
                },
                updateReceiver() {
                    const project = projects.find((item) => String(item.id) === String(this.projectId));
                    this.receiverName = project?.officer ? project.officer : '';
                },
                addRow() {
                    this.rows.push({ id: Date.now() + this.rows.length });
                },
                removeRow(index) {
                    this.rows.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
