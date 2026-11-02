<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" :value="__('Email (must be @eeccompany.com)')" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" placeholder="name@eeccompany.com" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password" :value="__('Password')" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
        @if ($user->exists)
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Leave blank to keep the current password.</p>
        @endif
    </div>

    <div>
        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
    </div>

    <div>
        <x-input-label for="role" :value="__('Role')" />
        <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
            <option value="">{{ __('Select role') }}</option>
            @foreach ($roles as $roleName)
                <option value="{{ $roleName }}" @selected(old('role', $user->roles->first()?->name) === $roleName)>{{ $roleName }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="project_id" :value="__('Primary Assigned Project')" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Assign a primary project directly to this user.') }}</p>
        <select id="project_id" name="project_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
            <option value="">{{ __('No project assigned') }}</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}" @selected(old('project_id', $user->project_id ?? $user->assigned_project_id) == $project->id)>
                    {{ $project->project_name }} {{ $project->project_code ? '('.$project->project_code.')' : '' }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('project_id')" class="mt-2" />
    </div>

    <div x-data="{ open: false, search: '', selected: {{ json_encode(old('project_ids', $user->exists ? $user->projects->pluck('id')->toArray() : [])) }} }">
        <x-input-label :value="__('Additional Assigned Projects')" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Select one or more projects to assign to this user.') }}</p>

        {{-- Hidden inputs for selected project IDs --}}
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="project_ids[]" :value="id" />
        </template>

        {{-- Dropdown trigger --}}
        <div class="relative mt-1">
            <button
                type="button"
                @click="open = ! open"
                class="flex w-full items-center justify-between rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            >
                <span x-text="selected.length === 0 ? '{{ __('No projects selected') }}' : selected.length + ' project(s) selected'"></span>
                <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                class="absolute z-10 mt-1 w-full rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
            >
                {{-- Search --}}
                <div class="border-b border-gray-100 p-2 dark:border-gray-700">
                    <input
                        type="text"
                        x-model="search"
                        placeholder="{{ __('Search projects...') }}"
                        class="w-full rounded-md border border-gray-200 px-3 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    />
                </div>

                {{-- Project list --}}
                <ul class="max-h-52 overflow-y-auto py-1">
                    @forelse ($projects as $project)
                        <li
                            x-show="search === '' || '{{ strtolower($project->project_name) }}'.includes(search.toLowerCase())"
                            @click="
                                selected.includes({{ $project->id }})
                                    ? selected = selected.filter(i => i !== {{ $project->id }})
                                    : selected.push({{ $project->id }})
                            "
                            class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <span
                                class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                                :class="selected.includes({{ $project->id }}) ? 'border-indigo-500 bg-indigo-500 text-white' : 'border-gray-300 dark:border-gray-600'"
                            >
                                <svg x-show="selected.includes({{ $project->id }})" class="h-3 w-3" viewBox="0 0 12 12" fill="none">
                                    <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $project->project_name }}</span>
                            @if ($project->project_code)
                                <span class="ml-auto text-xs text-gray-400">{{ $project->project_code }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="px-3 py-4 text-center text-sm text-gray-400">{{ __('No projects available.') }}</li>
                    @endforelse
                </ul>

                {{-- Footer actions --}}
                <div class="flex items-center justify-between border-t border-gray-100 px-3 py-2 dark:border-gray-700">
                    <button type="button" @click="selected = []" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Clear all') }}</button>
                    <button type="button" @click="open = false" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">{{ __('Done') }}</button>
                </div>
            </div>
        </div>

        {{-- Selected tags --}}
        <div class="mt-2 flex flex-wrap gap-1.5" x-show="selected.length > 0" x-cloak>
            @foreach ($projects as $project)
                <span
                    x-show="selected.includes({{ $project->id }})"
                    x-cloak
                    class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300"
                >
                    {{ $project->project_name }}
                    <button
                        type="button"
                        @click="selected = selected.filter(i => i !== {{ $project->id }})"
                        class="ml-0.5 rounded-full text-indigo-400 hover:text-indigo-600"
                        aria-label="{{ __('Remove') }}"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 12 12" fill="currentColor">
                            <path d="M9 3L3 9M3 3l6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </span>
            @endforeach
        </div>

        <x-input-error :messages="$errors->get('project_ids')" class="mt-2" />
        <x-input-error :messages="$errors->get('project_ids.*')" class="mt-2" />
    </div>
</div>
