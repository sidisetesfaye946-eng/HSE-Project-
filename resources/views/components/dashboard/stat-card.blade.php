@props([
    'label',
    'value',
    'hint'  => null,
    'color' => 'eec',
    'icon'  => null,
])

@php
    $colorMap = [
        'eec'     => ['gradient' => 'from-cyan-500 to-teal-600',    'bg' => 'bg-cyan-500/10 dark:bg-cyan-500/15',    'text' => 'text-cyan-600 dark:text-cyan-400',    'border' => 'border-cyan-500/20'],
        'indigo'  => ['gradient' => 'from-indigo-500 to-violet-600', 'bg' => 'bg-indigo-500/10 dark:bg-indigo-500/15','text' => 'text-indigo-600 dark:text-indigo-400', 'border' => 'border-indigo-500/20'],
        'slate'   => ['gradient' => 'from-slate-500 to-gray-600',    'bg' => 'bg-slate-500/10 dark:bg-slate-500/15',  'text' => 'text-slate-600 dark:text-slate-400',   'border' => 'border-slate-500/20'],
        'blue'    => ['gradient' => 'from-blue-500 to-sky-600',      'bg' => 'bg-blue-500/10 dark:bg-blue-500/15',    'text' => 'text-blue-600 dark:text-blue-400',     'border' => 'border-blue-500/20'],
        'amber'   => ['gradient' => 'from-amber-500 to-orange-600',  'bg' => 'bg-amber-500/10 dark:bg-amber-500/15',  'text' => 'text-amber-600 dark:text-amber-400',   'border' => 'border-amber-500/20'],
        'emerald' => ['gradient' => 'from-emerald-500 to-green-600', 'bg' => 'bg-emerald-500/10 dark:bg-emerald-500/15','text' => 'text-emerald-600 dark:text-emerald-400','border' => 'border-emerald-500/20'],
        'rose'    => ['gradient' => 'from-rose-500 to-red-600',      'bg' => 'bg-rose-500/10 dark:bg-rose-500/15',    'text' => 'text-rose-600 dark:text-rose-400',     'border' => 'border-rose-500/20'],
    ];
    $c = $colorMap[$color] ?? $colorMap['eec'];
@endphp

<div class="card-hover group relative overflow-hidden rounded-2xl border bg-white p-5 dark:bg-gray-900 {{ $c['border'] }} border-opacity-50">
    {{-- Background gradient accent (top-right corner) --}}
    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $c['gradient'] }} opacity-8 group-hover:opacity-12 transition-opacity duration-300"></div>

    <div class="relative flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white animate-count-up">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-500">{{ $hint }}</p>
            @endif
        </div>
        {{-- Icon container --}}
        @if ($icon)
            <div class="shrink-0 rounded-xl {{ $c['bg'] }} p-3">
                <svg class="h-6 w-6 {{ $c['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    {!! $icon !!}
                </svg>
            </div>
        @else
            <div class="shrink-0 h-10 w-10 rounded-xl bg-gradient-to-br {{ $c['gradient'] }} opacity-80 shadow-lg"></div>
        @endif
    </div>

    {{-- Bottom accent line --}}
    <div class="mt-4 h-0.5 rounded-full bg-gradient-to-r {{ $c['gradient'] }} opacity-40"></div>
</div>
