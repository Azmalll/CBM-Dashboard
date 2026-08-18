<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'CBM System')
    </title>

    <script src="https://cdn.tailwindcss.com"></script>

    @vite(['resources/js/app.js'])

    <style>
        :root {
            --cbm-navy: #0F2D5C;
            --cbm-bg: #f1f5f9;
        }

        body {
            overflow-x: hidden;
        }

        .cbm-sidebar {
            width: 72px;
            transition: width 180ms ease;
        }

        .cbm-sidebar:hover {
            width: 248px;
        }

        .cbm-sidebar-label {
            opacity: 0;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 120ms ease, width 180ms ease;
        }

        .cbm-sidebar:hover .cbm-sidebar-label {
            opacity: 1;
            width: auto;
        }

        .cbm-nav-item {
            transition:
                transform 160ms ease,
                background-color 160ms ease,
                box-shadow 160ms ease;
        }

        .cbm-nav-item:hover {
            transform: translateX(3px) scale(1.025);
        }

        .cbm-main {
            margin-left: 72px;
            transition: margin-left 180ms ease;
        }

        .cbm-sidebar:hover ~ .cbm-main {
            margin-left: 248px;
        }

        @media (max-width: 640px) {
            .cbm-sidebar {
                width: 64px;
            }

            .cbm-sidebar:hover {
                width: 220px;
            }

            .cbm-main {
                margin-left: 64px;
            }

            .cbm-sidebar:hover ~ .cbm-main {
                margin-left: 220px;
            }
        }
    </style>

</head>


