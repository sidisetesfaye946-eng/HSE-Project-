@props(['href' => '', 'active' => false])

@php
$classes = $active
    ? 'bg-cyan-500/10 text-cyan-400 font-semibold border-l-2 border-cyan-500 shadow-inner'
    : 'text-slate-400 hover:bg-white/5 hover:text-slate-100 border-l-2 border-transparent hover:translate-x-1 duration-200';
@endphp

<a href="{{ $href }}" class="flex items-center gap-3 rounded-md py-2 text-xs font-medium transition-all duration-200 {{ $classes }}"
    :class="sidebarCollapsed ? 'px-2 justify-center' : 'px-3'">
    {{ $slot }}
</a>