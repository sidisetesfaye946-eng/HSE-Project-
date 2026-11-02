<div class="space-y-6">
    <div>
        <x-input-label for="project_name" :value="__('Project Name')" />
        <x-text-input id="project_name" name="project_name" type="text" class="mt-1 block w-full" :value="old('project_name', $project->project_name)" required autofocus />
        <x-input-error :messages="$errors->get('project_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="project_code" :value="__('Project Code')" />
        <x-text-input id="project_code" name="project_code" type="text" class="mt-1 block w-full" :value="old('project_code', $project->project_code)" required />
        <x-input-error :messages="$errors->get('project_code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="site_officer_id" :value="__('HSE Site Officer')" />
        <select id="site_officer_id" name="site_officer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300" required>
            <option value="">{{ __('Select site officer') }}</option>
            @foreach ($siteOfficers as $officer)
                <option value="{{ $officer->id }}" @selected((int) old('site_officer_id', $project->site_officer_id) === $officer->id)>{{ $officer->name }} ({{ $officer->email }})</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('site_officer_id')" class="mt-2" />
    </div>
</div>