<body class="bg-slate-100 min-h-screen text-slate-800">

    {{-- =====================================================
        GLOBAL CBM SIDEBAR
    ====================================================== --}}

    <aside
        class="cbm-sidebar fixed left-0 top-0 bottom-0 z-50
               bg-[#0F2D5C] text-white shadow-xl
               flex flex-col overflow-hidden"
        aria-label="CBM navigation"
    >

        {{-- BRAND --}}
        <a
            href="{{ route('home') }}"
            class="h-20 shrink-0 flex items-center px-5
                   border-b border-white/10
                   hover:bg-white/5 transition"
            title="CBM System"
        >
            <div
                class="w-8 h-8 shrink-0 rounded-lg
                       bg-white/10 flex items-center justify-center
                       font-bold text-sm"
            >
                CBM
            </div>

            <div class="cbm-sidebar-label ml-3">
                <div class="font-bold text-sm tracking-wide">
                    CBM SYSTEM
                </div>
                <div class="text-[10px] text-blue-200 mt-0.5">
                    Condition Monitoring
                </div>
            </div>
        </a>


        {{-- NAVIGATION --}}
        <nav class="flex-1 px-3 py-5 space-y-2">

            {{-- DASHBOARD --}}
            <a
                href="{{ route('dashboard') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-3 py-3
                       {{ request()->routeIs('dashboard')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Dashboard"
            >
                <span class="w-8 text-center text-xl shrink-0">📊</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    Dashboard
                </span>
            </a>


            {{-- EQUIPMENT --}}
            <a
                href="{{ route('equipment.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-3 py-3
                       {{ request()->routeIs('equipment.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Equipment"
            >
                <span class="w-8 text-center text-xl shrink-0">⚙️</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    Equipment
                </span>
            </a>


            {{-- MEASUREMENT POINT --}}
            <a
                href="{{ route('measurement-point.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-3 py-3
                       {{ request()->routeIs('measurement-point.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Measurement Point"
            >
                <span class="w-8 text-center text-xl shrink-0">📍</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    Measurement Point
                </span>
            </a>


            {{-- INSPECTION SESSION --}}
            <a
                href="{{ route('inspection.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-3 py-3
                       {{ request()->routeIs('inspection.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Inspection Session"
            >
                <span class="w-8 text-center text-xl shrink-0">📋</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    Inspection Session
                </span>
            </a>


            {{-- MEASUREMENT RESULT --}}
            <a
                href="{{ route('measurement-result.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-3 py-3
                       {{ request()->routeIs('measurement-result.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Measurement Result"
            >
                <span class="w-8 text-center text-xl shrink-0">📈</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    Measurement Result
                </span>
            </a>


            {{-- ODX IMPORT --}}
            <a
                href="{{ route('odx-import.create') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-3 py-3
                       {{ request()->routeIs('odx-import.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="ODX Import"
            >
                <span class="w-8 text-center text-xl shrink-0">📥</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    ODX Import
                </span>
            </a>

        </nav>


        {{-- BOTTOM ACTIONS --}}
        <div class="px-3 pb-4 space-y-2 border-t border-white/10 pt-3">

            {{-- SYSTEM INFO / ABOUT --}}
            <button
                type="button"
                onclick="document.getElementById('cbmAboutModal').classList.remove('hidden')"
                class="cbm-nav-item w-full flex items-center gap-3
                       rounded-xl px-3 py-3
                       text-blue-100 hover:bg-white/10"
                title="System Info"
            >
                <span class="w-8 text-center text-xl shrink-0">ℹ️</span>
                <span class="cbm-sidebar-label font-semibold text-sm">
                    System Info
                </span>
            </button>


            {{-- LOGOUT --}}
            <form
                action="{{ route('logout') }}"
                method="POST"
            >
                @csrf

                <button
                    type="submit"
                    class="cbm-nav-item w-full flex items-center gap-3
                           rounded-xl px-3 py-3
                           text-red-200 hover:bg-red-500/15"
                    title="Logout"
                >
                    <span class="w-8 text-center text-xl shrink-0">↪</span>
                    <span class="cbm-sidebar-label font-semibold text-sm">
                        Logout
                    </span>
                </button>

            </form>

        </div>

    </aside>


    {{-- =====================================================
        PAGE CONTENT
    ====================================================== --}}

    <main class="cbm-main min-h-screen px-4 sm:px-6 py-6">

        @yield('content')

    </main>


    {{-- =====================================================
        SYSTEM INFO / ABOUT MODAL
    ====================================================== --}}

    <div
        id="cbmAboutModal"
        class="hidden fixed inset-0 z-[100]
               bg-slate-900/50 backdrop-blur-sm
               items-center justify-center p-4"
        onclick="if(event.target === this) this.classList.add('hidden')"
    >

        <div
            class="w-full max-w-md bg-white rounded-2xl shadow-2xl
                   overflow-hidden"
        >

            <div class="bg-[#0F2D5C] text-white px-6 py-5">

                <div class="flex items-center justify-between">

                    <div>
                        <div class="text-xs uppercase tracking-[0.18em]
                                    text-blue-200">
                            System Information
                        </div>

                        <h2 class="text-2xl font-bold mt-1">
                            CBM System
                        </h2>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('cbmAboutModal').classList.add('hidden')"
                        class="w-9 h-9 rounded-full bg-white/10
                               hover:bg-white/20 transition"
                    >
                        ×
                    </button>

                </div>

            </div>


            <div class="px-6 py-6">

                <p class="text-sm text-slate-500 leading-6">
                    Condition Based Maintenance Monitoring System for
                    vibration-based equipment condition monitoring.
                </p>


                <div class="mt-6 rounded-xl bg-slate-50 border border-slate-200 p-4">

                    <div class="text-xs uppercase tracking-wider
                                text-slate-400">
                        Platform
                    </div>

                    <div class="font-semibold text-[#0F2D5C] mt-1">
                        CBM Monitoring System
                    </div>

                </div>


                <div class="mt-4 text-center">

                    <div class="text-xs uppercase tracking-wider
                                text-slate-400">
                        Designed & Developed by
                    </div>

                    <div class="text-lg font-bold text-[#0F2D5C] mt-1">
                        Muhammad Azmal Fahri
                    </div>

                    <div class="text-xs text-slate-400 mt-1">
                        Condition Monitoring
                    </div>

                </div>

            </div>

        </div>

    </div>


    <script>
        const cbmAboutModal =
            document.getElementById('cbmAboutModal');

        document.addEventListener('keydown', function (event) {

            if (event.key === 'Escape' && cbmAboutModal) {
                cbmAboutModal.classList.add('hidden');
            }

        });

        document.addEventListener('click', function (event) {

            const button =
                event.target.closest(
                    '[data-cbm-about-open]'
                );

            if (button && cbmAboutModal) {
                cbmAboutModal.classList.remove('hidden');
            }

        });
    </script>

</body>

</html>
