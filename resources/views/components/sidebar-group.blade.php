@props(['label' => '', 'open' => false, 'icon' => ''])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" class="space-y-0.5">
    <button type="button" @click="open = !open"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 transition-all duration-200 hover:bg-white/5 hover:text-slate-100"
        x-data
        :class="sidebarCollapsed ? 'justify-center px-2' : ''"
    >
        <div class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
            </svg>
            <span x-show="!sidebarCollapsed" x-cloak>{{ $label }}</span>
        </div>
        <svg x-show="!sidebarCollapsed" x-cloak class="h-4 w-4 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-collapse x-cloak class="space-y-0.5">
        <div x-data :class="sidebarCollapsed ? 'px-2' : 'pl-4'">
            {{ $slot }}
        </div>
    </div>
</div>