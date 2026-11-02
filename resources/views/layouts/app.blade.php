<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EEC HSE') }} - HSE Management</title>
        <meta name="description" content="Ethiopian Engineering Corporation - HSE Material Management System">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

        <script>
            if (localStorage.getItem('theme') === 'dark' || (! localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 font-sans antialiased dark:bg-gray-950">
        <div
            x-data="{
                mobileOpen: false,
                sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
                toggleSidebar() {
                    this.sidebarCollapsed = ! this.sidebarCollapsed;
                    localStorage.setItem('sidebar-collapsed', this.sidebarCollapsed ? 'true' : 'false');
                }
            }"
            class="min-h-screen"
        >
            <header class="sticky top-0 z-40 grid h-16 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-4 border-b border-cyan-500/10 bg-[#0b1e2d]/90 backdrop-blur-md px-4 shadow-sm sm:px-6">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="mobileOpen = true"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500/50 lg:hidden"
                        aria-label="{{ __('Open menu') }}"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center rounded-md bg-white px-2 py-1 shadow-sm ring-1 ring-white/80">
                        <x-application-logo class="h-10 w-auto object-contain" />
                    </a>
                </div>

                @isset($header)
                    @php
                        $headerHtml = (string) $header;
                        preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', $headerHtml, $headerMatches);
                        $topbarTitle = trim(preg_replace('/\s+/', ' ', strip_tags($headerMatches[1] ?? $headerHtml)));
                    @endphp

                    <div class="hidden min-w-0 justify-center lg:flex">
                        <div class="max-w-md truncate rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-center text-sm font-semibold text-slate-100 shadow-inner">
                            {{ $topbarTitle }}
                        </div>
                    </div>
                @else
                    <div></div>
                @endisset

                <div class="flex items-center justify-end gap-1.5 sm:gap-2">
                    <x-notification-bell />

                    <div x-data="themeSwitcher()">
                        <button
                            type="button"
                            @click="toggleTheme()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500/50"
                            :aria-label="isDark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to night mode') }}'"
                        >
                            <svg x-show="! isDark" x-cloak class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m8.25-9h-2.25M6 12H3.75m14.03-5.78-1.59 1.59M7.81 16.19l-1.59 1.59m11.56 0-1.59-1.59M7.81 7.81 6.22 6.22M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                            <svg x-show="isDark" x-cloak class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                            </svg>
                        </button>
                    </div>

                    <x-dropdown align="right" width="52">
                        <x-slot name="trigger">
                            <button
                                id="user-menu-button"
                                class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500/50"
                            >
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden max-w-[120px] truncate sm:block">{{ Auth::user()->name }}</span>
                                <svg class="h-4 w-4 shrink-0 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Signed in as') }}</p>
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ Auth::user()->email }}</p>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </header>

            <div class="flex min-h-[calc(100vh-4rem)]">
                <div
                    x-show="mobileOpen"
                    x-cloak
                    @click="mobileOpen = false"
                    class="fixed inset-0 z-30 bg-gray-900/60 lg:hidden"
                    x-transition.opacity
                ></div>

                <aside
                    class="fixed inset-y-0 left-0 top-16 z-30 flex flex-col border-r border-cyan-500/10 bg-[#0b1e2d]/95 backdrop-blur-md transition-all duration-300 lg:sticky lg:z-auto lg:translate-x-0"
                    :class="{
                        'w-72 translate-x-0': mobileOpen,
                        '-translate-x-full': ! mobileOpen,
                        'lg:w-20': sidebarCollapsed,
                        'lg:w-64': ! sidebarCollapsed
                    }"
                >
                    <div class="h-0.5 w-full bg-gradient-to-r from-cyan-500 via-teal-400 to-cyan-500"></div>

                    <div class="flex items-center justify-end px-3 pb-2 pt-4">
                        <button
                            type="button"
                            @click="toggleSidebar()"
                            class="hidden h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-cyan-500/40 lg:inline-flex"
                            :aria-label="sidebarCollapsed ? '{{ __('Expand sidebar') }}' : '{{ __('Collapse sidebar') }}'"
                        >
                            <svg class="h-4 w-4 transition-transform duration-300" :class="{ 'rotate-180': ! sidebarCollapsed }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 9l-3 3m0 0 3 3m-3-3h7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="mobileOpen = false"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                            aria-label="{{ __('Close menu') }}"
                        >
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div x-show="! sidebarCollapsed" x-cloak class="px-4 pb-2">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-cyan-400/80">Navigation</p>
                    </div>

                    <div class="flex-1 overflow-y-auto px-3 pb-6">
                        <nav class="space-y-1.5">
                            <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                                icon="M3.75 12 12 4.5 20.25 12M5.25 10.5V19.5h4.5v-5.25h4.5V19.5h4.5V10.5"
                                :label="__('Dashboard')" />

                            @hasanyrole('Admin|HSE Officer')
                                <x-sidebar-group label="Materials" :open="request()->routeIs('materials.*', 'material-quantities.*', 'material-histories.*')"
                                    icon="m12 3.75 7.5 4.125v8.25L12 20.25l-7.5-4.125v-8.25L12 3.75ZM4.5 7.875 12 12l7.5-4.125M12 20.25V12">
                                    <x-sidebar-sub-link :href="route('materials.index')" :active="request()->routeIs('materials.*')">
                                        {{ __('Manage Material') }}
                                    </x-sidebar-sub-link>

                                    @role('HSE Officer')
                                        <x-sidebar-sub-link :href="route('material-quantities.index')" :active="request()->routeIs('material-quantities.*')">
                                            {{ __('Add Quantity') }}
                                        </x-sidebar-sub-link>
                                        <x-sidebar-sub-link :href="route('material-histories.index')" :active="request()->routeIs('material-histories.*')">
                                            {{ __('Material History') }}
                                        </x-sidebar-sub-link>
                                    @endrole
                                </x-sidebar-group>
                            @endhasanyrole

                            @hasanyrole('Admin|HSE Officer|HSE Site Officer')
                                <x-sidebar-group label="Projects" :open="request()->routeIs('projects.*', 'site-officer.projects.*')"
                                    icon="M3.75 6.75h16.5M5.25 6.75v12h13.5v-12M8.25 10.5h7.5M8.25 14.25h4.5">
                                    @role('HSE Site Officer')
                                        <x-sidebar-sub-link :href="route('site-officer.projects.index')" :active="request()->routeIs('site-officer.projects.index') || request()->routeIs('site-officer.projects.show')">
                                            {{ __('My Projects') }}
                                        </x-sidebar-sub-link>
                                    @endrole

                                    @hasanyrole('Admin|HSE Officer')
                                        <x-sidebar-sub-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                                            {{ __('Projects') }}
                                        </x-sidebar-sub-link>
                                    @endhasanyrole
                                </x-sidebar-group>
                            @endhasanyrole

                            @hasanyrole('Admin|HSE Site Officer|HSE Officer')
                                <x-sidebar-group label="Assign Material" :open="request()->routeIs('material-assignments.*', 'site-officer.employee-assignments.*', 'site-officer.material-requests.*', 'hse-officer.material-requests.*')"
                                    icon="M7.5 7.5h9M7.5 12h9M7.5 16.5h4.5M4.5 4.5h15v15h-15z">
                                    @role('HSE Site Officer')
                                        <x-sidebar-sub-link :href="route('site-officer.material-requests.create')" :active="request()->routeIs('site-officer.material-requests.*')">
                                            {{ __('Request Material') }}
                                        </x-sidebar-sub-link>
                                        @if (Auth::user()->name !== 'Daniel Girma' && Auth::user()->email !== 'danielg@example.com')
                                            <x-sidebar-sub-link :href="route('site-officer.employee-assignments.create')" :active="request()->routeIs('site-officer.employee-assignments.create')">
                                                {{ __('Assign to Employees') }}
                                            </x-sidebar-sub-link>
                                        @endif
                                    @endrole

                                    @hasanyrole('Admin|HSE Officer')
                                        <x-sidebar-sub-link :href="route('hse-officer.material-requests.index')" :active="request()->routeIs('hse-officer.material-requests.*')">
                                            {{ __('Material Requests') }}
                                        </x-sidebar-sub-link>
                                        <x-sidebar-sub-link :href="route('material-assignments.create')" :active="request()->routeIs('material-assignments.*')">
                                            {{ __('Assign to Project') }}
                                        </x-sidebar-sub-link>
                                    @endhasanyrole
                                </x-sidebar-group>
                            @endhasanyrole

                            @hasanyrole('HSE Site Officer|HSE Officer')
                                <x-sidebar-group label="Reports" :open="request()->routeIs('material-reports.*', 'site-officer.material-reports.*', 'site-officer.reports.*')"
                                    icon="M6.75 3.75h7.5L18.75 8.25v12h-12V3.75ZM14.25 3.75v4.5h4.5M9 13.5h6M9 16.5h6M9 10.5h2.25">
                                    @role('HSE Officer')
                                        <x-sidebar-sub-link :href="route('material-reports.head-office')" :active="request()->routeIs('material-reports.head-office')">
                                            {{ __('Head Office Report') }}
                                        </x-sidebar-sub-link>
                                        <x-sidebar-sub-link :href="route('material-reports.site')" :active="request()->routeIs('material-reports.site')">
                                            {{ __('Site Reports') }}
                                        </x-sidebar-sub-link>
                                    @endrole
                                    @role('HSE Site Officer')
                                        <x-sidebar-sub-link :href="route('site-officer.material-reports.index')" :active="request()->routeIs('site-officer.material-reports.*')">
                                            {{ __('Material Balance Report') }}
                                        </x-sidebar-sub-link>
                                        <x-sidebar-sub-link :href="route('site-officer.reports.employees')" :active="request()->routeIs('site-officer.reports.employees*')">
                                            {{ __('Employees Report') }}
                                        </x-sidebar-sub-link>
                                    @endrole
                                </x-sidebar-group>
                            @endhasanyrole

                            @hasanyrole('Admin|HSE Officer|HSE Head Office')
                                <x-sidebar-group label="User Management" :open="request()->routeIs('users.*', 'roles.*', 'employees.*')"
                                    icon="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0M18 8.25a2.625 2.625 0 0 1 0 5.25M21 20.25a5.25 5.25 0 0 0-3.75-5.03">
                                    @hasanyrole('Admin|HSE Officer')
                                        <x-sidebar-sub-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                            {{ __('Users') }}
                                        </x-sidebar-sub-link>
                                    @endhasanyrole

                                    @hasanyrole('Admin|HSE Head Office|HSE Officer')
                                        <x-sidebar-sub-link :href="route('employees.index')" :active="request()->routeIs('employees.*')">
                                            {{ __('Employees') }}
                                        </x-sidebar-sub-link>
                                    @endhasanyrole

                                    @role('Admin')
                                        <x-sidebar-sub-link :href="route('roles.index')" :active="request()->routeIs('roles.*')">
                                            {{ __('Roles') }}
                                        </x-sidebar-sub-link>
                                    @endrole
                                </x-sidebar-group>
                            @endhasanyrole
                        </nav>
                    </div>

                    <div x-show="! sidebarCollapsed" x-cloak class="shrink-0 border-t border-cyan-900/40 px-4 py-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-500 to-teal-600 text-xs font-bold text-white">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-200">{{ Auth::user()->name }}</p>
                                <p class="truncate text-[10px] text-slate-500">
                                    {{ Auth::user()->roles->first()?->name ?? __('No role') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    @isset($header)
                        <div class="border-b border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900/50">
                            <div class="flex items-center gap-3">
                                <div class="h-6 w-1 shrink-0 rounded-full bg-gradient-to-b from-cyan-500 to-teal-500"></div>
                                <div>{{ $header }}</div>
                            </div>
                        </div>
                    @endisset

                    @if (session('success'))
                        <div
                            x-data="{ show: true }"
                            x-show="show"
                            x-init="setTimeout(() => show = false, 5000)"
                            x-transition
                            class="mx-6 mt-4 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-800 dark:bg-emerald-900/20"
                        >
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">{{ session('success') }}</p>
                            <button type="button" @click="show = false" class="ml-auto text-emerald-500 hover:text-emerald-700" aria-label="{{ __('Dismiss') }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div
                            x-data="{ show: true }"
                            x-show="show"
                            x-init="setTimeout(() => show = false, 6000)"
                            x-transition
                            class="mx-6 mt-4 flex items-center gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-800 dark:bg-rose-900/20"
                        >
                            <p class="text-sm font-medium text-rose-800 dark:text-rose-300">{{ session('error') }}</p>
                            <button type="button" @click="show = false" class="ml-auto text-rose-500 hover:text-rose-700" aria-label="{{ __('Dismiss') }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endif

                    <main class="flex-1 p-6 lg:p-8">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
