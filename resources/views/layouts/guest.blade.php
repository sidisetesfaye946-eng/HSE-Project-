<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'EEC HSE') }} — {{ __('Sign In') }}</title>
        <meta name="description" content="EEC HSE Management System — Secure portal for Ethiopian Engineering Corporation Health, Safety and Environment operations.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;0,14..32,900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Cloudflare Turnstile -->
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

        <style>
            * { font-family: 'Inter', sans-serif; }

            /* ── Minimal auth background ─────────────────────── */
            .auth-bg {
                background: linear-gradient(160deg, #f0f9ff 0%, #e8f4f8 50%, #f5faff 100%);
                position: relative;
                overflow: hidden;
            }

            /* Subtle mesh — very faint */
            .auth-mesh {
                background-image:
                    linear-gradient(rgba(11,163,178,0.03) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(11,163,178,0.03) 1px, transparent 1px);
                background-size: 56px 56px;
            }

            /* Login card */
            .login-card {
                background: rgba(255,255,255,0.92);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(11,163,178,0.12);
                box-shadow:
                    0 0 0 1px rgba(11,163,178,0.06),
                    0 32px 80px rgba(6,21,32,0.16),
                    0 0 80px rgba(11,163,178,0.06);
                border-radius: 24px;
                overflow: hidden;
            }

            /* Gradient top bar */
            .card-top-bar {
                height: 4px;
                background: linear-gradient(90deg, #07c6d6, #0ba3b2, #0d7a8a, #085f6c, #07c6d6);
                background-size: 300% 100%;
                animation: gradientFlow 4s linear infinite;
            }
            @keyframes gradientFlow {
                0%   { background-position: 0% 50%; }
                100% { background-position: 300% 50%; }
            }

            /* Input fields */
            .auth-input {
                display: block;
                width: 100%;
                border-radius: 12px;
                border: 1.5px solid #e2e8f0;
                background: rgba(248,250,252,0.7);
                padding: 12px 16px 12px 44px;
                font-size: 0.875rem;
                color: #0f172a;
                transition: all 0.2s ease;
                outline: none;
            }
            .auth-input:focus {
                border-color: #0ba3b2;
                background: #fff;
                box-shadow: 0 0 0 4px rgba(11,163,178,0.12);
            }
            .auth-input.error {
                border-color: #f43f5e;
            }
            .auth-input.error:focus {
                box-shadow: 0 0 0 4px rgba(244,63,94,0.12);
            }
            .auth-input::placeholder { color: #94a3b8; }

            /* Submit button */
            .signin-btn {
                width: 100%;
                padding: 13px 24px;
                border-radius: 12px;
                font-size: 0.9rem;
                font-weight: 600;
                color: #fff;
                border: none;
                cursor: pointer;
                background: linear-gradient(135deg, #0ba3b2 0%, #07c6d6 50%, #0d7a8a 100%);
                background-size: 200% 200%;
                box-shadow: 0 4px 20px rgba(11,163,178,0.35), 0 1px 3px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            .signin-btn:hover {
                background-position: right center;
                box-shadow: 0 8px 30px rgba(11,163,178,0.5), 0 2px 6px rgba(0,0,0,0.15);
                transform: translateY(-1px);
            }
            .signin-btn:active {
                transform: translateY(0);
                box-shadow: 0 2px 10px rgba(11,163,178,0.3);
            }
            .signin-btn::before {
                content: '';
                position: absolute;
                top: -50%; left: -60%;
                width: 40%; height: 200%;
                background: rgba(255,255,255,0.15);
                transform: skewX(-20deg);
                transition: left 0.5s ease;
            }
            .signin-btn:hover::before { left: 130%; }

            /* Floating animation for logo */
            @keyframes float {
                0%,100% { transform: translateY(0); }
                50%      { transform: translateY(-6px); }
            }
            .float-logo { animation: float 4s ease-in-out infinite; }

            /* Slide in animation for the card */
            @keyframes slideUp {
                from { opacity: 0; transform: translateY(24px); }
                to   { opacity: 1; transform: translateY(0); }
            }
            .slide-up { animation: slideUp 0.5s cubic-bezier(0.22,1,0.36,1) both; }

            /* Divider */
            .divider-line {
                flex: 1;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(11,163,178,0.25), transparent);
            }

            /* Back link */
            .back-link {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #64748b;
                font-size: 0.8rem;
                font-weight: 500;
                text-decoration: none;
                transition: color 0.2s ease;
            }
            .back-link:hover {
                color: #0ba3b2;
            }
        </style>
    </head>

    <body class="font-sans antialiased">

        <div class="auth-bg min-h-screen flex flex-col">

            {{-- Very subtle mesh overlay --}}
            <div class="auth-mesh absolute inset-0 z-0 pointer-events-none"></div>

            {{-- Soft decorative blobs — reduced from the old left panel --}}
            <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-cyan-100/40 blur-3xl pointer-events-none z-0"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-teal-100/30 blur-3xl pointer-events-none z-0"></div>

            {{-- Back to Home link --}}
            <div class="relative z-10 px-8 pt-6">
                <a href="{{ route('home') }}" class="back-link">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Back to Home
                </a>
            </div>

            {{-- Centred card area --}}
            <div class="relative z-10 flex-1 flex flex-col items-center justify-center px-6 py-8">

                {{-- Logo --}}
                <div class="mb-6 text-center slide-up">
                    <div class="inline-flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl eec-gradient flex items-center justify-center shadow-lg shadow-cyan-500/30 float-logo">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.955 11.955 0 003 12c0 1.087.14 2.14.398 3.143A11.959 11.959 0 0012 21a11.96 11.96 0 008.602-3.857A11.955 11.955 0 0021 12c0-1.087-.14-2.14-.398-3.144A11.96 11.96 0 0012 2.764z" />
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="text-slate-800 font-bold text-sm">EEC HSE</p>
                            <p class="text-slate-500 text-xs">Management System</p>
                        </div>
                    </div>
                </div>

                {{-- Login card --}}
                <div class="w-full max-w-[420px] slide-up">
                    <div class="login-card">
                        {{-- Top gradient bar --}}
                        <div class="card-top-bar"></div>

                        <div class="px-8 pt-8 pb-3">
                            {{-- Header --}}
                            <div class="mb-7">
                                <h1 class="text-xl font-bold text-slate-900 leading-tight">Welcome back</h1>
                                <p class="text-sm text-slate-500 mt-0.5 leading-relaxed">Sign in to the HSE Portal</p>
                                <div class="flex items-center gap-1.5 mt-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-[11px] text-emerald-600 font-semibold">Secure Connection</span>
                                </div>
                            </div>

                            {{-- Divider --}}
                            <div class="flex items-center gap-3 mb-6">
                                <div class="divider-line"></div>
                                <span class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider whitespace-nowrap">Credentials</span>
                                <div class="divider-line"></div>
                            </div>
                        </div>

                        {{-- Session status --}}
                        <div class="px-8">
                            <x-auth-session-status class="mb-4" :status="session('status')" />
                        </div>

                        {{-- Form --}}
                        <div class="px-8 pb-8">
                            {{ $slot }}
                        </div>
                    </div>

                    {{-- Footer text --}}
                    <p class="text-center text-xs text-slate-400 mt-5">
                        © {{ date('Y') }} Ethiopian Engineering Corporation
                    </p>
                </div>
            </div>
        </div>

    </body>
</html>
