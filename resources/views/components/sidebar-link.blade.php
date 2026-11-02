@props(['href', 'active' => false, 'icon' => '', 'label' => ''])

@php
$classes = $active
    ? 'bg-gradient-to-r from-cyan-500/20 to-teal-500/5 text-cyan-400 border-l-4 border-cyan-500 shadow-md shadow-cyan-950/50 font-semibold rounded-r-xl rounded-l-none'
    : 'text-slate-400 hover:bg-white/5 hover:text-slate-100 hover:translate-x-1 duration-200 border-l-4 border-transparent';
@endphp

<a href="{{ $href }}" title="{{ $label }}"
    class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ $classes }}"
    x-data
    :class="sidebarCollapsed ? 'justify-center px-2' : ''"
>
    <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak>{{ $label }}</span>
</a>