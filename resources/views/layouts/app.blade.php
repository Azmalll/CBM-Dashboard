<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        @yield('title', 'CBM System')
    </title>

    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Existing Vite / JavaScript --}}
    @vite(['resources/js/app.js'])

    <style>
        .cbm-sidebar {
            width: 68px;
            transition: width 180ms ease;
        }

        .cbm-sidebar:hover {
            width: 240px;
        }

        .cbm-sidebar-label {
            opacity: 0;
            max-width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 140ms ease, max-width 180ms ease;
        }

        .cbm-sidebar:hover .cbm-sidebar-label {
            opacity: 1;
            max-width: 180px;
        }

        .cbm-main {
            margin-left: 68px;
            transition: margin-left 180ms ease;
        }

        .cbm-sidebar:hover + .cbm-main {
            margin-left: 240px;
        }

        .cbm-nav-item {
            transition:
                transform 150ms ease,
                background-color 150ms ease,
                box-shadow 150ms ease;
        }

        .cbm-nav-item:hover {
            transform: translateX(3px) scale(1.025);
        }

        @media (max-width: 640px) {
            .cbm-sidebar {
                width: 60px;
            }

            .cbm-sidebar:hover {
                width: 220px;
            }

            .cbm-main {
                margin-left: 60px;
            }

            .cbm-sidebar:hover + .cbm-main {
                margin-left: 220px;
            }
        }
    </style>

    @stack('styles')
</head>


<body class="bg-slate-100 min-h-screen text-slate-800">

    {{-- =====================================================
        GLOBAL SIDEBAR
        Collapsed by default.
        Expands automatically while cursor is inside.
    ====================================================== --}}

    <aside
        class="cbm-sidebar fixed left-0 top-0 bottom-0 z-50
               bg-[#0F2D5C] text-white shadow-xl
               flex flex-col overflow-hidden"
    >

        {{-- BRAND / SYSTEM MARK --}}
        <a
            href="{{ route('home') }}"
            class="h-20 shrink-0 flex items-center px-4
                   border-b border-white/10
                   hover:bg-white/5 transition"
            title="CBM System"
        >

            <div
                class="w-9 h-9 shrink-0 rounded-xl
                       bg-white/10 border border-white/10
                       flex items-center justify-center
                       font-bold text-xs"
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


        {{-- MAIN NAVIGATION --}}
        <nav class="flex-1 px-3 py-5 space-y-2">

            {{-- DASHBOARD --}}
            <a
                href="{{ route('dashboard') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       {{ request()->routeIs('dashboard')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Dashboard"
            >
                <span class="w-9 text-center text-lg shrink-0">📊</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
                    Dashboard
                </span>
            </a>


            {{-- EQUIPMENT --}}
            <a
                href="{{ route('equipment.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       {{ request()->routeIs('equipment.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Equipment"
            >
                <span class="w-9 text-center text-lg shrink-0">⚙️</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
                    Equipment
                </span>
            </a>


            {{-- MEASUREMENT POINT --}}
            <a
                href="{{ route('measurement-point.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       {{ request()->routeIs('measurement-point.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Measurement Point"
            >
                <span class="w-9 text-center text-lg shrink-0">📍</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
                    Measurement Point
                </span>
            </a>


            {{-- INSPECTION SESSION --}}
            <a
                href="{{ route('inspection.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       {{ request()->routeIs('inspection.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Inspection Session"
            >
                <span class="w-9 text-center text-lg shrink-0">📋</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
                    Inspection Session
                </span>
            </a>


            {{-- MEASUREMENT RESULT --}}
            <a
                href="{{ route('measurement-result.index') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       {{ request()->routeIs('measurement-result.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="Measurement Result"
            >
                <span class="w-9 text-center text-lg shrink-0">📈</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
                    Measurement Result
                </span>
            </a>


            {{-- ODX IMPORT --}}
            <a
                href="{{ route('odx-import.create') }}"
                class="cbm-nav-item flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       {{ request()->routeIs('odx-import.*')
                            ? 'bg-white text-[#0F2D5C] shadow-sm'
                            : 'text-blue-100 hover:bg-white/10' }}"
                title="ODX Import"
            >
                <span class="w-9 text-center text-lg shrink-0">📥</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
                    ODX Import
                </span>
            </a>

        </nav>


        {{-- SYSTEM INFO + LOGOUT --}}
        <div class="px-3 pb-4 border-t border-white/10 pt-3 space-y-2">

            <button
                type="button"
                onclick="document.getElementById('cbmSystemInfo').classList.remove('hidden')"
                class="cbm-nav-item w-full flex items-center gap-3
                       rounded-xl px-2.5 py-3
                       text-blue-100 hover:bg-white/10"
                title="System Info"
            >
                <span class="w-9 text-center text-lg shrink-0">ℹ️</span>
                <span class="cbm-sidebar-label text-sm font-semibold">
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
                           rounded-xl px-2.5 py-3
                           text-red-200 hover:bg-red-500/15"
                    title="Logout"
                >
                    <span class="w-9 text-center text-lg shrink-0">↪</span>
                    <span class="cbm-sidebar-label text-sm font-semibold">
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
        SYSTEM INFO / ABOUT
    ====================================================== --}}

    <div
        id="cbmSystemInfo"
        class="hidden fixed inset-0 z-[100]
               bg-slate-900/50 backdrop-blur-sm
               items-center justify-center p-4"
        onclick="if (event.target === this) this.classList.add('hidden')"
    >

        <div
            class="w-full max-w-md bg-white rounded-2xl
                   shadow-2xl overflow-hidden"
        >

            <div class="bg-[#0F2D5C] text-white px-6 py-5">

                <div class="flex items-start justify-between gap-4">

                    <div>
                        <p class="text-xs uppercase tracking-[0.18em]
                                  text-blue-200">
                            System Information
                        </p>

                        <h2 class="text-2xl font-bold mt-1">
                            CBM System
                        </h2>
                    </div>

                    <button
                        type="button"
                        onclick="document.getElementById('cbmSystemInfo').classList.add('hidden')"
                        class="w-9 h-9 rounded-full bg-white/10
                               hover:bg-white/20 transition text-xl"
                    >
                        ×
                    </button>

                </div>

            </div>


            <div class="p-6">

                <p class="text-sm text-slate-500 leading-6">
                    A Condition Based Maintenance (CBM) system for vibration-based monitoring, trending, and condition assessment of rotating equipment.
                </p>


                <div class="mt-5 rounded-xl bg-slate-50
                            border border-slate-200 p-4">

                    <p class="text-[11px] uppercase tracking-wider
                              text-slate-400">
                        Platform
                    </p>

                    <p class="font-semibold text-[#0F2D5C] mt-1">
                        CBM Monitoring System
                    </p>

                </div>


                <div class="mt-6 text-center">

                    <div class="mt-6 text-center">

    <p class="text-[11px] uppercase tracking-wider text-slate-400">
        ENGINEERED BY
    </p>

    <p class="text-lg font-bold text-[#0F2D5C] mt-1">
        Muhammad Azmal Fahri
    </p>

    <p class="text-xs text-slate-400 mt-1">
        Condition-Based Maintenance Engineer | Vibration Analyst CAT II
    </p>

</div>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const modal =
                    document.getElementById('cbmSystemInfo');

                if (modal) {
                    modal.classList.add('hidden');
                }
            }
        });
    </script>

    @stack('scripts')

</body>

</html>