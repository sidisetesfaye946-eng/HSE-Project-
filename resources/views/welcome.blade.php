<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'EEC HSE') }} — Health, Safety & Environment</title>
        <meta name="description" content="Ethiopian Engineering Corporation's unified HSE management platform.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;0,14..32,900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            * { font-family: 'Inter', sans-serif; }

            /* ── Full-screen light landing page ─────────────────── */
            .landing-bg {
                background: linear-gradient(150deg, #ffffff 0%, #f0f9ff 45%, #e6f4f8 100%);
                position: relative;
                overflow: hidden;
            }

            /* Animated mesh grid (light theme) */
            .landing-mesh {
                background-image:
                    linear-gradient(rgba(11,163,178,0.06) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(11,163,178,0.06) 1px, transparent 1px);
                background-size: 48px 48px;
                animation: meshMove 20s linear infinite;
            }
            @keyframes meshMove {
                0%   { background-position: 0 0; }
                100% { background-position: 48px 48px; }
            }

            /* Soft light glow orbs */
            .landing-orb-1 {
                width: 500px; height: 500px;
                background: radial-gradient(circle, rgba(11,163,178,0.12) 0%, transparent 70%);
                border-radius: 50%;
                position: absolute;
                top: -160px; left: -140px;
                animation: orbPulse 8s ease-in-out infinite;
            }
            .landing-orb-2 {
                width: 400px; height: 400px;
                background: radial-gradient(circle, rgba(7,198,214,0.15) 0%, transparent 70%);
                border-radius: 50%;
                position: absolute;
                bottom: -120px; right: -100px;
                animation: orbPulse 10s ease-in-out infinite reverse;
            }
            .landing-orb-3 {
                width: 280px; height: 280px;
                background: radial-gradient(circle, rgba(11,163,178,0.08) 0%, transparent 70%);
                border-radius: 50%;
                position: absolute;
                top: 40%; left: 65%;
                transform: translate(-50%,-50%);
                animation: orbPulse 12s ease-in-out infinite;
            }
            @keyframes orbPulse {
                0%,100% { transform: scale(1) translate(0,0); opacity: 0.8; }
                50%      { transform: scale(1.15) translate(12px,-12px); opacity: 1; }
            }

            /* Floating particles */
            .landing-particle {
                position: absolute;
                border-radius: 50%;
                background: rgba(11,163,178,0.35);
                animation: particleDrift linear infinite;
            }
            @keyframes particleDrift {
                0%   { transform: translateY(110vh) rotate(0deg); opacity: 0; }
                10%  { opacity: 1; }
                90%  { opacity: 1; }
                100% { transform: translateY(-10vh)  rotate(360deg); opacity: 0; }
            }

            /* Stat cards (light theme) */
            .landing-stat {
                background: rgba(255, 255, 255, 0.85);
                border: 1px solid rgba(11,163,178,0.18);
                backdrop-filter: blur(12px);
                box-shadow: 0 10px 30px -10px rgba(11,163,178,0.12);
                border-radius: 16px;
                padding: 20px 24px;
                transition: all 0.3s ease;
            }
            .landing-stat:hover {
                background: #ffffff;
                border-color: rgba(11,163,178,0.4);
                transform: translateY(-3px);
                box-shadow: 0 14px 35px -8px rgba(11,163,178,0.22);
            }

            /* Header action buttons */
            .landing-btn-login {
                padding: 9px 28px;
                border-radius: 10px;
                font-size: 0.85rem;
                font-weight: 600;
                color: #0f172a;
                border: 1.5px solid rgba(11,163,178,0.35);
                background: rgba(255,255,255,0.7);
                backdrop-filter: blur(8px);
                transition: all 0.25s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .landing-btn-login:hover {
                border-color: #0ba3b2;
                color: #0ba3b2;
                background: #ffffff;
                box-shadow: 0 4px 15px rgba(11,163,178,0.15);
            }

            .landing-btn-register {
                padding: 9px 28px;
                border-radius: 10px;
                font-size: 0.85rem;
                font-weight: 600;
                color: #fff;
                border: none;
                background: linear-gradient(135deg, #0ba3b2 0%, #07c6d6 50%, #0d7a8a 100%);
                background-size: 200% 200%;
                box-shadow: 0 4px 20px rgba(11,163,178,0.35);
                transition: all 0.3s ease;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .landing-btn-register:hover {
                background-position: right center;
                box-shadow: 0 6px 28px rgba(11,163,178,0.5);
                transform: translateY(-1px);
            }

            /* Animations */
            @keyframes fadeSlideUp {
                from { opacity: 0; transform: translateY(28px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .anim-hero    { animation: fadeSlideUp 0.7s cubic-bezier(0.22,1,0.36,1) 0.15s both; }
            .anim-stats   { animation: fadeSlideUp 0.7s cubic-bezier(0.22,1,0.36,1) 0.4s both; }
            .anim-header  { animation: fadeSlideUp 0.5s cubic-bezier(0.22,1,0.36,1) 0.05s both; }
        </style>
    </head>

    <body class="antialiased text-slate-800">

        <div class="landing-bg min-h-screen flex flex-col">

            {{-- Background layers --}}
            <div class="landing-mesh absolute inset-0 z-0"></div>
            <div class="landing-orb-1 z-0"></div>
            <div class="landing-orb-2 z-0"></div>
            <div class="landing-orb-3 z-0"></div>

            {{-- Floating particles --}}
            @for ($i = 0; $i < 14; $i++)
                @php
                    $size  = rand(3, 7);
                    $left  = rand(5, 95);
                    $delay = rand(0, 15);
                    $dur   = rand(12, 22);
                    $opac  = rand(25, 60) / 100;
                @endphp
                <div class="landing-particle z-0"
                     style="width:{{ $size }}px;height:{{ $size }}px;left:{{ $left }}%;bottom:-10px;
                            animation-duration:{{ $dur }}s;animation-delay:-{{ $delay }}s;opacity:{{ $opac }};"></div>
            @endfor

            {{-- ═══ HEADER ═══ --}}
            <header class="relative z-20 flex items-center justify-between px-8 lg:px-14 py-6 anim-header">
                {{-- Logo & Brand --}}
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl eec-gradient flex items-center justify-center shadow-lg shadow-cyan-600/30">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 12c0 1.087.14 2.14.398 3.143A11.959 11.959 0 0112 21a11.96 11.96 0 008.602-3.857A11.955 11.955 0 0021 12c0-1.087-.14-2.14-.398-3.144A11.96 11.96 0 0012 2.764z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-slate-900 font-bold text-sm tracking-wide">EEC HSE</p>
                        <p class="text-cyan-700 text-xs font-medium">Management System</p>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="landing-btn-register" id="landing-dashboard-btn">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="landing-btn-login" id="landing-login-btn">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="landing-btn-register" id="landing-register-btn">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            {{-- ═══ HERO CONTENT ═══ --}}
            <main class="relative z-10 flex-1 flex flex-col justify-center items-center text-center px-8 lg:px-14 xl:px-20 pb-10">
                <div class="max-w-3xl flex flex-col items-center text-center">

                    {{-- Title & Badge --}}
                    <div class="anim-hero flex flex-col items-center text-center">
                        <div class="inline-flex items-center gap-2 bg-cyan-500/10 border border-cyan-500/20 rounded-full px-4 py-1.5 mb-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-cyan-700 text-xs font-semibold tracking-wide">Operational Platform</span>
                        </div>

                        <h1 class="text-4xl sm:text-5xl xl:text-6xl font-black text-slate-900 leading-[1.08] tracking-tight">
                            Health, Safety &
                            <span class="text-transparent bg-clip-text"
                                  style="background-image: linear-gradient(90deg, #0ba3b2, #07c6d6);">
                                Environment
                            </span>
                        </h1>
                    </div>

                    {{-- Company Motto --}}
                    <div class="mt-8 anim-stats">
                        <p class="text-lg sm:text-xl font-medium tracking-wide text-slate-600">
                            Collaborate <span class="text-slate-400 mx-2">|</span> Innovate <span class="text-slate-400 mx-2">|</span> Deliver
                        </p>
                    </div>
                </div>
            </main>

            {{-- ═══ FOOTER ═══ --}}
            <footer class="relative z-10 px-8 lg:px-14 py-5 text-center">
                <p class="text-slate-500 text-xs font-medium">
                    © {{ date('Y') }} Ethiopian Engineering Corporation · All rights reserved
                </p>
            </footer>
        </div>

    </body>
</html>
